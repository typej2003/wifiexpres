<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PackageUser extends Pivot
{
    protected $table = 'package_user';

    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'allowed_routers',
        'router_quantity',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'allowed_routers' => 'integer',
        'router_quantity' => 'integer',
    ];
}