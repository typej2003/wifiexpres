<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Admin\Users\ListUsers;

use App\Http\Livewire\Mikrotik\Router\ListRouters;
use App\Http\Livewire\Mikrotik\ViewIntegration;
use App\Http\Livewire\Mikrotik\Router\ConfigureRouter;
use App\Http\Livewire\Mikrotik\Router\RouterUsers;
use App\Http\Livewire\Mikrotik\Router\RouterHotspots;
use App\Http\Livewire\Mikrotik\Router\RouterPlanes;
use App\Http\Livewire\Mikrotik\Router\RouterPlanesAfiliado;
use App\Http\Livewire\Mikrotik\User\TimeOut;
use App\Http\Livewire\Mikrotik\Router\HotspotUsers;

use App\Http\Livewire\Mikrotik\ListUsersMikrotik;
use App\Http\Livewire\Mikrotik\Hotspot\ListHotspot;
use App\Http\Livewire\Mikrotik\Hotspot\CrearTicket;
use App\Http\Livewire\Mikrotik\Hotspot\CrearTicketPhone;
use App\Http\Livewire\Mikrotik\Hotspot\CreateUser;
use App\Http\Livewire\Mikrotik\Hotspot\ListUsersAliados;
use App\Http\Controllers\LoginMikrotik;
use App\Http\Livewire\Mikrotik\Hotspot\ListPlanes;
use App\Http\Controllers\Api\MikrotikPasarelaController;
use App\Http\Livewire\Mikrotik\Hotspot\ListEventos;
use App\Http\Livewire\Mikrotik\Herramientas\Diagnostico;
use App\Http\Livewire\Mikrotik\Herramientas\VisorLogs;
use App\Http\Livewire\Mikrotik\Herramientas\ConfigurarRemoto;
use App\Http\Livewire\Mikrotik\Herramientas\ConfRemotoLite;
use App\Http\Livewire\Mikrotik\Herramientas\ConfDetallada;

use Illuminate\Support\Facades\Response;

Route::get('users', ListUsers::class)->name('users.index');

Route::get('/listRouters', ListRouters::class)->name('routers.index')->middleware('auth');

Route::get('/viewintegration', ViewIntegration::class)->name('viewintegration')->middleware('auth');

Route::get('/configureRouter/{router_id}', ConfigureRouter::class)->name('configureRouter')->middleware('auth');

Route::get('/routerUsers/{router_id}', RouterUsers::class)->name('routerUsers')->middleware('auth');

Route::get('/routerHotspots/{router_id}', RouterHotspots::class)->name('routerHotspots')->middleware('auth');

Route::get('/routerPlanes/{router_id}', RouterPlanes::class)->name('routerPlanes')->middleware('auth');

Route::get('/routerPlanesAfiliado/{router_id}', RouterPlanesAfiliado::class)->name('routerPlanesAfiliado')->middleware('auth');

Route::get('/usersMikrotik', ListUsersMikrotik::class)->name('usersMikrotik')->middleware('auth');

Route::get('/ListHotspot', ListHotspot::class)->name('ListHotspot')->middleware('auth');

Route::get('/loginMikrotik', [LoginMikrotik::class, 'loginMikrotik'])->name('loginMikrotik')->middleware('auth');

Route::get('/crearTicket', CrearTicket::class)->name('crearTicket')->middleware('auth');

Route::get('/crearTicketPhone', CrearTicketPhone::class)->name('crearTicketPhone')->middleware('auth');

Route::get('/createUser', CreateUser::class)->name('createUser')->middleware('auth');

Route::get('/listUsersAliados', ListUsersAliados::class)->name('listUsersAliados')->middleware('auth');

Route::get('/listPlanesHotspot', ListPlanes::class)->name('listPlanesHotspot')->middleware('auth');

Route::get('/listEventos', ListEventos::class)->name('listEventos')->middleware('auth');

Route::get('/timeOut', TimeOut::class)->name('timeOut')->middleware('auth');

Route::get('/hotspot-users/{nrorouter}/{name}', HotspotUsers::class)->name('hotspot-users')->middleware('auth');



// Operaciones para la pasarela del mikrotik
Route::get('/pagosatisfactorioMikrotik/{id}', function ( $id ) {

    $newUser = [];
    $newUser = '';
    $id_suc = $id;
    //$pasarela = Pasarela();
    $result = new MikrotikPasarelaController();
    $newUser = $result->registrarReferenciaMikrotik($id);

    //return view('externalviews.ver', ['newUser' => $newUser, ] );
    
    if ($newUser['status'] == true)
    {
        return view('pagosatisfactorioMikrotik', ['user' => $newUser['user'], 'status'  => 'exito'] );
    }else{
        return view('pagosatisfactorioMikrotik', ['user' => 'fallo', 'status'  => 'exito'] );
    }
        
    
});

Route::get('/pruebapagosatisfactorioMikrotik', function () {
    
    $user = '04165800403';

    $password = '52479051';

    $host = 'typej.ddns.net';



    $datos = true;

    return view('externalviews.ver', ['user' => $user, 'password'  =>  $password, 'newUser'  =>  $newUser] );
});

Route::get('/google', function() {
    return redirect()->away('https://www.google.com');
});

Route::get('/enviarLoginNo', function() {
    $host = 'typej.ddns.net';
    $user = '04165800403';
    $pass = '52479051';
    $url = 'http://'.$host .'/login?username='. $user . '&password='.$pass;
    return redirect()->away($url);
});

Route::get('/enviarValores', function () {

    $host = 'typej.ddns.net';
    $user = '04165800403';
    $pass = '52479051';

    return Response::stream(
        function () {
            while (true) {
                echo "data: " . json_encode(['message' => 'Evento recibido: ' . date('Y-m-d H:i:s')]) . "\n\n";
                flush(); // Asegura que los datos se envíen inmediatamente
                sleep(5); // Espera 5 segundos antes de enviar el siguiente evento
            }
        },
        200,
        [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Para servidores como Nginx
        ]
    );
});

Route::get('diagnostico', Diagnostico::class)->name('herramientas.diagnostico');

Route::group(['middleware' => ['auth']], function () {
    
    // ... tus otras rutas ...

    // Nueva ruta para el Visor de Logs Amigable
    Route::get('/mikrotik/herramientas/logs', VisorLogs::class)->name('mikrotik.logs');

    Route::get('/mikrotik/herramientas/configurarremoto', ConfigurarRemoto::class)->name('mikrotik.remoto');

    Route::get('/mikrotik/herramientas/configurarremotolite', ConfRemotoLite::class)->name('mikrotik.remotolite');

    Route::get('/mikrotik/herramientas/confdetallada', ConfDetallada::class)->name('mikrotik.confdetallada');

});