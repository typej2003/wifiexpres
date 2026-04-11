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
    
    public $hablador_id, $nombre, $tipo = 'imagen';
    public $productos = []; 
    
    public $pantalla_id, $pantalla_nombre, $slug_pantalla;

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

    // ... (Mantener métodos createHablador, editHablador, createPantalla, closeModal, agregarProducto, removerProducto)

    public function storeHablador() {
        $this->validate([
            'nombre' => 'required',
            'productos.*.nombre' => 'required'
        ]);

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
            // Importante: Si editamos como admin, mantenemos el user_id original
            'user_id' => $this->hablador_id ? Hablador::find($this->hablador_id)->user_id : Auth::id(),
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'caracteristicas' => $productosFinales, 
            'recursos' => array_column($productosFinales, 'imagen')
        ]);

        $this->isModalOpen = false;
    }

    // ... (Mantener storePantalla y lanzarAPantalla)
}