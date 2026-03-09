<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $fillable = [
        'nombre_completo', 'telefono', 'email', 
        'direccion_servicio', 'fecha', 'hora', 'servicio', 'atendida'
    ];
}