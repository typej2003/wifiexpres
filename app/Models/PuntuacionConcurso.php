<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntuacionConcurso extends Model
{
    use HasFactory;

    protected $fillable = [
        'concurso_id', 'full_name', 'cellphonecode', 'cellphone', 'puntaje'
    ];

    /**
     * Relación con el concurso al que pertenece esta puntuación.
     */
    public function concurso()
    {
        return $this->belongsTo(AdvertisingConcurso::class, 'concurso_id');
    }
}