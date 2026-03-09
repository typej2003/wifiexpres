<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MikrotikSocket extends Controller
{
    private function getSocketClient()
    {
        $libPath = "/var/www/vhosts/wifiexpres.com/httpdocs/sistema/app/Libraries/ElephantIO/src/";

        require_once $libPath . 'Exception/SocketException.php';
        require_once $libPath . 'Exception/ServerConnectionFailureException.php';
        require_once $libPath . 'Exception/MalformedUrlException.php';
        require_once $libPath . 'StringableInterface.php';
        require_once $libPath . 'Engine/EngineInterface.php';
        require_once $libPath . 'Engine/SocketInterface.php';
        require_once $libPath . 'Stream/StreamInterface.php';
        require_once $libPath . 'Util.php';
        require_once $libPath . 'SequenceReader.php';
        require_once $libPath . 'SocketUrl.php';
        require_once $libPath . 'Yeast.php';
        require_once $libPath . 'Engine/Store.php'; 
        require_once $libPath . 'Engine/Option.php';
        require_once $libPath . 'Engine/Packet.php';
        require_once $libPath . 'Engine/Session.php';
        require_once $libPath . 'Engine/Argument.php';
        require_once $libPath . 'Engine/Transport.php';
        require_once $libPath . 'Engine/Transport/Polling.php';
        require_once $libPath . 'Engine/SocketIO.php'; 
        require_once $libPath . 'Engine/SocketIO/Version1X.php';
        require_once $libPath . 'Engine/SocketIO/Version2X.php'; 
        require_once $libPath . 'Parser/Polling/Decoder.php';
        require_once $libPath . 'Parser/Polling/Encoder.php';
        require_once $libPath . 'Stream/Stream.php';
        require_once $libPath . 'Stream/SocketStream.php';
        require_once $libPath . 'Client.php';

        $url = 'http://127.0.0.1:3000'; 
        $options = ['version' => 2, 'transport' => 'polling'];
        
        $engine = new \ElephantIO\Engine\SocketIO\Version2X($url, $options);
        return new \ElephantIO\Client($engine);
    }

    public function enviarPeticionRecursos(Request $request)
    {
        try {
            $comando = $request->input('comando', 'RECURSOS');
            $mac = $request->input('mac'); // Capturamos la MAC

            $client = $this->getSocketClient();
            $client->connect();
            // Emitimos comando y mac juntos
            $client->emit('enviar-comando-mt', [
                'comando' => $comando,
                'mac' => $mac
            ]);
            usleep(200000); 
            $client->disconnect();

            return response()->json(['status' => 'success', 'mensaje' => 'Enviado']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }
}