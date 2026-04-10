<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // ESTA IMPORTACIÓN ES VITAL

class SyPagoController extends Controller
{
    private $baseUrl  = "https://sypago.net:8086"; 
    private $clientId = "ddrs"; 
    private $secretKey = "NHnKKwoEaKlIkKjvfnFucPRUPuHGfSaA";

    /**
     * Obtener Access Token
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

            return $response->successful() ? ($response->json()['access_token'] ?? null) : null;
        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    /**
     * PASO 2: Solicitar OTP (FUNCIONAL - NO TOCAR)
     */
    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Error Auth'], 401);

        try {
            $payload = [
                "creditor_account" => [
                    "bank_code" => "0114",
                    "type"      => "CNTA",
                    "number"    => "01140182191820067459"
                ],
                "debitor_document_info" => [
                    "type"   => "V",
                    "number" => (string) $request->input('id_number')
                ],
                "debitor_account" => [
                    "bank_code" => (string) $request->input('bank_code'),
                    "type"      => "CELE",
                    "number"    => (string) $request->input('phone_number')
                ],
                "amount" => [
                    "amt"      => floatval($request->input('amount', 0)),
                    "currency" => "VES"
                ]
            ];

            $response = Http::withoutVerifying()->withToken($token)->asJson()
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            return response()->json(['success' => $response->successful(), 'data' => $response->json()], $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PASO 3: Confirmar Pago con OTP (REVISADO)
     */
    public function confirmPayment(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false], 401);

        try {
            // Generamos IDs únicos (Máximo 12 caracteres alfanuméricos)
            $internalId = substr(strtoupper(Str::random(12)), 0, 12); 
            $groupId    = substr(strtoupper(Str::random(12)), 0, 12);

            $payload = [
                "internal_id" => $internalId,
                "group_id"    => $groupId,
                "account" => [
                    "bank_code" => "0114",
                    "type"      => "CNTA",
                    "number"    => "01140182191820067459"
                ],
                "amount" => [
                    "amt"      => floatval($request->input('amount', 0)),
                    "currency" => "VES"
                ],
                "concept" => "Pago WiFiExpres", 
                "notification_urls" => [
                    // IMPORTANTE: Cambia esto por tu URL real de producción
                    "web_hook_endpoint" => "https://panexpres.com/api/sypago-webhook" 
                ],
                "receiving_user" => [
                    "name" => $request->input('customer_name', 'Cliente PanExpres'),
                    "otp"  => (string) $request->input('otp'), // El código del SMS
                    "document_info" => [
                        "type"   => (string) $request->input('document_type', 'V'),
                        "number" => (string) $request->input('id_number')
                    ],
                    "account" => [
                        "bank_code" => (string) $request->input('bank_code'),
                        "type"      => "CELE",
                        "number"    => (string) $request->input('phone_number')
                    ]
                ]
            ];

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/transaction/otp', $payload);

            if ($response->successful()) {
                Log::info("PAGO PROCESADO SYPAGO EXITOSAMENTE", $response->json());
                return response()->json([
                    'success' => true,
                    'transaction_id' => $response->json()['transaction_id'] ?? null,
                    'data' => $response->json()
                ]);
            }

            // Si falla, registramos exactamente qué dijo el API para corregir
            Log::error("SYPAGO VALIDATION FAIL: " . $response->status() . " - " . $response->body());
            
            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO CRITICAL EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}