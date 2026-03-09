<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ElephantIO\Client;

class HotspotController extends Controller
{
    public function activarUsuario(Request $request)
    {
        // Datos del usuario que viene del login.html
        $username = $request->input('user');
        $password = $request->input('password');

        // El comando que queremos que ejecute el MikroTik
        $comandoMikrotik = "/ip hotspot user add name=$username password=$password comment='Creado desde Laravel'";

        try {
            // Conectamos con el servidor Node.js (el puente)
            $url = 'http://TU_IP_SERVIDOR_REMOTO:3000';
            $client = Client::create($url);
            $client->initialize();

            // Enviamos el comando al socket
            // Nota: El evento debe coincidir con el que pusimos en Node.js
            $client->emit('enviar-comando-mt', [
                'mtId' => 'router01', // El ID que le diste a tu MikroTik
                'comando' => $comandoMikrotik
            ]);

            $client->close();

            return response()->json(['status' => 'success', 'message' => 'Orden enviada al MikroTik']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}