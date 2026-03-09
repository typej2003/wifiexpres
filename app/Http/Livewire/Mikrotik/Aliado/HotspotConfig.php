<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class HotspotConfig extends Component
{
    use WithFileUploads;

    public $router_id;
    public $status, $is_active, $comercio_nombre, $comercio_banner, $is_store, $store, $address;
    public $is_promotion, $path_imgs = [], $photo;
    public $is_trial; 
    
    public $originalStatus;
    public $banner_photo; 

    public function mount($id)
    {
        $router = Router::findOrFail($id);
        
        if ($router->user_id !== Auth::id() && Auth::user()->role !== "admin") {
            abort(403);
        }

        $this->router_id = $router->id;
        $this->status = $router->status ?? "Habilitado";
        $this->originalStatus = $this->status;
        $this->is_active = $router->is_active;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->comercio_banner = $router->comercio_banner;
        $this->is_store = $router->is_store;
        $this->store = $router->store;
        $this->address = $router->address;
        $this->is_promotion = $router->is_promotion;
        $this->is_trial = $router->is_trial;

        $this->path_imgs = is_array($router->path_imgs) ? $router->path_imgs : [];
    }

    public function updatedBannerPhoto()
    {
        $this->validate(['banner_photo' => 'image|max:2048']);

        $router = Router::find($this->router_id);

        if ($router->comercio_banner) {
            if (Storage::disk('bannerrouter')->exists($router->comercio_banner)) {
                Storage::disk('bannerrouter')->delete($router->comercio_banner);
            }
        }

        $nombreOriginal = $this->banner_photo->getClientOriginalName();
        $this->banner_photo->storeAs('', $nombreOriginal, 'bannerrouter');

        $router->update(['comercio_banner' => $nombreOriginal]);

        $this->comercio_banner = $nombreOriginal;
        $this->banner_photo = null;
        
        session()->flash('message', 'Banner actualizado correctamente.');
    }

    // NUEVO MÉTODO PARA ELIMINAR EL BANNER
    public function removeBanner()
    {
        $router = Router::find($this->router_id);

        if ($router->comercio_banner) {
            if (Storage::disk('bannerrouter')->exists($router->comercio_banner)) {
                Storage::disk('bannerrouter')->delete($router->comercio_banner);
            }
            
            $router->update(['comercio_banner' => null]);
            $this->comercio_banner = null;

            session()->flash('message', 'Banner eliminado correctamente.');
        }
    }

    public function back()
    {
        if (Auth::user()->role === 'admin') {
            return redirect()->route("routers.index");
        }
        return redirect()->route("aliado.routers");
    }

    public function updatedPhoto()
    {
        $this->validate(["photo" => "image|max:2048"]);
        $filename = "promo_" . time() . "_" . uniqid() . "." . $this->photo->getClientOriginalExtension();
        $this->photo->storeAs("carruselhotspot", $filename, "public");
        
        $this->path_imgs[] = $filename;
        Router::find($this->router_id)->update(["path_imgs" => $this->path_imgs]);

        $this->photo = null;
        session()->flash("message", "Imagen añadida al carrusel.");
    }

    public function removeImage($index)
    {
        if (isset($this->path_imgs[$index])) {
            Storage::disk("public")->delete("carruselhotspot/" . $this->path_imgs[$index]);
            unset($this->path_imgs[$index]);
            $this->path_imgs = array_values($this->path_imgs);
            Router::find($this->router_id)->update(["path_imgs" => $this->path_imgs]);
            session()->flash("message", "Imagen eliminada del carrusel.");
        }
    }

    public function save()
    {
        $router = Router::find($this->router_id);
        $this->is_active = ($this->status === "Habilitado");

        $router->update([
            "status" => $this->status,
            "is_active" => $this->is_active,
            "comercio_nombre" => $this->comercio_nombre,
            "is_store" => $this->is_store,
            "store" => $this->store,
            "address" => $this->address,
            "is_promotion" => $this->is_promotion,
            "is_trial" => $this->is_trial,
            "path_imgs" => $this->path_imgs
        ]);

        $condicionSincronizar = (
            ($this->originalStatus === "Habilitado" && $this->status === "Suspendido") ||
            ($this->originalStatus === "Suspendido" && $this->status === "Habilitado")
        );

        if ($condicionSincronizar) {
            $result = $router->syncToMikrotik();
            if ($result === true) {
                session()->flash("message", "Sincronizado con MikroTik correctamente.");
            } else {
                session()->flash("error", "Error MikroTik: " . $result);
            }
        } else {
            session()->flash("message", "Cambios guardados localmente.");
        }

        $this->originalStatus = $this->status;
    }

    public function render()
    {
        return view("livewire.mikrotik.aliado.hotspot-config", [
            'router' => Router::find($this->router_id)
        ])->layout("layouts.app");
    }
}