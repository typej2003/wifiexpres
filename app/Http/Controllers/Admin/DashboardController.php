<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected $listeners = ['prueba' => 'prueba'];
    
    public function __invoke(Request $request)
    {
        return view('admin.dashboard');
    }

    public function prueba($postId=0)
    {
        dd('prueba');
        $this->dispatchBrowserEvent('sendCategories', ['categories' => $this->categories, 'message' => 'variables enviadas satisfactoriamente!']);
    }
}
