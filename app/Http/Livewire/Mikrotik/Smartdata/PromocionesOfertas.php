<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\AdvertisingCampaign;
use App\Models\Router;
use App\Models\UserMikrotik;
use App\Models\CampaignResponse;
use App\Models\AgeRange;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromocionesOfertas extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterAliado = '';
    public $isAdmin = false;
    
    // Propiedades del Formulario
    public $isModalOpen = false;
    public $selected_id, $name, $description, $target_gender = 'todos', $router_identity;
    public $age_range_id;
    public $media_type = 'imagen', $media, $current_media_path;
    public $question_text = 'Publicidad Estándar', $question_type = 'simple';
    
    // Reglas de Envío (Nuevos campos)
    public $on_connect = false;
    public $only_new = false;

    public $user_id;

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
        $this->user_id = $this->isAdmin ? '' : Auth::id();
        $this->age_range_id = 0;
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->target_gender = 'todos';
        $this->router_identity = '';
        $this->age_range_id = 0;
        $this->media_type = 'imagen';
        $this->media = null;
        $this->question_text = '';
        $this->question_type = 'simple';
        $this->on_connect = false;
        $this->only_new = false;
        $this->selected_id = null;
        $this->current_media_path = null;
        if ($this->isAdmin) {
            $this->user_id = '';
        } else {
            $this->user_id = Auth::id();
        }
    }

    public function updatedUserId($value)
    {
        $this->router_identity = '';
        $this->age_range_id = 0;
    }

    // FUNCIÓN PARA CAMBIAR ESTADO ACTIVO/INACTIVO
    public function toggleStatus($id)
    {
        $campaign = AdvertisingCampaign::findOrFail($id);
        $campaign->active = !$campaign->active;
        $campaign->save();
    }

    public function edit($id)
    {
        $campaign = AdvertisingCampaign::findOrFail($id);
        $this->selected_id = $id;
        $this->name = $campaign->name;
        $this->description = $campaign->description;
        $this->target_gender = $campaign->target_gender;
        $this->router_identity = $campaign->router_identity;
        $this->age_range_id = $campaign->age_range_id;
        $this->media_type = $campaign->media_type;
        $this->question_text = $campaign->question_text;
        $this->question_type = $campaign->question_type;

        // Recuperar reglas de envío desde el JSON options
        $opts = $campaign->options ?? [];
        $this->on_connect = $opts['on_connect'] ?? false;
        $this->only_new = $opts['only_new'] ?? false;

        $this->user_id = $campaign->user_id;
        $this->current_media_path = $campaign->media_path;
        
        $this->isModalOpen = true;
    }

    public function addOption()
    {
    }

    public function removeOption($index)
    {
    }

    public function delete($id)
    {
        $campaign = AdvertisingCampaign::findOrFail($id);
        if ($campaign->media_path) {
            Storage::disk('public')->delete($campaign->media_path);
        }
        $campaign->delete();
        session()->flash('message', 'Campaña eliminada correctamente.');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'router_identity' => 'required',
            'user_id' => 'required',
            'age_range_id' => 'required',
            'media' => $this->selected_id ? 'nullable|max:20480' : 'required|max:20480',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'router_identity' => $this->router_identity,
            'user_id' => $this->user_id,
            'target_gender' => $this->target_gender,
            'age_range_id' => $this->age_range_id ?: 0,
            'media_type' => $this->media_type,
            'question_text' => 'Promoción Hotspot',
            'question_type' => 'simple',
            'options' => [
                'on_connect' => $this->on_connect,
                'only_new' => $this->only_new
            ],
        ];

        if ($this->media) {
            if ($this->selected_id && $this->current_media_path) {
                Storage::disk('public')->delete($this->current_media_path);
            }
            $originalName = $this->media->getClientOriginalName();
            $path = $this->media->storeAs('campaign', $originalName, 'public');
            $data['media_path'] = $path;
        }

        AdvertisingCampaign::updateOrCreate(['id' => $this->selected_id], $data);

        session()->flash('message', $this->selected_id ? 'Campaña actualizada.' : 'Campaña creada.');
        $this->closeModal();
    }

    public function render()
    {
        $query = AdvertisingCampaign::query()->with('user');
        if (!$this->isAdmin) {
            $query->where('user_id', Auth::id());
        } else {
            if ($this->filterAliado) {
                $query->where('user_id', $this->filterAliado);
            }
        }
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Obtener rangos de edad filtrados por el aliado seleccionado (o el logueado)
        // Esto asegura que al crear una campaña se vean solo los rangos del dueño de la misma.
        $userIdForRanges = $this->isAdmin ? $this->user_id : Auth::id();
        $ageRanges = $userIdForRanges 
            ? AgeRange::where('user_id', $userIdForRanges)->get() 
            : collect();

        $routers = $userIdForRanges
            ? Router::where('user_id', $userIdForRanges)->get()
            : collect();

        return view('livewire.mikrotik.smartdata.promociones-ofertas', [
            'campaigns' => $query->latest()->paginate(15),
            'aliados' => $this->isAdmin ? User::where('role', 'aliado')->orwhere('role', 'aliadoSmartData')->get() : [],
            'ageRanges' => $ageRanges,
            'routers' => $routers
        ])->layout('layouts.app');
    }

    /**
     * Obtiene únicamente la campaña activa para un router específico.
     * Mantiene la sintaxis de Request para consistencia operativa.
     */
    public static function getActiveCampaignByIdentity(Request $request)
    {
        $identity = $request->query('identity');
        $mac = $request->query('mac');

        // Encontrar el router de forma flexible (MAC o Identity)
        $router = Router::when($identity, function($q) use ($identity) {
                return $q->where('identity', $identity);
            })
            ->when($mac, function($q) use ($mac) {
                return $q->orWhere('macAddress', $mac);
            })
            ->first();

        // Si el router existe, usamos su identidad oficial; de lo contrario, el parámetro
        $campaign = AdvertisingCampaign::where('router_identity', $router ? $router->identity : $identity)
            ->where('active', true)
            ->first();

        return [
            'campaign' => $campaign
        ];
    }

    /**
     * Procesa y guarda la información del portal cautivo.
     * Soporta tanto el registro estándar como las respuestas de campaña.
     */
    public static function savePortalData(Request $request)
    {
        try {
            $mac = strtoupper($request->input('mac_cliente'));
            $identity = $request->input('identity');
            $isCampaign = $request->input('is_campaign', false);

            $router = Router::where('identity', $identity)->orWhere('macAddress', $identity)->first();
            if (!$router) {
                return response()->json(['success' => false, 'message' => 'Router no identificado'], 404);
            }

            // 1. Gestionar UserMikrotik (Siempre se crea o actualiza por MAC)
            $userData = [
                'router_id' => $router->id,
                'server'    => $identity,
                'macaddress'=> $mac,
                'password'  => '12345',
                'active'    => true,
            ];

            // Si no es campaña, vienen los datos personales estándar
            if (!$isCampaign) {
                $userData = array_merge($userData, [
                    'full_name'     => $request->input('full_name'),
                    'gender'        => $request->input('gender'),
                    'birthday'      => $request->input('birthday'),
                    'email'         => $request->input('email'),
                    'cellphonecode' => $request->input('cellphonecode'),
                    'cellphone'     => $request->input('cellphone'),
                    'profile'       => 'conexion_estandar'
                ]);
            }

            $userMikrotik = UserMikrotik::updateOrCreate(['name' => $mac, 'router_id' => $router->id], $userData);

            // 2. Gestionar Respuesta de Campaña si aplica
            if ($isCampaign) {
                $campaignId = $request->input('campaign_id');
                $campaign = AdvertisingCampaign::find($campaignId);

                if ($campaign) {
                    CampaignResponse::create([
                        'campaign_id'      => $campaignId,
                        'user_mikrotik_id' => $userMikrotik->id,
                        'mac_address'      => $mac,
                        'router_identity'  => $identity,
                        'answer'           => is_array($request->input('answer')) 
                                              ? json_encode($request->input('answer')) 
                                              : $request->input('answer'),
                        'campaign_name'           => $campaign->name,
                        'campaign_description'    => $campaign->description,
                        'campaign_target_gender'  => $campaign->target_gender,
                        'campaign_age_range_id'   => $campaign->age_range_id,
                        'campaign_media_type'     => $campaign->media_type,
                        'campaign_media_path'     => $campaign->media_path,
                        'campaign_question_text'  => $campaign->question_text,
                        'campaign_question_type'  => $campaign->question_type,
                        'campaign_options'        => $campaign->options,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (Exception $e) {
            \Log::error("Error al guardar data del portal: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }
}