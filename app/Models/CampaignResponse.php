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
        'mac_address',
        'router_identity',
        'answer',
        'campaign_name',
        'campaign_description',
        'campaign_target_gender',
        'campaign_age_range_id',
        'campaign_media_type',
        'campaign_media_path',
        'campaign_question_text',
        'campaign_question_type',
        'campaign_options',
    ];

    protected $casts = [
        'campaign_options' => 'array',
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