<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Auth;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id", "package_id", "ip", "macAddress", "dns", "api_port", "identity", "admin", 
        "password", "location", "is_active", "status", "login_source",
        "comercio_nombre", "comercio_logo", "comercio_banner", "hotspot_url",
        "is_store", "store", "address", "is_promotion", "is_trial", "path_imgs", "hotspot_version_id"
    ];

    protected $casts = [
        "path_imgs" => "array", 
        "is_active" => "boolean",
        "is_promotion" => "boolean",
        "is_store" => "boolean",
        "is_trial" => "boolean",
    ];

    protected $appends = ['banner_router'];

    public function getPathImgsAttribute($value)
    {
        if (is_null($value) || $value === "") return [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $value;
    }

    public function getBannerRouterAttribute()
    {
        // Buscamos directamente en el disco 'bannerrouter'
        if ($this->comercio_banner && \Storage::disk('bannerrouter')->exists($this->comercio_banner)) {
            return \Storage::disk('bannerrouter')->url($this->comercio_banner);
        }
        return asset('noimage.png');
    }

    // --- RELACIONES ---

    public function user() { return $this->belongsTo(User::class, "user_id"); }

    public function package() { return $this->belongsTo(Package::class, "package_id"); }

    public function hotspotVersion() { return $this->belongsTo(HotspotVersion::class, "hotspot_version_id"); }

    // --- LÓGICA DE VALIDACIÓN ---

    /**
     * Verifica si el usuario puede activar un router adicional.
     */
    public static function canActivate($userId)
    {
        $user = User::find($userId);
        if (!$user) return false;

        // Sumamos el límite de routers de todos sus planes activos (valor congelado en la tabla pivote)
        $limitTotal = $user->packages()
            ->wherePivot('status', 'active')
            ->wherePivot('end_date', '>=', now())
            ->sum('package_user.allowed_routers');

        // Contamos cuántos routers tiene actualmente con is_active = true
        $activeCount = self::where('user_id', $userId)
            ->where('is_active', true)
            ->count();

        return $activeCount < $limitTotal;
    }

    // --- SINCRONIZACIÓN MIKROTIK ---

    public function syncToMikrotik()
    {
        try {
            $port = (int) ($this->api_port ?? 8728);
            $client = new Client([
                "host"    => $this->ip,
                "user"    => $this->admin,
                "pass"    => $this->password,
                "port"    => $port,
                "timeout" => 5
            ]);

            /**
             * IMPORTANTE: El portal solo se habilita si:
             * 1. El router está marcado como is_active (dentro del límite del plan)
             * 2. El status general es Habilitado u online.
             */
            $isAuthorized = ($this->is_active && ($this->status === "Habilitado" || $this->status === "online"));
            
            $html = $isAuthorized ? $this->login_source : "<h1>Portal Deshabilitado</h1><p>Por favor, verifique su plan de suscripción en el panel de aliado.</p>";

            $client->query((new Query("/file/set"))
                ->equal(".id", "hotspot/login.html")
                ->equal("contents", $html))->read();
            
            return true;
        } catch (\Exception $e) { 
            return "Error: " . $e->getMessage(); 
        }
    }

    public function hotspotSetting() {
        return $this->hasOne(HotspotSetting::class, 'router_id');
    }
}