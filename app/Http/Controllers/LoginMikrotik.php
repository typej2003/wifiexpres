<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use RouterOS\Client;
use RouterOS\Query;

class LoginMikrotik extends Controller
{
    public function loginMikrotik()
    {
        return view('loginMikrotik');
    }

    // Función para crear el usuario en el Hotspot
    public function crearUsuarioHotspot($username, $password, $profile = 'default')
    {
        // Conexión al dispositivo MikroTik
        $datos = [
            'host' => '192.168.2.1',
            'user' => 'admin',
            'pass' => 'admin123'
        ];
        // Crear la consulta para añadir el usuario
        $query = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $username)
            ->equal('password', $password)
            ->equal('profile', $profile); // Asigna un perfil, 'default' por defecto
        // Ejecutar la consulta
        $client->query($query)->read();
        return "Usuario {$username} creado con éxito.";
    }

    // Función para autenticar al usuario y darle acceso
    public function iniciarSesionHotspot($username, $password)
    {
        // Conexión al dispositivo MikroTik
        $datos = [
            'host' => '192.168.2.1',
            'user' => 'admin',
            'pass' => 'admin123'
        ];

        $client = new Client($datos);
        
        // Crear la consulta para iniciar sesión
        $query = (new Query('/login'))
            ->equal('name', $username)
            ->equal('password', $password);

        try {
            // Ejecutar la consulta de login
            $response = $client->query($query)->read();
            // Verificar la respuesta para confirmar el éxito
            if (!empty($response) && isset($response['!trap'])) 
            {
                return 'Error de autenticación: ' . $response['!trap'][0]['message'];
            }
            return "Inicio de sesión exitoso para {$username}.";
        } catch (Exception $e) {
            return 'Error al conectar con MikroTik: ' . $e->getMessage();
        }
    }

    public function accesoMikrotik()
    {
        $username = 'Jose';
        $password = '123';
        // $this->crearUsuarioHotspot($username, $password, $profile = 'default');
        $this->iniciarSesionHotspot($username, $password);
        
        $data = ['valor' => "Operacion exitosa a traves de api!", 'success' => true ];
        return response()->json($data);
    }
}
