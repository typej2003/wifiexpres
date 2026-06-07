<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisingConcurso extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'router_identity',
        'description',
        'target_gender',
        'age_range_id',
        'media_type',
        'media_path',
        'question_text',
        'question_type',
        'options',
        'active',
    ];

    protected $casts = [
        'options' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Obtener el aliado (usuario) dueño de la campaña.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el rango de edad configurado para la campaña.
     */
    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }

    /**
     * Obtener las respuestas registradas para esta campaña.
     */
    public function responses()
    {
        return $this->hasMany(CampaignResponse::class, 'campaign_id');
    }
}
