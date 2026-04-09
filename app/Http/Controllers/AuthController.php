<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DatosBasicos;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use DB;
use Carbon\Carbon;
use Mail;
use Exception; // Importante para capturar errores

class AuthController extends Controller
{
    public $messages = [
        'required' => 'Campo requerido.',
    ];

    // ... (Mantén tus métodos acceder, autenticar, registro, etc. exactamente igual) ...

    public function loginAliado(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            // Validamos contraseña y que sea ROL_ALIADO
            if (!$user || !Hash::check($request->password, $user->password) || !$user->isAliado()) {
                return response()->json([
                    'message' => 'Credenciales inválidas o no es un Aliado autorizado.'
                ], 401);
            }

            // --- ESTA LÍNEA ES LA QUE SUELE DAR EL 500 ---
            // Si el modelo User NO tiene "use HasApiTokens", aquí explota.
            $token = $user->createToken('hablador-token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        } catch (Exception $e) {
            // Si algo falla, esto convertirá el Error 500 en un JSON que la App puede leer
            return response()->json([
                'message' => 'Error en el servidor: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}