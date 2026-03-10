<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'router_id',
        'name',
        'mikrotik_profile',
        'price',
        'session_timeout',
        'idle_timeout',
        'keepalive_timeout',
        'status_autorefresh',
        'add_mac_cookie',      // Nuevo
        'mac_cookie_timeout',   // Nuevo
        'shared_users',
        'rate_limit',
        'is_active'
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'shared_users' => 'integer',
        'is_active' => 'boolean',
        'add_mac_cookie' => 'boolean', // Nuevo
    ];

    /**
     * Relación: Un plan pertenece a un Router.
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Scope para obtener solo planes activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}