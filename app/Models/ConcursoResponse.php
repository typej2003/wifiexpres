<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcursoResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'concurso_id',
        'user_mikrotik_id',
        'mac_address',
        'router_identity',
        'answer',
        'concurso_name',
        'concurso_etapa',
        'concurso_description',
        'concurso_target_gender',
        'concurso_age_range_id',
        'concurso_media_type',
        'concurso_media_path',
        'concurso_question_text',
        'concurso_question_type',
        'concurso_options',
    ];

    protected $casts = [
        'concurso_options' => 'array',
    ];

    /**
     * Obtener la concurso a la que pertenece esta respuesta.
     */
    public function concurso()
    {
        return $this->belongsTo(AdvertisingConcurso::class, 'concurso_id');
    }

    /**
     * Obtener el usuario del hotspot que proporcionó la respuesta.
     */
    public function userMikrotik()
    {
        return $this->belongsTo(UserMikrotik::class, 'user_mikrotik_id');
    }
}
