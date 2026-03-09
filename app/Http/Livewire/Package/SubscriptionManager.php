<?php

namespace App\Http\Livewire\Package;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\Package;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class SubscriptionManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    public $search = '';

    // Propiedades para el cambio de plan
    public $isChangePlanModalOpen = false;
    public $selectedUserId, $selectedPackageId, $newPackageId;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function approveSubscription($userId, $packageId)
    {
        $user = User::findOrFail($userId);
        $package = Package::findOrFail($packageId);
        
        $user->packages()->updateExistingPivot($packageId, [
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths($package->duration_months ?? 1),
            'allowed_routers' => $package->limit_routers 
        ]);

        $routers = Router::where('user_id', $user->id)
                         ->where('package_id', $packageId)
                         ->get();

        foreach ($routers as $router) {
            $router->update([
                'status' => 'activo',
                'is_active' => true
            ]);
            $router->syncToMikrotik();
        }

        session()->flash('message', "Suscripción al plan '{$package->name}' activada.");
    }

    public function toggleStatus($userId, $packageId, $currentStatus)
    {
        $user = User::findOrFail($userId);
        $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';
        
        $user->packages()->updateExistingPivot($packageId, [
            'status' => $newStatus
        ]);

        $isActive = ($newStatus === 'active');
        $routers = Router::where('user_id', $user->id)
                         ->where('package_id', $packageId)
                         ->get();
        
        foreach ($routers as $router) {
            $router->update([
                'status' => $isActive ? 'activo' : 'suspendido',
                'is_active' => $isActive
            ]);
            $router->syncToMikrotik();
        }

        $msg = $isActive 
            ? "Plan y sus routers reactivados." 
            : "Plan y sus routers suspendidos.";

        session()->flash('message', $msg);
    }

    public function openChangePlanModal($userId, $packageId)
    {
        $this->selectedUserId = $userId;
        $this->selectedPackageId = $packageId;
        $this->newPackageId = $packageId; 
        $this->isChangePlanModalOpen = true;
    }

    public function closeChangePlanModal()
    {
        $this->isChangePlanModalOpen = false;
        $this->reset(['selectedUserId', 'selectedPackageId', 'newPackageId']);
    }

    public function updatePlan()
    {
        $this->validate([
            'newPackageId' => 'required|exists:packages,id'
        ]);

        try {
            DB::transaction(function () {
                $user = User::findOrFail($this->selectedUserId);
                $newPackage = Package::findOrFail($this->newPackageId);

                // Desvincular anterior y vincular nuevo
                $user->packages()->detach($this->selectedPackageId);
                $user->packages()->attach($this->newPackageId, [
                    'start_date' => now(),
                    'end_date' => now()->addMonths($newPackage->duration_months ?? 1),
                    'status' => 'active',
                    'allowed_routers' => $newPackage->limit_routers
                ]);

                // Migrar los routers del plan viejo al nuevo
                Router::where('user_id', $this->selectedUserId)
                    ->where('package_id', $this->selectedPackageId)
                    ->update([
                        'package_id' => $this->newPackageId,
                        'status' => 'activo',
                        'is_active' => true
                    ]);
            });

            session()->flash('message', 'Plan actualizado y routers reasignados con éxito.');
            $this->closeChangePlanModal();
        } catch (\Exception $e) {
            session()->flash('message', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $usuarios = User::whereHas('packages')
            ->where(function($q) {
                $q->where('names', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->with(['packages' => function($q) {
                $q->withPivot('status', 'start_date', 'end_date', 'package_id', 'allowed_routers')
                  ->withCount(['routers' => function($query) {
                      $query->whereColumn('routers.user_id', 'package_user.user_id')
                            ->where('is_active', true);
                  }]);
            }])
            ->paginate(10);

        return view('livewire.package.subscription-manager', [
            'usuarios' => $usuarios,
            'allPackages' => Package::where('is_active', true)->get()
        ])->layout('layouts.app');
    }
}