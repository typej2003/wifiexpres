<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'router_id', 
        'type', 
        'reference_id', 
        'description', 
        'amount_usd', 
        'amount_bs', 
        'rate'
    ];

    /**
     * Relación con el usuario (Aliado)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el router donde se originó la venta
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}