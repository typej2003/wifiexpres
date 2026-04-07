<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Importante para manejar fechas

class UserMikrotik extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'mikrotik_id',
        'server',
        'name',
        'password',
        'full_name',
        'gender',    
        'birthday',  
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

    // Accessor para obtener la edad actual basada en birthday
    public function getAgeAttribute()
    {
        if (!$this->birthday) return null;
        return Carbon::parse($this->birthday)->age;
    }

    public function router()
    {
        return $this->belongsTo(Router::class, 'router_id');
    }
}