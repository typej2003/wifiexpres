<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_age',
        'max_age',
    ];

    /**
     * Obtener las campañas publicitarias asociadas a este rango de edad.
     */
    public function advertisingCampaigns()
    {
        return $this->hasMany(AdvertisingCampaign::class, 'age_range_id');
    }
}