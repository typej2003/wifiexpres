<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'site_name',
        'site_email',
        'site_title',
        'currency',
        'api_bcv',
        'dollar_rate', // <--- Nueva columna
        'mikrotik_connection_mode',
        'sidebar_collapse',
        'in_cellphonecontact',
        'in_sliderprincipal',
    ];

    protected $casts = [
        'mikrotik_connection_mode' => 'integer', 
        'sidebar_collapse' => 'boolean',
        'in_cellphonecontact' => 'boolean',
        'in_sliderprincipal' => 'boolean',
        'dollar_rate' => 'decimal:2', // <--- Cast para decimales
    ];
}