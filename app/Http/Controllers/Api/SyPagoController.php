<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Paso 2: Solicitar OTP (Ya lo tienes funcionando)
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
     * PASO 3: Confirmar Pago con OTP
     * Endpoint: POST /api/v1/confirm/otp
     */
    public function confirmPayment(Request $request)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Error de autenticación.'], 401);
        }

        try {
            // El payload es idéntico al de solicitud, pero agregando "otp"
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
                ],
                "otp" => (string) $request->input('otp') // El código que llegó por SMS
            ];

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/confirm/otp', $payload);

            if ($response->successful()) {
                // Aquí el pago fue exitoso
                Log::info("PAGO EXITOSO SYPAGO: " . json_encode($response->json()));
                return response()->json([
                    'success' => true,
                    'message' => 'Pago realizado con éxito.',
                    'receipt' => $response->json()
                ]);
            }

            Log::error("SYPAGO CONFIRM ERROR: " . $response->status() . " - " . $response->body());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el pago.',
                'detail'  => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO CONFIRM EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error en el proceso de confirmación'], 500);
        }
    }
}