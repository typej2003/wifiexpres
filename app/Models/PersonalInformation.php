<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'cellphonecode',
        'cellphone',
        'msgcontact',
    ];

    protected $appends = [
        'telefono',
    ];

    public function getTelefonoAttribute()
    {
        return $this->cellphonecode . $this->cellphone;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
