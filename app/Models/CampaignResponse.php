<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_mikrotik_id',
        'answer',
    ];

    /**
     * Obtener la campaña a la que pertenece esta respuesta.
     */
    public function campaign()
    {
        return $this->belongsTo(AdvertisingCampaign::class, 'campaign_id');
    }

    /**
     * Obtener el usuario del hotspot que proporcionó la respuesta.
     */
    public function userMikrotik()
    {
        return $this->belongsTo(UserMikrotik::class, 'user_mikrotik_id');
    }
}