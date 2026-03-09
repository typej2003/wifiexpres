<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hablador extends Model
{
    protected $table = 'habladors';

    protected $fillable = [
        'user_id',
        'nombre',
        'tipo',
        'recursos',
        'audio_url',
        'caracteristicas', // Cambiado de productos a caracteristicas
        'activo'
    ];

    protected $casts = [
        'recursos' => 'array',
        'caracteristicas' => 'array', // Cambiado de productos a caracteristicas
        'activo' => 'boolean',
    ];

    public function aliado() {
        return $this->belongsTo(User::class, 'user_id');
    }
}