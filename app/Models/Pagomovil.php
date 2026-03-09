<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagomovil extends Model
{
    use HasFactory;

    const CONFIRMADO = 'confirmado';
    const NOCONFIRMADO = 'noconfirmado';
    const RECHAZADO = 'rechazado';

    protected $fillable = [
        'referencia',
        'telefono',
        'banco',
        'monto',
        'status',
        'active',
        'user',
        'plan',
        'externalcomment',
        'fecha_pago',
        'identity',
    ];

    
}
