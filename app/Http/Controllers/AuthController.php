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

class AuthController extends Controller
{
    
    public $messages = [
        'required'  => 'Campo requerido.',
    ];

   //Muestra la vista de acceso o login
    public function acceder()
    {
        return view('auth.login');
    }

    //Autentica al usuario
    public function autenticar(Request $request)
    {
        //Validación de datos (incluyendo la de activo)
        if($request->post('cellphone')){
            $credentials = $request->validate([
                'cellphonecode' => ['required'],
                'cellphone' => ['required'],
                'password' => ['required']
            ]);    
        }else{
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required']
            ], $this->messages);    
        }

        //Si es correcto, inicio sesión y login
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $role = auth()->user()->role;
        
            return match ($role) {
                'admin'    => redirect()->route('admin.index'),
                // 'admin'    => redirect()->intended('admin/dashboard')->with('success','Bienvenido al panel de Administración'),
                'aliado' => redirect()->route('aliado.index'),
                'cliente' => redirect()->intended('dashboard-cliente')->with('success','Bienvenido al panel de Administración'),
                default    => redirect()->route('cliente.index'),
            };
            
            
        }

        //Si no, muestro mensaje de error
        return back()->withErrors([
            'email' => 'El email no está registrado.',
            'showLogin' => 'SI',
        ])->withInput(['showLogin' => 'SI']);

    }

    //Muestra la vista de registro
    public function registro()
    {
        return view('auth.registro');
    }

    //Registra al usuario
    public function registrarse(Request $request)
    {
        //Validación y recopilación de datos
        //$validatedData = $request->validate([
        
        $validatedData = Validator::make($request->all(), [
            'identificationNac' => 'required|not_in:0',
            'identificationNumber' => 'required',
			'name' => 'required',
            'names' => 'required',
            'surnames' => 'required',
			'email' => 'required|email|unique:users',
			'password' => 'required|confirmed',
            'cellphonecode' => 'required|not_in:0',
            'cellphone' => 'required',
            'role' => 'required',
		//], $this->messages);
        ], $this->messages)->validate();

		$validatedData['password'] = bcrypt($validatedData['password']);
        
        $user = User::create($validatedData);

        DatosBasicos::create([
            'user_id' => $user->id,
            'cellphonecode' => $request->post('cellphonecode'),
            'cellphone' => $request->post('cellphone'),
        ]);

        $cart = new CartController;
        $contenido = $cart->contenido();

        foreach($contenido as $elemento)
        {   
            $comercio_id = $elemento->attributes->comercio_id;
        }

        Client::create([
            'user_id' => $user->id,
            'comercio_id' => 1,
        ]);
        
        //Login de usuario
        Auth::login($user);

        // return back()->withErrors([
        //     'email' => 'El email no está registrado.',
        //     'showRegister' => 'SI',
        // ])->withInput(['showRegister' => 'SI']);

        return back()->withErrors([
            'email' => 'Email is invalid!',
            'showRegister' => 'SI',
            ])->withInput(['showRegister' => 'SI']);

        //Redirección
        // return redirect("admin")->with('success','Te has registrado correctamente. Bienvenido');
    }

    //Salir del panel de administración
    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('acceder')->with('success','Hasta pronto');
    }

    //Muestro el formulario para introducir el email
    public function email()
    {
        return view('auth.email');
    }

    //Genero y envío el enlace para restaurar la clave
    public function enlace(Request $request)
    {
        //Validación de email
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        //Generación de token y almacenado en la tabla password_resets
        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        //Envío de email al usuario
        Mail::send('email.email', ['token' => $token, 'correo' => $request->email ], function($message) use($request){
            $message->to($request->email);
            $message->subject('Solicitud de Cambio de Contraseña');
        });

        //Retorno
        return redirect('login')->with('success','Te hemos enviado un email a '.$request->email.' con un enlace para realizar el cambio de contraseña.');
    }

    public function enlacepedido(Request $request)
    {

        $token = Str::random(64);
        //Envío de email al usuario
        Mail::send('email.email-pedido', ['token' => $token], function($message) use($request){
            $message->to( auth()->user()->email );
            $message->subject('Solicitud de Cambio de Contraseña');
        });

        //Retorno
        return redirect('login')->with('success','Te hemos enviado un email a '.$request->email.' con un enlace para realizar el cambio de contraseña.');
    }


    //Muestro el formulario para cambiar la clave
    public function clave( $token  )
    {
        return view('auth.clave', [ 'token' => $token ] );
    }

    public function clave2( $token, $correo  )
    {
        return view('auth.clave', [ 'token' => $token, 'correo' => $correo ] );
    }

    //cambio la clave
    public function cambiar(Request $request)
    {
        //Valido datos
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|min:8|max:16|confirmed',
            'password_confirmation' => 'required'
        ]);

        /*
        //Compruebo token válido
        $comprobarToken = DB::table('password_resets')->where(['email' => $request->email, 'token' => $request->token])->first();
        if(!$comprobarToken){
            //return back()->withInput()->with('danger','El enlace no es válido');
            return redirect('/login')->with('alert_error','El Enlace no es valido');
        }
*/

        //Actualizo password
        User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        //Borro token para que no se pueda volver a usar
        DB::table('password_resets')->where(['email'=> $request->email])->delete();

        //Retorno
        return redirect('/login')->with('success','La contraseña se ha cambiado correctamente.');
    }

    
    public function loginAliado(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Validamos contraseña y que sea ROL_ALIADO
        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->isAliado()) {
            return response()->json(['message' => 'Credenciales inválidas o no es un Aliado autorizado.'], 401);
        }

        // Creamos el token de acceso
        $token = $user->createToken('hablador-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }
}
