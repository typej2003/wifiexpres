<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationApp extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'notification_apps';

    /**
     * Los atributos que son asignables.
     *
     * @var array
     */
    protected $fillable = [
        'app_name',
        'title',
        'body',
        'device_id',
    ];

    /**
     * Accessor opcional para mostrar nombres de apps más amigables.
     * Esto convierte "com.whatsapp" en "WhatsApp" en la vista.
     */
    public function getFriendlyAppNameAttribute()
    {
        $names = [
            'com.whatsapp' => 'WhatsApp',
            'com.instagram.android' => 'Instagram',
            'com.facebook.orca' => 'Messenger',
            'com.facebook.katana' => 'Facebook',
            'com.google.android.gm' => 'Gmail',
            'com.android.settings' => 'Sistema',
        ];

        return $names[$this->app_name] ?? $this->app_name;
    }
}