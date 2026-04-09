<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Mail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    const ROLE_ROOT = 'root';
    const ROLE_ADMIN = 'admin';
    const ROLE_LIDERNEGOCIO = 'lidernegocio';
    const ROLE_VENDEDOR = 'vendedor';
    const ROLE_CLIENTE = 'cliente';
    const ROLE_ALIADO = 'aliado';
    const ROLE_USER = 'user';

    protected $fillable = [
        'identificationNac', 'identificationNumber', 'names', 'surnames', 'name',
        'email', 'password', 'avatar', 'role', 'external_id', 'external_auth', 'active',
    ];

    protected $hidden = [
        'password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && Storage::disk('avatars')->exists($this->avatar)) {
            return Storage::disk('avatars')->url($this->avatar);
        }
        return asset('noimage.png');
    }

    // --- ROLES ---
    public function isAdmin() { return $this->role === self::ROLE_ADMIN; }
    public function isRoot() { return $this->role === self::ROLE_ROOT; }
    public function isAliado() { return $this->role === self::ROLE_ALIADO; }

    // --- RELACIONES HOTSPOT ---
    public function routers() { return $this->hasMany(Router::class, 'user_id'); }
    
    public function tickets() { 
        return $this->hasManyThrough(Ticket::class, Router::class, 'user_id', 'router_id'); 
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_user')
                    ->using(PackageUser::class)
                    ->withPivot('start_date', 'end_date', 'status', 'allowed_routers', 'router_quantity')
                    ->withTimestamps();
    }

    public function hasActivePlan()
    {
        return $this->packages()
                    ->wherePivot('status', 'active')
                    ->wherePivot('end_date', '>=', now())
                    ->exists();
    }

    // --- OTRAS RELACIONES ---
    public function datosbasicos() { return $this->hasOne(PersonalInformation::class, 'user_id', 'id'); }
    public function comercios() { return $this->hasMany(Comercio::class); }
    public function sales() { return $this->hasMany(Sale::class); }
}