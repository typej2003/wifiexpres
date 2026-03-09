<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Administrator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        if (auth()->check() && (auth()->user()->isAdmin() 
            || auth()->user()->isUser()
            || auth()->user()->isCliente()
            || auth()->user()->isAliado()
            || auth()->user()->isDelivery())) 
        {
            return $next($request);
        }
        
        abort(403);
    }

}

