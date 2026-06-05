<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TicketLog extends Model
{
    protected $fillable = [
        'router_id', 
        'username', 
        'mac_address', // IP del usuario según aclaratoria
        'user_ip', 
        'disconnected_at', 
        'duration_seconds'
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Relación con el usuario de MikroTik basado en el nombre de usuario.
     */
    public function userMikrotik(): BelongsTo
    {
        // Vinculamos por username -> name
        // Agregamos el filtro de router_id para asegurar que el log pertenezca al usuario del mismo equipo
        return $this->belongsTo(UserMikrotik::class, 'username', 'name')
                    ->whereColumn('user_mikrotiks.router_id', 'ticket_logs.router_id');
    }

    /**
     * Obtiene la ubicación física basada en el segmento de IP 
     * que reside en el campo 'mac_address'.
     */
    public function getUbicacionFisicaAttribute(): string
    {
        // Usamos mac_address que es donde guardas la IP
        $ip = $this->mac_address;

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip ?? 'Sin IP';
        }

        // Extraemos los primeros 3 octetos (ej: 10.0.0.)
        $parts = explode('.', $ip);
        if (count($parts) < 3) return $ip;
        
        $segmento = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.';

        $mapeo = AntennaMapping::where('router_id', $this->router_id)
            ->where('ip_address', 'LIKE', $segmento . '%')
            ->first();

        return $mapeo ? $mapeo->location_name : $ip;
    }

    public function getDuracionFormateadaAttribute(): string
    {
        if (!$this->duration_seconds) return 'En línea';
        $horas = floor($this->duration_seconds / 3600);
        $minutos = floor(($this->duration_seconds / 60) % 60);
        $segundos = $this->duration_seconds % 60;
        return $horas > 0 ? "{$horas}h {$minutos}m" : "{$minutos}m {$segundos}s";
    }

    public function getAliadoNombreAttribute(): string
    {
        return $this->router->user->name ?? 'Sistema';
    }
}