<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'logout', // exclude exact URL
        'api/mikrotikPasarela',
        'api/v2/*', // Esto permitirá que las rutas de la API no requieran token CSRF
        'api/SypagoRequestSms',
        'api/payments/sypago-confirm',
    ];
    
}
