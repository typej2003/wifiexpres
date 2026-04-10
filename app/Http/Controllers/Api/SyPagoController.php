<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Esto corrige el error: Class "App\Http\Controllers\Api\Controller" not found
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl  = "https://pruebas.sypago.net:8086";
    private $clientId = "ddrs";
    private $apiKey   = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJmZXpQcl9HSWhIZ05jOVc1cU5Td2FIQXBRMVRqeUlqbWtpY0d5V1hHUjFzIn0.eyJleHAiOjE4NzA0NjU2MTUsImlhdCI6MTc3NTg1NzYxNSwianRpIjoiMGVkNWU2NmYtMTAzNS00NDIzLThkZDItMTUzN2E5ODIxYWVhIiwiaXNzIjoiaHR0cHM6Ly9zeXBhZ28ubmV0OjgwODEvcmVhbG1zL3N5cGFnbyIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiIzMzQ0YTI0Ni0wMTIzLTQ3MWItODYwZi05MTNmNmFkYTJkMTciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzeXBhZ29fYXBpa2V5X2FkbWluIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1zeXBhZ28iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJvZmZsaW5lX2FjY2VzcyBzeXBhZ29fYXBpX2tleV9zY29wZTphYTQ4YWY1OS00Yzc0LTQzMDEtYWRiNy1jYTIzM2ZkZmVjZTguVXNlciBzeWFwcF9zY29wZSBwcm9maWxlIGVtYWlsIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJjbGllbnRIb3N0IjoiMTcyLjIwLjAuMSIsInByZWZlcnJlZF91c2VybmFtZSI6InNlcnZpY2UtYWNjb3VudC1zeXBhZ29fYXBpa2V5X2FkbWluIiwiY2xpZW50QWRkcmVzcyI6IjE3Mi4yMC4wLjEiLCJjbGllbnRfaWQiOiJzeXBhZ29fYXBpa2V5X2FkbWluIn0.tnA9Vqi7DXelkbJpVQ5nXkxw_F0BoplEXNFAdsCqiGLJRurimaDG81UmH2qRuNZxFTdhPM69abPZVHZBgjvQ5PwjphAoJ_KoFKwadAnoP_F3MAUs_pSKhcJ1Frn7dPzaLCCYnChZFr3vDwj5t7wMBsiWjjsgCJtYwil7omo5YqwFtPOvQ0CRB9QTvop4JoZRw2kXsSgXu6kwLtS5e84VMQSelU9DOa4n8NgsH_tZJ5slaVGWWqSWIYuLZWQ1SqYGvG449joa5hGQ11e64YmP8H-uL3ReBBYNQ0WqJGaJ7gh0gK_5AmAcbKVCnyE4-qSHzxqmmu5KYNqkv7Q-DxGaPA";

    /**
     * Obtener Token según documentación oficial
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withoutVerifying()
                ->asJson()
                ->post($this->baseUrl . '/api/v1/auth/token', [
                    'client_id' => $this->clientId,
                    'secret'    => trim($this->apiKey) // Usamos el campo 'secret' como pide la doc
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            Log::error("SYPAGO AUTH ERROR: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Paso 1: Solicitar OTP
     */
    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();

        // Fallback: Si el token falla, intentamos usar la apiKey directamente
        if (!$token) {
            $token = trim($this->apiKey);
        }

        try {
            // Datos del formulario
            $bankCode    = (string) $request->input('bank_code');
            $idNumber    = (string) $request->input('id_number');
            $phoneNumber = (string) $request->input('phone_number');
            $amount      = floatval($request->input('amount', 0));

            // Tus datos (Bancaribe)
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

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data'    => $response->json()
                ]);
            }

            Log::error("SYPAGO OTP ERROR: " . $response->status() . " - " . $response->body());
            return response()->json([
                'success' => false,
                'message' => 'Error 401: Respuesta de pasarela no autorizada.'
            ], 401);

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}