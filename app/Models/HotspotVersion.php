<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotspotVersion extends Model
{
    use HasFactory;

    protected $table = 'hotspot_versions';

    protected $fillable = [
        'name', 
        'description', 
        'code'
    ];

    public function packages()
    {
        return $this->hasMany(Package::class, 'hotspot_version_id');
    }
}