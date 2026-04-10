<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // CORRECCIÓN: Importación correcta
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl = "https://pruebas.sypago.net:8086";
    private $clientId = "ddrsistemas@gmail.com";
    
    // API KEY sanitizada (sin espacios ni saltos de línea)
    private $apiKey = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJmZXpQcl9HSWhIZ05jOVc1cU5Td2FIQXBRMVRqeUlqbWtpY0d5V1hHUjFzIn0.eyJleHAiOjE4NzAzNzIzNjksImlhdCI6MTc3NTc2NDM2OSwianRpIjoiYWFhYjM4MDMtZDc5NS00MjkxLWJkODgtMzBiY2IyNWExYTc0IiwiaXNzIjoiaHR0cHM6Ly9zeXBhZ28ubmV0OjgwODEvcmVhbG1zL3N5cGFnbyIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiIzMzQ0YTI0Ni0wMTIzLTQ3MWItODYwZi05MTNmNmFkYTJkMTciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzeXBhZ29fYXBpa2V5X2FkbWluIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1zeXBhZ28iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJvZmZsaW5lX2FjY2VzcyBzeXBhZ29fYXBp_KEY_SCOPE_ETC";

    private function getAccessToken()
    {
        try {
            // NOTA: Probamos enviar 'api_key' porque el log dice que no la encuentra como 'secret'
            $response = Http::withoutVerifying()->post($this->baseUrl . '/api/v1/auth/token', [
                'client_id' => $this->clientId,
                'secret'   => trim($this->apiKey) 
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            // Si falla con 'api_key', intentamos con 'secret' una última vez
            $responseFallback = Http::withoutVerifying()->post($this->baseUrl . '/api/v1/auth/token', [
                'client_id' => $this->clientId,
                'secret'    => trim($this->apiKey)
            ]);

            if ($responseFallback->successful()) {
                return $responseFallback->json()['access_token'] ?? null;
            }

            Log::error("SYPAGO AUTH FAIL FINAL: " . $responseFallback->body());
            return null;
        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();

        // Si el token dinámico falla, usamos la API Key como token Bearer directo (fallback)
        if (!$token) {
            $token = trim($this->apiKey);
        }

        try {
            $bankCode    = (string) $request->input('bank_code');
            $idNumber    = (string) $request->input('id_number');
            $phoneNumber = (string) $request->input('phone_number');
            $amount      = floatval($request->input('amount', 0));

            $payload = [
                "creditor_account" => [
                    "bank_code" => "0114",
                    "type"      => "CNTA",
                    "number"    => "01140182191820067459"
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
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json(['success' => true, 'data' => $response->json()]);
            }

            Log::error("SYPAGO OTP ERROR: " . $response->status() . " - " . $response->body());
            return response()->json([
                'success' => false, 
                'message' => 'Error 401: Revisa si tu API Key de pruebas sigue activa.'
            ], 401);

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}