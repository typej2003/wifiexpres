<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    // Configuración base de SyPago
    private $baseUrl = "https://pruebas.sypago.net:8086";
    private $clientId = "ddrsistemas@gmail.com";
    private $apiKey = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJmZXpQcl9HSWhIZ05jOVc1cU5Td2FIQXBRMVRqeUlqbWtpY0d5V1hHUjFzIn0.eyJleHAiOjE4NzAzNzIzNjksImlhdCI6MTc3NTc2NDM2OSwianRpIjoiYWFhYjM4MDMtZDc5NS00MjkxLWJkODgtMzBiY2IyNWExYTc0IiwiaXNzIjoiaHR0cHM6Ly9zeXBhZ28ubmV0OjgwODEvcmVhbG1zL3N5cGFnbyIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiIzMzQ0YTI0Ni0wMTIzLTQ3MWItODYwZi05MTNmNmFkYTJkMTciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzeXBhZ29fYXBpa2V5X2FkbWluIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1zeXBhZ28iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJvZmZsaW5lX2FjY2VzcyBzeXBhZ29fYXBpX2tleV9zY29wZTphYTQ4YWY1OS00Yzc0LTQzMDEtYWRiNy1jYTIzM2ZkZmVjZTguVXNlciBzeWFwcF9zY29wZSBwcm9maWxlIGVtYWlsIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJjbGllbnRIb3N0IjoiMTcyLjIwLjAuMSIsInByZWZlcnJlZF91c2VybmFtZSI6InNlcnZpY2UtYWNjb3VudC1zeXBhZ29fYXBpa2V5X2FkbWluIiwiY2xpZW50QWRkcmVzcyI6IjE3Mi4yMC4wLjEiLCJjbGllbnRfaWQiOiJzeXBhZ29fYXBpa2V5X2FkbWluIn0.uW4Cya0lRTMhPMUbWXcQs2XurdDQbPQpxzJPTruPSjQLURcPkZNJdTlVqHEZOUpVfTNTZnle0dU02VzZym31Fq7ISUWoV00rFJ4Hh7SEFRScNrluGIj7y5FYZ-9dbKY1LLTFnG4-lnAAQMmucmsG3Yktnlylq5pfNXt4wxC3yuP_zoDIuCVKQjU1cgf1HUX1Qi72KaHH-w8JEZEVbZELvEUzV49A8kCKLMJhoZ9zDDaeCHmTZDHXV1mf8t8dpLbIYSwSAomS5BzrAp3Q3vBZ-zpIcggWIlwWvljwtKX6x7G0wfQnA1gXubccp7jUdOQeO6PCrs1Ej-TNgtDt76bW_w";

    /**
     * Paso 1: Obtener Token de Acceso Dinámico
     */
    private function getAccessToken()
    {
        try {
            $response = Http::post($this->baseUrl . '/api/v1/auth/token', [
                'client_id' => $this->clientId,
                'secret'    => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            Log::error("Error Autenticación SyPago: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Excepción Autenticación SyPago: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Paso 2: Solicitar el SMS (OTP) al usuario
     */
    public function requestSms(Request $request)
    {
        // A. Obtener el Token primero
        $token = $this->getAccessToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo autenticar con la pasarela de pagos.'
            ], 401);
        }

        try {
            // B. Recoger datos del formulario
            $bankCode    = $request->input('bank_code');
            $idNumber    = $request->input('id_number');
            $phoneNumber = $request->input('phone_number');
            $amount      = (float) $request->input('amount');

            // DATOS DE TU COMERCIO (Ajusta estos valores con tus datos reales de banco)
            $myBankCode      = "0102"; 
            $myAccountNumber = "01020000000000000000"; 

            // C. Construir el Payload para el OTP
            $payload = [
                "creditor_account" => [
                    "bank_code" => $myBankCode,
                    "type"      => "CNTA",
                    "number"    => $myAccountNumber
                ],
                "debitor_document_info" => [
                    "type"   => "V",
                    "number" => $idNumber
                ],
                "debitor_account" => [
                    "bank_code" => $bankCode,
                    "type"      => "CELE",
                    "number"    => $phoneNumber
                ],
                "amount" => [
                    "amt"      => $amount,
                    "currency" => "VES"
                ]
            ];

            // D. Petición a SyPago para solicitar OTP
            $response = Http::withToken($token)
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Código SMS solicitado con éxito.',
                    'details' => $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar SMS: ' . ($response->json()['message'] ?? 'Respuesta inválida')
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("Error en requestSms: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno en el servidor.'
            ], 500);
        }
    }

    /**
     * Paso 3: Confirmar el pago con el OTP recibido
     */
    public function confirmPayment(Request $request)
    {
        // También requiere token para procesar el pago final
        $token = $this->getAccessToken();
        
        // Aquí iría la lógica de POST /api/v1/payment/confirm (o similar según tu doc)
        // Usando el token obtenido.
    }
}