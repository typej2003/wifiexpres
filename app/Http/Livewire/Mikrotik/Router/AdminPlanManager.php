<?php

namespace App\Http\Livewire\Mikrotik\Router;

use App\Http\Livewire\Mikrotik\Aliado\PlanManager;
use Illuminate\Support\Facades\Auth;

class AdminPlanManager extends PlanManager
{
    // Al heredar de PlanManager, ya tiene acceso a setConnectionLabel() porque es 'protected'
    
    public function backToRouters()
    {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.routers.index');
        }

        return redirect()->route('aliado.routers');
    }
}