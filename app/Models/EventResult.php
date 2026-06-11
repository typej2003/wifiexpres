<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'concurso_id',
        'etapa',
        'results',
    ];

    protected $casts = [
        'results' => 'array',
    ];

    /**
     * Relación con el concurso al que pertenecen estos resultados.
     */
    public function concurso()
    {
        return $this->belongsTo(AdvertisingConcurso::class, 'concurso_id');
    }
}
