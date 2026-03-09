<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    public function dashboard()
    {
        $role = auth()->user()->role;
        
        return match ($role) {
            'admin'    => redirect()->route('admin.index'),
            'afiliado' => redirect()->route('afiliado.index'),
            'cliente' => redirect()->route('cliente.index'),
            default    => redirect()->route('cliente.index'),
        };
    }
}