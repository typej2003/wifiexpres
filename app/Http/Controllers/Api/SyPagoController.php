<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyPagoController extends Controller
{
    private $baseUrl  = "https://sypago.net:8086"; 
    private $clientId = "ddrs"; 
    private $secretKey = "NHnKKwoEaKlIkKjvfnFucPRUPuHGfSaA";

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
            return null;
        }
    }

    /**
     * PASO 1 y 2: Solicitar OTP
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

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PASO 3: Confirmar Pago (Débito OTP)
     * Endpoint: /api/v1/transaction/otp
     */
    public function confirmPayment(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false], 401);

        try {
            // Generamos IDs únicos para la transacción según pide la documentación
            $internalId = strtoupper(Str::random(12)); 
            $groupId    = strtoupper(Str::random(12));

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
                "concept" => "Pago WiFiExpres", // Puedes hacerlo dinámico con $request
                "notification_urls" => [
                    "web_hook_endpoint" => "https://tu-dominio.com/api/sypago-webhook" 
                ],
                "receiving_user" => [
                    "name" => $request->input('customer_name', 'Cliente WiFi'),
                    "otp"  => (string) $request->input('otp'),
                    "document_info" => [
                        "type"   => "V",
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
                Log::info("PAGO PROCESADO SYPAGO: " . json_encode($response->json()));
                return response()->json([
                    'success' => true,
                    'transaction_id' => $response->json()['transaction_id'] ?? null,
                    'data' => $response->json()
                ]);
            }

            Log::error("SYPAGO TRANSACTION ERROR: " . $response->status() . " - " . $response->body());
            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}