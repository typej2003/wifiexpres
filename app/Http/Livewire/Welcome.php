<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Carrusel;

class Welcome extends Component
{
    public $device = 'd';

    protected $listeners = ['setDevice' => 'setDevice'];

    public function setDevice($type)
    {
        if (in_array($type, ['d', 't', 'm'])) {
            $this->device = $type;
        }
    }

    public function render()
    {
        $banners = Carrusel::where('active', 'active')
            ->where('bannerside', 1)
            ->where('device', $this->device)
            ->orderBy('order', 'asc')
            ->get();

        if ($banners->isEmpty() && $this->device !== 'd') {
            $banners = Carrusel::where('active', 'active')
                ->where('bannerside', 1)
                ->where('device', 'd')
                ->orderBy('order', 'asc')
                ->get();
        }

        return view('livewire.welcome', [
            'banners' => $banners
        ])->layout('layouts.app_guest');
    }
}