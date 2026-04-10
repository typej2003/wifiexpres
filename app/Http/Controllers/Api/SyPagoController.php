<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    public function processPayment(Request $request)
    {
        // 1. Validar los datos recibidos del formulario HTML
        $request->validate([
            'amount'      => 'required|numeric',
            'bank_code'   => 'required',
            'id_number'   => 'required',
            'phone_number'=> 'required',
            'otp'         => 'required',
            'username'    => 'required' // Para saber a quién activar el internet
        ]);

        // Configuración de SyPago (Recomiendo mover esto a tu archivo .env)
        $apiKey = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJmZXpQcl9HSWhIZ05jOVc1cU5Td2FIQXBRMVRqeUlqbWtpY0d5V1hHUjFzIn0.eyJleHAiOjE4NzAzNzIzNjksImlhdCI6MTc3NTc2NDM2OSwianRpIjoiYWFhYjM4MDMtZDc5NS00MjkxLWJkODgtMzBiY2IyNWExYTc0IiwiaXNzIjoiaHR0cHM6Ly9zeXBhZ28ubmV0OjgwODEvcmVhbG1zL3N5cGFnbyIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiIzMzQ0YTI0Ni0wMTIzLTQ3MWItODYwZi05MTNmNmFkYTJkMTciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzeXBhZ29fYXBpa2V5X2FkbWluIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1zeXBhZ28iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJvZmZsaW5lX2FjY2VzcyBzeXBhZ29fYXBpX2tleV9zY29wZTphYTQ4YWY1OS00Yzc0LTQzMDEtYWRiNy1jYTIzM2ZkZmVjZTguVXNlciBzeWFwcF9zY29wZSBwcm9maWxlIGVtYWlsIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJjbGllbnRIb3N0IjoiMTcyLjIwLjAuMSIsInByZWZlcnJlZF91c2VybmFtZSI6InNlcnZpY2UtYWNjb3VudC1zeXBhZ29fYXBpa2V5X2FkbWluIiwiY2xpZW50QWRkcmVzcyI6IjE3Mi4yMC4wLjEiLCJjbGllbnRfaWQiOiJzeXBhZ29fYXBpa2V5X2FkbWluIn0.uW4Cya0lRTMhPMUbWXcQs2XurdDQbPQpxzJPTruPSjQLURcPkZNJdTlVqHEZOUpVfTNTZnle0dU02VzZym31Fq7ISUWoV00rFJ4Hh7SEFRScNrluGIj7y5FYZ-9dbKY1LLTFnG4-lnAAQMmucmsG3Yktnlylq5pfNXt4wxC3yuP_zoDIuCVKQjU1cgf1HUX1Qi72KaHH-w8JEZEVbZELvEUzV49A8kCKLMJhoZ9zDDaeCHmTZDHXV1mf8t8dpLbIYSwSAomS5BzrAp3Q3vBZ-zpIcggWIlwWvljwtKX6x7G0wfQnA1gXubccp7jUdOQeO6PCrs1Ej-TNgtDt76bW_w'; // Tu key completa
        $endpoint = 'https://sypago.net:8081/api/v1/transaction/c2p'; // Endpoint oficial de SyPago para C2P
        $rifEmpresa = 'J315129558';

        try {
            // 2. Preparar el cuerpo de la petición según el estándar de SyPago
            // Nota: El formato exacto de los campos puede variar según la versión de su API, 
            // pero este es el estándar para integraciones C2P.
            $payload = [
                "amount"          => (float)$request->amount,
                "currency"        => "VES",
                "description"     => "Pago Hotspot WiFi - Usuario: " . $request->username,
                "reference"       => "WIFI" . time(), // Generamos una referencia única
                "rif"             => $rifEmpresa,
                "payment_method"  => "C2P",
                "c2p_details"     => [
                    "bank_code"    => $request->bank_code,
                    "id_number"    => "V" . $request->id_number, // Prefijo V o J según corresponda
                    "phone_number" => $request->phone_number,
                    "otp"          => $request->otp
                ]
            ];

            // 3. Realizar la petición POST a SyPago
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post($endpoint, $payload);

            // 4. Procesar la respuesta
            if ($response->successful()) {
                $data = $response->json();

                // Aquí puedes agregar la lógica para activar al usuario en MikroTik
                // o marcar el ticket como pagado en tu base de datos.
                
                Log::info("Pago SyPago Exitoso: " . $request->username);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago procesado con éxito',
                    'data'    => $data
                ]);
            } else {
                $errorMsg = $response->json()['message'] ?? 'Error en la respuesta de SyPago';
                Log::error("Error SyPago: " . $errorMsg);

                return response()->json([
                    'success' => false,
                    'message' => $errorMsg
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("Excepción SyPago: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con la pasarela de pagos.'
            ], 500);
        }
    }
}