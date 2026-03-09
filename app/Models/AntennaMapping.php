<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntennaMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'ip_address', // La IP o prefijo (ej: 10.0.5.20 o 10.0.5.0/24)
        'location_name', // Ej: "Pasillo Norte", "Piso 2 - Tienda 40"
        'description'
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}