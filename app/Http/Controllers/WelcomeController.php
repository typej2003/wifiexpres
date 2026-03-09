<?php

namespace App\Http\Controllers;
use App\Http\Livewire\Admin\AdminComponent;

use Illuminate\Http\Request;
use App\Models\Comercio;
use App\Models\Category;
use App\Models\Setting;
use App\Models\SettingUser;
use App\Models\User;


use App\Events\NewEventCreated;

class WelcomeController extends Controller
{
    protected $listeners = [
        'receiveManufacturerS' => 'receiveManufacturerS', 
        'receiveModeloS' => 'receiveModeloS', 
        'receiveMotorS' => 'receiveMotorS', 
        'emitCurrency' => 'emitCurrency'
    ];

    public $words = '';

    public $state = [];

    public $manufacturer_id, $modelo_id, $motor_id;

    public $currencyValue;

    public function __invoke(Request $request)
    {

       
        
    }

    public function index(Request $request)
    {
        return view('welcome');
    }

    

}
