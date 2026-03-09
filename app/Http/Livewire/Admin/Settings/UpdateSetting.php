<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class UpdateSetting extends Component {
    public $state = [];
    public $setting;

    public function mount() {
        // Obtenemos la configuración del admin principal o del usuario actual
        $this->setting = Setting::where('user_id', auth()->id())->first();
        
        if ($this->setting) {
            $this->state = $this->setting->toArray();
        } else {
            $this->state = [
                'user_id' => auth()->id(),
                'mikrotik_connection_mode' => 0,
                'api_bcv' => 'NO',
                'dollar_rate' => 36.00, // Valor inicial
                'currency' => '$',
                'sidebar_collapse' => false,
                'in_cellphonecontact' => false,
                'in_sliderprincipal' => true,
                'site_name' => 'RedNet Hotspot'
            ];
        }
    }

    public function updateSetting() {
        $this->state['user_id'] = auth()->id();
        
        if ($this->setting) {
            $this->setting->update($this->state);
        } else {
            $this->setting = Setting::create($this->state);
        }

        // Limpiar caché para que el Servicio lea los nuevos datos
        Cache::forget('setting');
        $this->dispatchBrowserEvent('updated', ['message' => 'Configuración guardada!']);
    }

    public function render() {
        return view('livewire.admin.settings.update-setting')->layout('layouts.app');
    }
}