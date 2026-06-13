<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\AdvertisingCampaign;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PromocionesOfertas extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Propiedades del Formulario
    public $isModalOpen = false;
    public $selected_id, $name, $description, $router_identity, $user_id;
    public $media, $current_media_path;
    
    // Reglas de Envío
    public $on_connect = false;
    public $only_new = false;

    // Filtros y Estado
    public $search = '';
    public $filterAliado = '';
    public $isAdmin = false;

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
        $this->user_id = $this->isAdmin ? '' : Auth::id();
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->router_identity = '';
        $this->user_id = $this->isAdmin ? '' : Auth::id();
        $this->media = null;
        $this->current_media_path = null;
        $this->on_connect = false;
        $this->only_new = false;
        $this->selected_id = null;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|min:3',
            'description' => 'required',
            'router_identity' => 'required',
            'user_id' => 'required',
            'media' => $this->selected_id ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'router_identity' => $this->router_identity,
            'user_id' => $this->user_id,
            'target_gender' => 'todos', // Simplificado para promociones
            'age_range_id' => 0,
            'media_type' => 'imagen',
            'question_text' => 'Promoción estándar', // Campo requerido por el modelo base
            'question_type' => 'simple',
            // Usamos campos existentes o extendemos la lógica
            'active' => true,
        ];

        // Manejo de la imagen
        if ($this->media) {
            if ($this->selected_id && $this->current_media_path) {
                Storage::disk('public')->delete($this->current_media_path);
            }
            $originalName = $this->media->getClientOriginalName();
            $path = $this->media->storeAs('campaign', $originalName, 'public');
            $data['media_path'] = $path;
        }

        // Guardamos las reglas en una estructura que el portal cautivo pueda leer
        // (En este caso, aprovechamos el campo options para guardar las reglas de negocio)
        $data['options'] = [
            'on_connect' => $this->on_connect,
            'only_new' => $this->only_new
        ];

        AdvertisingCampaign::updateOrCreate(['id' => $this->selected_id], $data);

        session()->flash('message', $this->selected_id ? 'Promoción actualizada.' : 'Promoción creada con éxito.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $promo = AdvertisingCampaign::findOrFail($id);
        $this->selected_id = $id;
        $this->name = $promo->name;
        $this->description = $promo->description;
        $this->router_identity = $promo->router_identity;
        $this->current_media_path = $promo->media_path;
        $this->user_id = $promo->user_id;
        
        // Recuperar reglas de envío
        $options = $promo->options ?? [];
        $this->on_connect = $options['on_connect'] ?? false;
        $this->only_new = $options['only_new'] ?? false;

        $this->isModalOpen = true;
    }

    public function toggleStatus($id)
    {
        $promo = AdvertisingCampaign::findOrFail($id);
        $promo->active = !$promo->active;
        $promo->save();
    }

    public function delete($id)
    {
        $promo = AdvertisingCampaign::findOrFail($id);
        if ($promo->media_path) {
            Storage::disk('public')->delete($promo->media_path);
        }
        $promo->delete();
        session()->flash('message', 'Promoción eliminada.');
    }

    public function render()
    {
        $user = Auth::user();
        
        $query = AdvertisingCampaign::query()->with('user')
            ->withCount('responses') // "A cuánta gente le ha llegado"
            ->when(!$this->isAdmin, function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->when($this->isAdmin && $this->filterAliado, function($q) {
                return $q->where('user_id', $this->filterAliado);
            })
            ->when($this->search, function($q) {
                return $q->where('name', 'like', '%' . $this->search . '%');
            });

        // Obtener routers del usuario seleccionado o del logueado
        $targetUserId = ($this->isAdmin && $this->user_id) ? $this->user_id : $user->id;
        
        $routers = Router::where('user_id', $targetUserId)->get();

        return view('livewire.mikrotik.smartdata.promociones-ofertas', [
            'promociones' => $query->latest()->paginate(15),
            'routers' => $routers,
            'aliados' => $this->isAdmin ? User::whereIn('role', ['aliado', 'aliadoSmartData'])->get() : []
        ]);
    }
}
