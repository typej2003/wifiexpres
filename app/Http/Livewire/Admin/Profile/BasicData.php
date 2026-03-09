<?php

namespace App\Http\Livewire\Admin\Profile;

use App\Http\Livewire\Admin\AdminComponent;

use Illuminate\Support\Facades\Validator;

use App\Models\DatosBasicos;

class BasicData extends AdminComponent
{
    public $state = [];

    public $user_id;

    public function mount($user_id)
    {
        $this->user_id = $user_id;

        $datosbasicos = DatosBasicos::where('user_id', $user_id)->first();

        $this->state = $datosbasicos->toArray();
    }

    public function updateBasicData()
    {
        $validatedData = Validator::make($this->state, [
			'address' => 'nullable',
            'cellphonecode' => 'nullable',
            'cellphone' => 'nullable',
		])->validate();

        $datosbasicos = DatosBasicos::where('user_id', $this->user_id)->first();

        if($datosbasicos)
        {
            $datosbasicos->update($validatedData);
        }else{
            $validatedData['user_id'] = $this->user_id;

            DatosBasicos::create($validatedData);

        }

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Datos Básicos actualizados satisfactoriamente!']);
    }

    public function render()
    {
        return view('livewire.admin.profile.basic-data');
    }
}
