<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'min_age',
        'max_age',
    ];

    /**
     * Obtener el aliado (usuario) dueño del rango de edad.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener las campañas publicitarias asociadas a este rango de edad.
     */
    public function advertisingCampaigns()
    {
        return $this->hasMany(AdvertisingCampaign::class, 'age_range_id');
    }
}