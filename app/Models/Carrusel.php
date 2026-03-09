<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Carrusel extends Model
{
    use HasFactory;

    protected $fillable = ['bannerside', 'device', 'title', 'avatar', 'order', 'active'];

    // En el modelo, no necesitas cambiar el accessor, 
    // ya que la lógica de qué imagen traer la haremos en la consulta del Welcome.

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && Storage::disk('avatarscarrusel')->exists($this->avatar)) {
            return Storage::disk('avatarscarrusel')->url($this->avatar);
        }

        return asset('noimage.png');
    }
}
