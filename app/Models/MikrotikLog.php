<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikrotikLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 
        'time_mikrotik', 
        'category', 
        'type', 
        'message', 
        'hash'
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}