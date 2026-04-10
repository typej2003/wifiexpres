<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    // URL Real de Producción
    private $baseUrl  = "https://sypago.net:8086"; 
    
    // Credenciales de Producción
    private $clientId = "ddrs"; 
    private $secretKey = "NHnKKwoEaKlIkKjvfnFucPRUPuHGfSaA";

    /**
     * Paso 1: Obtener el Access Token
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withoutVerifying()
                ->asJson()
                ->post($this->baseUrl . '/api/v1/auth/token', [
                    'client_id' => trim($this->clientId),
                    'secret'    => trim($this->secretKey)
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            Log::error("SYPAGO AUTH FAIL (PROD): " . $response->status() . " - " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Paso 2: POST /api/v1/request/otp
     */
    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación con la pasarela.'
            ], 401);
        }

        try {
            // Construcción del payload según el modelo oficial
            $payload = [
                "creditor_account" => [
                    "bank_code" => "0114",
                    "type"      => "CNTA",
                    "number"    => "01140182191820067459"
                ],
                "debitor_document_info" => [
                    "type"   => "V",
                    "number" => (string) $request->input('id_number') // Ej: 123456789
                ],
                "debitor_account" => [
                    "bank_code" => (string) $request->input('bank_code'), // Ej: 0102
                    "type"      => "CELE",
                    "number"    => (string) $request->input('phone_number') // Ej: 04141234567
                ],
                "amount" => [
                    "amt"      => floatval($request->input('amount', 0)), // Debe ser numérico
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
                    'message' => 'Solicitud de OTP procesada.',
                    'data'    => $response->json()
                ]);
            }

            Log::error("SYPAGO OTP ERROR (PROD): " . $response->status() . " - " . $response->body());
            
            return response()->json([
                'success' => false,
                'detail'  => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error interno del servidor'], 500);
        }
    }
}