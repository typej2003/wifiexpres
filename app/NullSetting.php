<?php

namespace App;

use App\Models\Setting;

class NullSetting extends Setting
{
    protected $attributes = [
        'site_title' => 'WifiExpres',
        'site_name' => 'WifiExpres',
        'site_email' => 'wifiexpress1@gmail.com',
        'footer_text' => 'default footer text',
        'sidebar_collapse' => false,
    ];
}
