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
use App\Http\Livewire\Mikrotik\Aliado\ListAdvertisingCampaign;
use App\Http\Livewire\Mikrotik\Aliado\ListAdvertisingConcursos;
use App\Http\Controllers\Api\V2\UserController;
use App\Models\NotificationApp;
use App\Models\HotspotVersion;
use App\Models\User;
use App\Models\Pagomovil;
use App\Models\Router;
use App\Models\Hablador;
use App\Models\Pantalla;

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
Route::get('sypago-status/{transactionId}', [SyPagoController::class, 'checkStatus']);

Route::apiResource('processpayment', ApiProcessPaymentController::class);
Route::post('/capturarPagomovil', [ListPagomovil::class, 'capturarPagomovil']);
Route::get('/accesoMikrotik', [LoginMikrotik::class, 'accesoMikrotik']);
Route::get('/log-connection', [MikrotikController::class, 'logConnection']);

/** * RUTAS V1 - MANTENIDAS POR COMPATIBILIDAD
 */
Route::prefix('v1')->group(function () {
    Route::get('/get-plans', [HotspotController::class, 'getPlans']);
    Route::get('/get-infoRouter', [HotspotController::class, 'getInfoRouter']);
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

    // Obtener campaña publicitaria por identidad del router
    Route::get('/get-campaign', [UserController::class, 'getActiveCampaignByIdentity']);

    // Guardar datos del portal (Standard o Campaña)
    Route::post('/save-portal-data', [ListAdvertisingCampaign::class, 'savePortalData']);

    // Guardar datos del portal para Concursos
    Route::post('/save-portal-data-concurso', [ListAdvertisingConcursos::class, 'savePortalDataConcurso']);
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
    // 1. Capturar y limpiar datos
    $email = trim($request->input('email'));
    $password = $request->input('password');

    // 2. Buscar usuario
    $user = User::where('email', $email)->first();

    // 3. Validación de credenciales
    if ($user && Hash::check($password, $user->password)) {
        
        // Verificamos el rol para restringir el acceso a la App
        if ($user->role !== 'aliado') {
            return response()->json(['message' => 'No autorizado: Rol ' . $user->role], 403);
        }

        try {
            // Generar Token Sanctum
            $token = $user->createToken('hablador-token')->plainTextToken;

            // --- NUEVO: Recuperar datos del Aliado ---
            
            // Traemos los habladores activos
            $habladores = Hablador::where('user_id', $user->id)
                ->where('activo', true)
                ->get();

            // Traemos todas las pantallas configuradas
            $pantallas = Pantalla::where('user_id', $user->id)->get();

            // Retornamos la respuesta completa para que Android la procese
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'habladores' => $habladores,
                'pantallas' => $pantallas
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    // Fallo de autenticación
    return response()->json(['message' => 'Credenciales incorrectas'], 401);
});

Route::middleware('auth:sanctum')->get('/get-habladores', function (Request $request) {
    $user = $request->user();

    $habladores = Hablador::where('user_id', $user->id)
        ->where('activo', true)
        ->get();

    // Traemos las pantallas vinculadas al aliado
    $pantallas = Pantalla::where('user_id', $user->id)->get();

    return response()->json([
        'habladores' => $habladores,
        'pantallas' => $pantallas
    ]);
});

Route::middleware('auth:sanctum')->post('/update-hablador-info', function (Request $request) {
    $request->validate([
        'id' => 'nullable|integer', // Recibimos el ID opcional desde la App
        'user_id' => 'required|integer',
        'slug_pantalla' => 'required|string',
        'hablador_id' => 'required|integer',
        'orientation' => 'required|string|in:portrait,landscape'
    ]);

    // LÓGICA CLAVE: Buscamos el registro por ID (prioritario) o por Slug
    $pantalla = Pantalla::where('id', $request->id)
        ->orWhere('slug_pantalla', $request->slug_pantalla)
        ->first();

    if ($pantalla) {
        // Si existe, actualizamos (esto permite cambiar el slug sin duplicar)
        $pantalla->update([
            'user_id' => $request->user_id,
            'slug_pantalla' => $request->slug_pantalla,
            'nombre' => $request->slug_pantalla, 
            'hablador_id' => $request->hablador_id,
            'orientation' => $request->orientation,
        ]);
    } else {
        // Si no existe ninguno de los dos, creamos uno nuevo
        $pantalla = Pantalla::create([
            'user_id' => $request->user_id,
            'slug_pantalla' => $request->slug_pantalla,
            'nombre' => $request->slug_pantalla, 
            'hablador_id' => $request->hablador_id,
            'orientation' => $request->orientation,
        ]);
    }

    return response()->json([
        'message' => 'Pantalla sincronizada correctamente',
        'status' => 'success',
        'data' => $pantalla // Devolvemos el objeto completo para que la App guarde el ID
    ]);
});

Route::middleware('auth:sanctum')->get('/get-assigned-hablador', function (Request $request) {
    $user = $request->user();
    $slug = $request->query('slug_pantalla');

    if (!$slug) {
        return response()->json(['message' => 'Slug no proporcionado'], 400);
    }

    // Buscamos la pantalla configurada para este Aliado
    $pantalla = Pantalla::where('user_id', $user->id)
        ->where('slug_pantalla', $slug)
        ->first();

    if ($pantalla && $pantalla->hablador_id) {
        $hablador = Hablador::find($pantalla->hablador_id);
        if ($hablador && $hablador->activo) {
            return response()->json($hablador);
        }
    }

    return response()->json(['message' => 'Sin hablador asignado'], 404);
});

//**** fin de habladores ****/

// *** Captura Notificaciones de Android para pruebas *** //
Route::post('/auth-capturanotificaciones', function (Request $request) {
    // 1. Capturar y limpiar datos
    $email = trim($request->input('email'));
    $password = $request->input('password');

    // 2. Buscar usuario
    $user = User::where('email', $email)->first();

    // 3. Validación de credenciales
    if ($user && Hash::check($password, $user->password)) {
        
        // Verificamos el rol para restringir el acceso a la App
        if ($user->role !== 'aliado') {
            return response()->json(['message' => 'No autorizado: Rol ' . $user->role], 403);
        }

        try {
            // Generar Token Sanctum
            $token = $user->createToken('pagomovil-token')->plainTextToken;

            // --- NUEVO: Recuperar datos del Aliado ---
            
            // Traemos los routers del aliado
            $routers = Router::where('user_id', $user->id)
                ->get();

            // Retornamos la respuesta completa para que Android la procese
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'routers' => $routers,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    // Fallo de autenticación
    return response()->json(['message' => 'Credenciales incorrectas'], 401);
});

Route::middleware('auth:sanctum')->get('/get-pagomovil', function (Request $request) {
     
    $identity = $request->input('identity');
    $pagomovil = Pagomovil::where('identity', $identity)
        ->get();

    return response()->json([
        'pagomovil' => $pagomovil
    ]);
});
// ** Fin de Captura de Notificaciones ** //