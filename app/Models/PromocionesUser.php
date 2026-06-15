<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromocionesUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'name',
        'phone',
        'email',
        'deliveryMethod',
        'enviado',
    ];

    protected $casts = [
        'enviado' => 'boolean',
    ];

    /**
     * Obtener el aliado que gestiona esta promoción.
     */
    public function aliado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtener la campaña o promoción asociada.
     */
    public function campaign()
    {
        return $this->belongsTo(AdvertisingCampaign::class, 'campaign_id');
    }

    /**
     * Scope para filtrar solo las enviadas.
     */
    public function scopeEnviadas($query)
    {
        return $query->where('enviado', true);
    }
}
