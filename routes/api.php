<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
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
use App\Models\NotificationApp;
use App\Models\HotspotVersion;
use App\Models\User;
use App\Models\Hablador;

use App\Http\Controllers\Api\SyPagoController;

// MANEJO GLOBAL DE CORS
Route::options('{any}', function() {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
})->where('any', '.*');

Route::middleware(['cors'])->group(function () {
    
});

Route::get('/portal-download/{id}', function ($id) {
    $version = HotspotVersion::findOrFail($id);
    
    // Retornamos el código HTML puro
    return response($version->code, 200)
        ->header('Content-Type', 'text/plain'); 
});

Route::get('/hotspot-assets/{file}', function ($file) {
    // Si el archivo es un CSS, lo buscamos en public/css
    $subfolder = str_ends_with($file, '.css') ? 'css/' : '';
    $path = public_path($subfolder . $file);

    if (!file_exists($path)) return response()->json(['error' => 'No encontrado'], 404);

    return Response::file($path);
});

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
Route::post('sypagoRequestSms', [SyPagoController::class, 'requestSms']);
Route::post('sypago-confirm', [SyPagoController::class, 'confirmPayment']);

Route::apiResource('processpayment', ApiProcessPaymentController::class);
Route::post('/capturarPagomovil', [ListPagomovil::class, 'capturarPagomovil']);
Route::get('/accesoMikrotik', [LoginMikrotik::class, 'accesoMikrotik']);
Route::get('/log-connection', [MikrotikController::class, 'logConnection']);

/** * RUTAS V1 - MANTENIDAS POR COMPATIBILIDAD
 */
Route::prefix('v1')->group(function () {
    Route::get('/get-plans', [HotspotController::class, 'getPlans']);
    Route::post('/users/add', [HotspotController::class, 'addUser']);
});

/** * RUTAS V2 - NUEVO FLUJO (USER CONTROLLER ÚNICO)
 */
Route::prefix('v2')->group(function () {
    // Info y Planes (Desde DB)
    Route::get('/router-info', [UserController::class, 'getRouterInfo']);
    Route::get('/get-plans', [UserController::class, 'getPlans']);
    
    // Acciones de Usuario (Socket)
    Route::post('/users/pre-add', [UserController::class, 'preAdd']);
    Route::post('/users/pre-addSypago', [UserController::class, 'preAddSypago']);
    Route::post('/users/activate', [UserController::class, 'activate']);
    Route::post('/users/trial-lead', [UserController::class, 'trialLead']);

    // Testeo del Bridge
    Route::post('/test-recursos', [MikrotikSocket::class, 'enviarPeticionRecursos']);
    Route::get('/test-socket', [MikrotikSocket::class, 'testSocket']);
    Route::post('/hotspot/user-add', [MikrotikSocket::class, 'crearUsuarioHotspot']);

    Route::get('/users/check-status', [UserController::class, 'checkStatus']);

    Route::post('/free-connection', [UserController::class, 'freeConnection']);
});

/** * RUTAS V3 - MARKETING
 */
Route::prefix('v3')->group(function () {
    Route::post('/leads/add', [HotspotController::class, 'v3RegisterLead']);
});

//**** habladores ****/
Route::post('/save-notifications', function (Request $request) {
    // Validamos y guardamos
    NotificationApp::create([
        'app_name'  => $request->app,
        'title'     => $request->titulo,
        'body'      => $request->mensaje,
        'device_id' => $request->device_id ?? 'Android_Unknown'
    ]);

    return response()->json(['status' => 'success'], 201);
});

// Ruta de prueba para verificar qué está llegando al servidor
Route::post('/auth-sync-service', function (Request $request) {
    // 1. Capturar datos
    $email = trim($request->input('email'));
    $password = $request->input('password');

    // 2. Buscar usuario
    $user = User::where('email', $email)->first();

    // 3. Validación simple (como la teníamos cuando funcionó)
    // Usamos password_verify que es lo más directo
    if ($user && password_verify($password, $user->password)) {
        
        // Verificamos el rol para estar seguros
        if ($user->role !== 'aliado') {
            return response()->json(['message' => 'No autorizado: Rol ' . $user->role], 403);
        }

        try {
            // Aquí es donde daba el error antes. 
            // Si el modelo User ya tiene el trait HasApiTokens, esto funcionará.
            $token = $user->createToken('hablador-token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ], 200);

        } catch (\Exception $e) {
            // Si vuelve a fallar el createToken, aquí veremos por qué
            return response()->json(['message' => 'Error de Token: ' . $e->getMessage()], 500);
        }
    }

    // Si llega aquí es porque falló el usuario o la clave
    return response()->json(['message' => 'Credenciales incorrectas'], 401);
});

Route::middleware('auth:sanctum')->get('/get-habladores', function (Request $request) {
    // Laravel identifica al Aliado por el Token enviado desde la App
    $user = $request->user();

    // Traemos los habladores que pertenecen a este user_id
    $habladores = Hablador::where('user_id', $user->id)
        ->where('activo', true)
        ->get();

    return response()->json($habladores);
});

//**** fin de habladores ****/

