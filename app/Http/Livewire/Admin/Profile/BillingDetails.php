<?php

namespace App\Http\Livewire\Admin\Profile;

use App\Http\Livewire\Admin\AdminComponent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\DatosFacturacion;
use App\Models\Country;
use App\Models\Estado;
use App\Models\Cities;
use App\Models\DeliveryArea;

class BillingDetails extends AdminComponent
{

    public $state = [];

    public $user_id;
    public $country = 237;
    public $province;
    public $city;
    public $zona;
    public $countries = [], $provinces = [], $cities = [];

    public function mount($user_id)
    {
        $this->user_id = $user_id;

        $datosfacturacion = DatosFacturacion::where('user_id', $user_id)->first();

        if($datosfacturacion)
        {
            $this->state = $datosfacturacion->toArray();
        }

        $this->provinces = collect();
        $this->cities = collect();
        $this->zonas = collect();

        $this->countries = Country::all();

        $this->provinces = Estado::where('country_id', 237)->get();

        
    }

    public function updateBillingDetails()
    {
        $validatedData = Validator::make($this->state, [
            'identificationNac' => 'require|not_in:0',
            'identificationNumber' => 'require',
            'names' => 'require',
            'surnames' => 'require',
            'cellphonecode' => 'require|not_in:0',
            'cellphone' => 'require',
			'address' => 'require',
            'zipcode' => 'require',
		])->validate();

        $validatedData['country'] = $this->country;
        $validatedData['state'] = $this->province;
        $validatedData['city'] = $this->city;
        $validatedData['deliveryarea'] = $this->zona;        

        $datosfacturacion = DatosFacturacion::where('user_id', $this->user_id)->first();

        if($datosfacturacion)
        {
            $datosfacturacion->update($validatedData);
        }else{
            $validatedData['user_id'] = $this->user_id;

            DatosFacturacion::create($validatedData);

        }

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Datos Básicos actualizados satisfactoriamente!']);
    }

    public function updatedCountry($value)
	{
		$this->provinces = Estado::where('country_id', $value)->get();
		// $this->subcategory = $this->subcategories->first()->id ?? null;
	}

    public function updatedProvince($value)
	{
		$this->cities = Cities::where('state_id', $value)->get();
		// $this->subcategory = $this->subcategories->first()->id ?? null;
	}

    public function updatedCity($value)
	{
		$this->zonas = DeliveryArea::where('city_id', $value)->get();
		// $this->subcategory = $this->subcategories->first()->id ?? null;
	}

    public function render()
    {
        return view('livewire.admin.profile.billing-details');
    }
}
