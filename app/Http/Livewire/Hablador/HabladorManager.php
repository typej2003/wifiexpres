<?php

namespace App\Http\Livewire\Hablador;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hablador;
use App\Models\Pantalla;
use App\Models\User; // Importamos User
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        
        // Mejoramos la obtención de pantallas para que el admin vea las del aliado seleccionado
        $queryPantallas = Pantalla::query();
        if ($user->role == 'admin') {
            if ($this->selectedAliado) {
                $queryPantallas->where('user_id', $this->selectedAliado);
            }
        } else {
            $queryPantallas->where('user_id', Auth::id());
        }

        return view('livewire.hablador.hablador-manager', [
            'habladores' => $queryHabladores->latest()->get(),
            'pantallas' => $queryPantallas->with('hablador')->get(),
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

    public function editPantalla($id) {
        $pantalla = Pantalla::findOrFail($id);
        $this->pantalla_id = $id;
        $this->pantalla_nombre = $pantalla->nombre;
        $this->slug_pantalla = $pantalla->slug_pantalla;
        $this->orientation = $pantalla->orientation;
        $this->assigned_user_id = $pantalla->user_id;
        $this->modalMode = 'pantalla';
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

    public function deleteHablador($id) {
        $hablador = Hablador::findOrFail($id);
        
        // 1. Eliminar archivos físicos de los productos para no llenar el servidor
        if ($hablador->recursos) {
            foreach ($hablador->recursos as $img) {
                if (!empty($img) && is_string($img)) {
                    Storage::disk('habladores')->delete($img);
                }
            }
        }

        // 2. Desvincular de pantallas activas
        Pantalla::where('hablador_id', $id)->update(['hablador_id' => null]);

        $hablador->delete();
        session()->flash('message', 'Hablador eliminado con éxito.');
    }

    public function deletePantalla($id) {
        Pantalla::findOrFail($id)->delete();
        session()->flash('message', 'Pantalla eliminada del sistema.');
    }

    public function storeHablador() {
        $this->validate([
            'nombre' => 'required',
            'tipo' => 'required|string',
            'productos.*.nombre' => 'required',
            'assigned_user_id' => auth()->user()->role == 'admin' ? 'required' : 'nullable',
        ]);

        // Validación manual para imágenes y videos
        foreach ($this->productos as $index => $prod) {
            if (isset($prod['imagen']) && !is_string($prod['imagen'])) {
                $this->validate([
                    "productos.$index.imagen" => 'file|mimes:jpg,jpeg,png,mp4,mov,avi,webm|max:20480'
                ]);
            }
        }

        // Obtener el registro anterior para la limpieza de archivos
        $oldHablador = $this->hablador_id ? Hablador::find($this->hablador_id) : null;
        $oldImages = $oldHablador ? ($oldHablador->recursos ?? []) : [];

        $productosFinales = [];
        foreach ($this->productos as $prod) {
            $imgPath = $prod['imagen'] ?? null;
            
            if (isset($prod['imagen']) && !is_string($prod['imagen'])) {
                // Conservar nombre original con un prefijo único
                $originalName = time() . '_' . $prod['imagen']->getClientOriginalName();
                $imgPath = $prod['imagen']->storeAs('/', $originalName, 'habladores');
            }

            $productosFinales[] = [
                'nombre' => $prod['nombre'],
                'precio' => $prod['precio'],
                'oferta' => $prod['oferta'],
                'imagen' => $imgPath
            ];
        }

        $nuevasImagenes = array_filter(array_column($productosFinales, 'imagen'));

        // Limpieza: Eliminar archivos físicos que ya no están en la lista (reemplazados o quitados)
        foreach ($oldImages as $oldImg) {
            if ($oldImg && !in_array($oldImg, $nuevasImagenes)) {
                Storage::disk('habladores')->delete($oldImg);
            }
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