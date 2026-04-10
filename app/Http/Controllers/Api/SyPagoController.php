<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Indispensable para que herede correctamente
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl = "https://pruebas.sypago.net:8086";
    private $clientId = "ddrsistemas@gmail.com";
    private $apiKey = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJmZXpQcl9HSWhIZ05jOVc1cU5Td2FIQXBRMVRqeUlqbWtpY0d5V1hHUjFzIn0.eyJleHAiOjE4NzAzNzIzNjksImlhdCI6MTc3NTc2NDM2OSwianRpIjoiYWFhYjM4MDMtZDc5NS00MjkxLWJkODgtMzBiY2IyNWExYTc0IiwiaXNzIjoiaHR0cHM6Ly9zeXBhZ28ubmV0OjgwODEvcmVhbG1zL3N5cGFnbyIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiIzMzQ0YTI0Ni0wMTIzLTQ3MWItODYwZi05MTNmNmFkYTJkMTciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzeXBhZ29fYXBpa2V5X2FkbWluIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1zeXBhZ28iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJvZmZsaW5lX2FjY2VzcyBzeXBhZ29fYXBpX2tleV9zY29wZTphYTQ4YWY1OS00Yzc0LTQzMDEtYWRiNy1jYTIzM2ZkZmVjZTguVXNlciBzeWFwcF9zY29wZSBwcm9maWxlIGVtYWlsIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJjbGllbnRIb3N0IjoiMTcyLjIwLjAuMSIsInByZWZlcnJlZF91c2VybmFtZSI6InNlcnZpY2UtYWNjb3VudC1zeXBhZ29fYXBpa2V5X2FkbWluIiwiY2xpZW50QWRkcmVzcyI6IjE3Mi4yMC4wLjEiLCJjbGllbnRfaWQiOiJzeXBhZ29fYXBpa2V5X2FkbWluIn0.uW4Cya0lRTMhPMUbWXcQs2XurdDQbPQpxzJPTruPSjQLURcPkZNJdTlVqHEZOUpVfTNTZnle0dU02VzZym31Fq7ISUWoV00rFJ4Hh7SEFRScNrluGIj7y5FYZ-9dbKY1LLTFnG4-lnAAQMmucmsG3Yktnlylq5pfNXt4wxC3yuP_zoDIuCVKQjU1cgf1HUX1Qi72KaHH-w8JEZEVbZELvEUzV49A8kCKLMJhoZ9zDDaeCHmTZDHXV1mf8t8dpLbIYSwSAomS5BzrAp3Q3vBZ-zpIcggWIlwWvljwtKX6x7G0wfQnA1gXubccp7jUdOQeO6PCrs1Ej-TNgtDt76bW_w";

    /**
     * Obtiene el token dinámico de SyPago
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withoutVerifying()->post($this->baseUrl . '/api/v1/auth/token', [
                'client_id' => $this->clientId,
                'secret'    => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            Log::error("SYPAGO AUTH FAIL: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Paso 1: Solicitar SMS OTP al cliente
     */
    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();

        // Si la autenticación falla, usamos la API Key directamente como fallback
        if (!$token) { $token = $this->apiKey; }

        try {
            // Limpieza de datos recibidos del formulario
            $bankCode    = (string) $request->input('bank_code');
            $idNumber    = (string) $request->input('id_number');
            $phoneNumber = (string) $request->input('phone_number');
            $amount      = floatval($request->input('amount', 0));

            // Datos de tu comercio (Bancaribe)
            $myBankCode      = "0114"; 
            $myAccountNumber = "01140182191820067459"; 

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

            // Petición al API de SyPago para generar el SMS
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS enviado correctamente por SyPago.',
                    'data'    => $response->json()
                ]);
            }

            Log::error("SYPAGO OTP ERROR: " . $response->status() . " - " . $response->body());
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar SMS: ' . ($response->json()['message'] ?? 'Respuesta inválida')
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno en el servidor.'
            ], 500);
        }
    }
}