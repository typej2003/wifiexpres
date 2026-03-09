<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'username', 'password', 'identity', 'plan', 
        'tiempo_uso', 'costo', 'activado', 'anulado', 'sincronizado', 'fecha_uso',
        'estado', 'tiempo_consumido',
    ];

    protected $casts = [
        'fecha_uso' => 'datetime',
        'activado' => 'boolean',
        'anulado' => 'boolean',
        'sincronizado' => 'boolean',
    ];

    public function router() {
        return $this->belongsTo(Router::class, 'router_id');
    }
}