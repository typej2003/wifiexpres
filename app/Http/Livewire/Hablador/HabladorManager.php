<?php

namespace App\Http\Livewire\Hablador;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hablador;
use App\Models\Pantalla;
use Illuminate\Support\Facades\Auth;

class HabladorManager extends Component
{
    use WithFileUploads;

    public $isModalOpen = false;
    public $modalMode = 'hablador'; 
    
    public $hablador_id, $nombre, $tipo = 'imagen';
    // Mantenemos la variable $productos para la interfaz, pero guardamos en 'caracteristicas'
    public $productos = []; 
    
    public $pantalla_id, $pantalla_nombre, $slug_pantalla;

    public function mount() {
        $this->resetProds();
    }

    public function resetProds() {
        $this->productos = [['nombre' => '', 'precio' => '', 'oferta' => '', 'imagen' => null]];
    }

    public function render() {
        return view('livewire.hablador.hablador-manager', [
            'habladores' => Hablador::where('user_id', Auth::id())->latest()->get(),
            'pantallas' => Pantalla::where('user_id', Auth::id())->get()
        ]);
    }

    public function createHablador() {
        $this->reset(['hablador_id', 'nombre', 'tipo']);
        $this->resetProds();
        $this->modalMode = 'hablador';
        $this->isModalOpen = true;
    }

    public function editHablador($id) {
        $h = Hablador::findOrFail($id);
        $this->hablador_id = $h->id;
        $this->nombre = $h->nombre;
        $this->tipo = $h->tipo;
        // Cargamos desde 'caracteristicas'
        $this->productos = $h->caracteristicas ?? [['nombre' => '', 'precio' => '', 'oferta' => '', 'imagen' => null]];
        $this->modalMode = 'hablador';
        $this->isModalOpen = true;
    }

    public function createPantalla() {
        $this->reset(['pantalla_id', 'pantalla_nombre', 'slug_pantalla']);
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

        // CORRECCIÓN: Guardamos en 'caracteristicas' en lugar de 'productos'
        Hablador::updateOrCreate(['id' => $this->hablador_id], [
            'user_id' => Auth::id(),
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'caracteristicas' => $productosFinales, 
            'recursos' => array_column($productosFinales, 'imagen')
        ]);

        $this->isModalOpen = false;
    }

    public function storePantalla() {
        $this->validate(['pantalla_nombre' => 'required', 'slug_pantalla' => 'required']);
        Pantalla::updateOrCreate(['id' => $this->pantalla_id], [
            'user_id' => Auth::id(),
            'nombre' => $this->pantalla_nombre,
            'slug_pantalla' => $this->slug_pantalla
        ]);
        $this->isModalOpen = false;
    }

    public function lanzarAPantalla($habladorId, $pantallaId) {
        $h = Hablador::find($habladorId);
        $p = Pantalla::find($pantallaId);
        $this->dispatchBrowserEvent('send-to-socket', [
            'room' => "pantalla-{$p->id}",
            'config' => [
                'tipo' => $h->tipo,
                'recursos' => array_map(fn($r) => asset('storage/'.$r), $h->recursos ?? []),
                'productos' => $h->caracteristicas // Usamos caracteristicas aquí también
            ]
        ]);
    }
}