<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisingCampaign extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'target_gender', 
        'age_min', 'age_max', 'media_type', 'media_path', 'question', 'active'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function responses() {
        return $this->hasMany(CampaignResponse::class, 'campaign_id');
    }
}