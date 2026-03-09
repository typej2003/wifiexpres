<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMikrotik extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'mikrotik_id',
        'server',
        'name',
        'password',
        'full_name', // Nuevo
        'gender',    // Nuevo
        'birthday',  // Nuevo
        'address',
        'macaddress',
        'cellphone',
        'cellphonecode',
        'profile',
        'routes',
        'email',
        'limitUptime',
        'limitBytesIn',
        'limitBytesOut',
        'limitBytesTotal',
        'uptime',
        'bytesIn',
        'packetsIn',
        'bytesOut',
        'packetsOut',
        'active',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class, 'router_id');
    }
}