<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnviarDatos;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ApiProcessPaymentController;
use App\Http\Controllers\Api\MikrotikPasarelaController;
use App\Http\Livewire\Pagomovil\ListPagomovil;
use App\Http\Controllers\LoginMikrotik;
use App\Http\Livewire\Mikrotik\Hotspot\CreateUser;
use App\Http\Livewire\Mikrotik\Hotspot\ListPlanes;
use App\Http\Livewire\Admin\Users\ListUsers;
use App\Http\Controllers\Api\MikrotikController;
use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\MikrotikSocket; 
use App\Http\Controllers\Api\V2\UserController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('datos', [ApiController::class, 'recibirDatosApi']);
Route::apiResource('enviardatos', EnviarDatos::class);
Route::get('createUserSession', [CreateUser::class, 'addNew']);
Route::post('listPlanes', [ListPlanes::class, 'listPlanes']);
Route::post('saveComment', [ListPlanes::class, 'saveComment']);
Route::post('registerUser', [ListUsers::class, 'registerUser']);
Route::post('hotspot-login', [CreateUser::class, 'login1']);
Route::apiResource('apiuser', ApiController::class);
Route::post('apiprocesspayment', [ApiProcessPaymentController::class, 'apiprocesspayment']);
Route::post('mikrotikPasarela', [MikrotikPasarelaController::class, 'mikrotikPasarela']);
Route::apiResource('processpayment', ApiProcessPaymentController::class);
Route::post('/capturarPagomovil', [ListPagomovil::class, 'capturarPagomovil']);
Route::get('/accesoMikrotik', [LoginMikrotik::class, 'accesoMikrotik']);
Route::get('/log-connection', [MikrotikController::class, 'logConnection']);

/** * RUTAS V1 - PORTAL CAUTIVO TRADICIONAL 
 */
Route::prefix('v1')->group(function () {
    Route::get('/get-plans', [HotspotController::class, 'getPlans']);
    Route::post('/users/add', [HotspotController::class, 'addUser']);
});

/** * RUTAS V2 - NUEVO FLUJO (BRIDGE / SOCKET) 
 */
Route::prefix('v2')->group(function () {
    Route::post('/users/pre-add', [UserController::class, 'preAdd']);
    Route::post('/users/activate', [UserController::class, 'activate']);
    Route::post('/users/trial', [UserController::class, 'trialAdd']);
    
    Route::get('/router-info', [UserController::class, 'getRouterInfo']);
    Route::post('/users/trial-lead', [UserController::class, 'trialLead']);

    // Testeo del Bridge
    Route::post('/test-recursos', [MikrotikSocket::class, 'enviarPeticionRecursos']);
    Route::get('/test-socket', [MikrotikSocket::class, 'testSocket']);
    Route::post('/hotspot/user-add', [MikrotikSocket::class, 'crearUsuarioHotspot']);
});

/** * RUTAS V3 - MARKETING
 */
Route::prefix('v3')->group(function () {
    Route::post('/leads/add', [HotspotController::class, 'v3RegisterLead']);
});

// MANEJO GLOBAL DE CORS (Evita el "Error de comunicación")
Route::options('{any}', function() {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
})->where('any', '.*');