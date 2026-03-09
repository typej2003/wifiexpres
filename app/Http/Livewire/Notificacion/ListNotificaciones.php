<?php

namespace App\Http\Livewire\Notificacion;

use App\Http\Livewire\Admin\AdminComponent;
use Illuminate\Http\Request;
use App\Http\Controllers\SmsTwilioController;
use App\Http\Controllers\SendMailController;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

use App\Models\Notificacion;
use App\Models\Comercio;
use App\Models\Client;


class ListNotificaciones extends AdminComponent
{
	use WithFileUploads;

	public $state = [];

    public $file;

	public $d_none = 'd-none';

	public $notificacion;

	public $comercio_id;

	public $showEditModal = false;

	public $notificacionIdBeingRemoved = null;

	public $searchTerm = null;

    protected $queryString = ['searchTerm' => ['except' => '']];

    public $sortColumnName = 'created_at';

    public $sortDirection = 'desc';

	protected $rules = [
        'file' => 'nullable|mimes:pdf,xlsx,xls,csv,txt,png,gif,jpg,jpeg|max:8192',
    ];

    public function mount($comercioId)
    {
    	$this->comercio_id = $comercioId;

    	
    }

    public function addNew()
	{
		$comercio_id = $this->comercio_id;
		$this->reset();
		$this->comercio_id = $comercio_id;

		
		$this->state['medio'] = 'email';
		$this->state['title'] = 'archivo';
		$this->state['content'] = 'contenido del archivo';

		$this->d_none = '';

		$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');
	}

	public function createNotificacion()
	{
		$validatedData = Validator::make($this->state, [
			'medio' => 'required',
            'title' => 'required',
            'content' => 'required',
		])->validate();

		$validatedData['comercio_id']=$this->comercio_id;

        $filename = $validatedData['title'].'-'.$this->comercio_id;

		if ($this->file) {
			dd('entro');
            $validatedData['adjunto'] = 1;
            $validatedData['file'] = $this->file->storeAs(null,
                $filename . '-1.png', 'filesnotificaciones'
            );     
		}
		else{
			dd('no entro');
		}

		Notificacion::create($validatedData);

		// session()->flash('message', 'User added successfully!');

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Notificación agregada satisfactoriamente!']);
	}

	public function edit(Notificacion $notificacion)
	{
		$comercio_id = $this->comercio_id;
		$this->reset();
		$this->comercio_id = $comercio_id;

		$this->showEditModal = true;

		$this->notificacion = $notificacion;

		$this->state = $notificacion->toArray();

		$this->dispatchBrowserEvent('show-form');
	}

	public function updateNotificacion()
	{
		$validatedData = Validator::make($this->state, [
			'medio' => 'required|not_in:0',
            'title' => 'required',
            'content' => 'required',
		])->validate();

        $filename = $validatedData['title'].'-'.$this->comercio_id;

        if ($this->file) {
            if (Storage::disk('filesnotificaciones')->exists($this->product->file)) {
				Storage::disk('filesnotificaciones')->delete($this->product->file);
			}
			$validatedData['file'] = $this->file->storeAs(null,
                $filename . '-1.png', 'filesnotificaciones'
            );     
		}

		$this->notificacion->update($validatedData);

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Marca actualizada satisfactoriamente!']);
	}

	public function confirmNotificacionRemoval($notificacionId)
	{
		$this->notificacionIdBeingRemoved = $notificacionId;

		$this->dispatchBrowserEvent('show-delete-modal');
	}

	public function deleteNotificacion()
	{
		$notificacion = Notificacion::findOrFail($this->notificacionIdBeingRemoved);

		$notificacion->delete();

		$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Notificación eliminada satisfactoriamente!']);
	}

    public function sendNotificacion(Notificacion $notificacion)
	{
		try {

			switch ($notificacion->medio) {
				case 'sms':
					$twilioSms = new SmsTwilioController;

					$clientes = Client::where('comercio_id', $notificacion->comercio_id)->get();

					foreach ($clientes as $key => $cliente) {
						$messageTwilio = $twilioSms->sendSms($cliente->telefono(), $notificacion->title, $notificacion->content);
					}
					break;				
				
				case 'email':

					$emailSend = new EmailController();

        			$emailSend->sendEmail('compra', auth()->user(), 'xxxxx');
					
					// $clientes = Client::where('comercio_id', $notificacion->comercio_id)->get();

					// foreach ($clientes as $key => $cliente) {
					// 	$notificacion->sendEmail('compra', $cliente, $newpedido->nropedido);
					// 	$sendFile->sendMailNotificacion($cliente, $notificacion);
					// }
					break;
			}
			
			
			$nro = $notificacion->nrosends;

			$nrosends = $nro + 1;

			$notificacion->update(['nrosends' => $nrosends ]);
			// session()->flash('message', 'User added successfully!');

			//$this->dispatchBrowserEvent('hide-form', ['message' => $messageTwilio]);

        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
	}

	public function saveNotificacion(Request $request)
	{

		$this->validate();

		$validatedData = Validator::make($this->state, [
			'medio' => 'required',
            'title' => 'required',
            'content' => 'required',
		])->validate();

		
		$validatedData['comercio_id']=$this->comercio_id;

       // Handle file upload
        if ($this->file) {
            // Generate a unique filename with microtime
            $filename = $validatedData['title'].'-'.$this->comercio_id;

            // Save the file to the storage directory
			// $validatedData['file'] = $this->file->storeAs(null,  $filename. '.' . $this->file->getClientOriginalExtension() , 'filesnotificaciones');

			$validatedData['file'] = $this->file->store('/', 'filesnotificaciones');
            
            $this->file = null;
        }
		
		Notificacion::create($validatedData);

        $this->dispatchBrowserEvent('hide-form', ['message' => 'Notificación agregada satisfactoriamente!']);        
		
	}

    public function sortBy($columnName)
    {
        if ($this->sortColumnName === $columnName) {
            $this->sortDirection = $this->swapSortDirection();
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortColumnName = $columnName;
    }

    public function swapSortDirection()
    {
        return $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function render()
    {
    	$notificaciones = Notificacion::query()
    		->where('comercio_id', $this->comercio_id)
            ->orderBy($this->sortColumnName, $this->sortDirection)
            ->paginate(15);

            $comercio = Comercio::find($this->comercio_id);

        return view('livewire.notificacion.list-notificaciones', [
        	'notificaciones' => $notificaciones,
            'comercio' => $comercio,
        ]);
    }
}
