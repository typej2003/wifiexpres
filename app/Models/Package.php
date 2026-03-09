<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'hotspot_version_id', 'service_type', 'cost', 'duration_months', 
        'limit_routers', 'is_offer', 'offer_cost', 'is_active', 'description', 
        'commission_aliado', 'commission_system', 'is_visible'
    ];

    public function hotspotVersion() {
        return $this->belongsTo(HotspotVersion::class, 'hotspot_version_id');
    }

    public function routers() {
        return $this->hasMany(Router::class, 'package_id');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'package_user')
                    ->using(PackageUser::class)
                    ->withPivot('status', 'start_date', 'end_date', 'allowed_routers', 'router_quantity')
                    ->withTimestamps();
    }
}