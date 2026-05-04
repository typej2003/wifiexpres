<?php

namespace App\Http\Livewire\Hablador;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hablador;
use App\Models\Pantalla;
use App\Models\User; // Importamos User
use Illuminate\Support\Facades\Auth;

class HabladorManager extends Component
{
    use WithFileUploads;

    public $isModalOpen = false;
    public $modalMode = 'hablador'; 
    
    public $hablador_id, $nombre, $tipo = 'imagen', $activo = true, $assigned_user_id;
    public $productos = []; 
    
    public $pantalla_id, $pantalla_nombre, $slug_pantalla, $orientation = 'landscape';

    // NUEVAS PROPIEDADES PARA FILTRADO
    public $selectedAliado = ''; 

    public function mount() {
        $this->resetProds();
        // Si no es admin, el filtro por defecto es su propio ID
        if (auth()->user()->role != 'admin') {
            $this->selectedAliado = Auth::id();
        }
    }

    public function resetProds() {
        $this->productos = [['nombre' => '', 'precio' => '', 'oferta' => '', 'imagen' => null]];
    }

    public function render() {
        $user = auth()->user();
        
        // 1. Obtener Habladores según rol y filtro
        $queryHabladores = Hablador::query();
        
        if ($user->role == 'admin') {
            if ($this->selectedAliado) {
                $queryHabladores->where('user_id', $this->selectedAliado);
            }
            // Si es admin y no hay seleccionado, los muestra todos por defecto
        } else {
            // Si es aliado, solo los suyos
            $queryHabladores->where('user_id', Auth::id());
        }

        return view('livewire.hablador.hablador-manager', [
            'habladores' => $queryHabladores->latest()->get(),
            'pantallas' => Pantalla::where('user_id', Auth::id())->get(),
            // Enviamos la lista de aliados para el select del Admin
            'aliados' => $user->role == 'admin' ? User::where('role', 'aliado')->get() : []
        ]);
    }

    public function createHablador() {
        $this->resetProds();
        $this->hablador_id = null;
        $this->nombre = '';
        $this->tipo = 'imagen';
        $this->activo = true;
        $this->assigned_user_id = auth()->user()->role == 'admin' ? '' : Auth::id();
        $this->modalMode = 'hablador';
        $this->isModalOpen = true;
    }

    public function editHablador($id) {
        $hablador = Hablador::findOrFail($id);
        $this->hablador_id = $id;
        $this->nombre = $hablador->nombre;
        $this->tipo = $hablador->tipo;
        $this->activo = $hablador->activo;
        $this->assigned_user_id = $hablador->user_id;
        $this->productos = $hablador->caracteristicas ?? [['nombre' => '', 'precio' => '', 'oferta' => '', 'imagen' => null]];
        $this->modalMode = 'hablador';
        $this->isModalOpen = true;
    }

    public function createPantalla() {
        $this->pantalla_id = null;
        $this->pantalla_nombre = '';
        $this->slug_pantalla = '';
        $this->orientation = 'landscape';
        $this->assigned_user_id = auth()->user()->role == 'admin' ? '' : Auth::id();
        $this->modalMode = 'pantalla';
        $this->isModalOpen = true;
    }

    public function closeModal() {
        $this->isModalOpen = false;
    }

    public function agregarProducto() {
        $this->productos[] = ['nombre' => '', 'precio' => '', 'oferta' => '', 'imagen' => null];
    }

    public function removerProducto($index) {
        unset($this->productos[$index]);
        $this->productos = array_values($this->productos);
    }

    public function lanzarAPantalla($habladorId, $pantallaId) {
        $pantalla = Pantalla::find($pantallaId);
        if ($pantalla) {
            $pantalla->update(['hablador_id' => $habladorId]);
            session()->flash('message', 'Transmitiendo contenido...');
            $this->render();
        }
    }

    public function storeHablador() {
        $this->validate([
            'nombre' => 'required',
            'tipo' => 'required|string',
            'productos.*.nombre' => 'required',
            'assigned_user_id' => auth()->user()->role == 'admin' ? 'required' : 'nullable',
        ]);

        // Validación manual para imágenes solo si se está subiendo un archivo nuevo
        foreach ($this->productos as $index => $prod) {
            if (isset($prod['imagen']) && !is_string($prod['imagen'])) {
                $this->validate([
                    "productos.$index.imagen" => 'image|mimes:jpg,jpeg,png|max:2048'
                ]);
            }
        }

        $productosFinales = [];
        foreach ($this->productos as $prod) {
            $imgPath = $prod['imagen'] ?? null;
            
            if (isset($prod['imagen']) && !is_string($prod['imagen'])) {
                $imgPath = $prod['imagen']->store('productos', 'public');
            }

            $productosFinales[] = [
                'nombre' => $prod['nombre'],
                'precio' => $prod['precio'],
                'oferta' => $prod['oferta'],
                'imagen' => $imgPath
            ];
        }

        Hablador::updateOrCreate(['id' => $this->hablador_id], [
            'user_id' => $this->assigned_user_id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'caracteristicas' => $productosFinales,
            'recursos' => array_column($productosFinales, 'imagen'),
            'activo' => (bool)$this->activo,
        ]);

        $this->isModalOpen = false;
    }

    public function storePantalla() {
        $this->validate([
            'pantalla_nombre' => 'required',
            'slug_pantalla' => 'required',
            'assigned_user_id' => auth()->user()->role == 'admin' ? 'required' : 'nullable',
        ]);

        Pantalla::updateOrCreate(['id' => $this->pantalla_id], [
            'user_id' => $this->assigned_user_id,
            'nombre' => $this->pantalla_nombre,
            'slug_pantalla' => $this->slug_pantalla,
            'orientation' => $this->orientation,
        ]);

        $this->isModalOpen = false;
    }
}