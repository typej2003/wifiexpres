<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotspotSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'portal_name',
        'has_shop',
        'shop_name',
        'shop_location',
        'show_carousel',
        'carousel_images'
    ];

    protected $casts = [
        'carousel_images' => 'array',
        'has_shop' => 'boolean',
        'show_carousel' => 'boolean',
    ];

    public function router() {
        return $this->belongsTo(Router::class);
    }
}