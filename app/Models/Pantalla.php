<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pantalla extends Model
{
    protected $fillable = [
        'user_id', 
        'nombre', 
        'slug_pantalla',
        'hablador_id',
    ];

    /**
     * La pantalla pertenece a un aliado específico.
     */
    public function aliado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}