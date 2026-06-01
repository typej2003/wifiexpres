<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Pagomovil;
use App\Models\Router;
use App\Models\Sale;
use App\Services\ExchangeRateService;

class SyPagoController extends Controller
{
    private $baseUrl   = "https://app.sypago.net:8086";
    private $clientId  = "ddrs"; 
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
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Error de Autenticación'], 401);

        try {
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone_number'));
            $amount = (float) $request->input('amount');

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
                    "number"    => (string) $phone
                ],
                "amount" => [
                    "amt"      => $amount, 
                    "currency" => "VES"
                ]
            ];

            $response = Http::withoutVerifying()->withToken($token)->asJson()
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            $data = $response->json();

            return response()->json([
                'success' => $response->successful(), 
                'message' => $data['message'] ?? ($response->successful() ? 'SMS enviado' : 'Error al solicitar SMS'),
                'debug_sypago' => $data // <--- DEBUG PARA EL NAVEGADOR
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Token expirado'], 401);

        try {
            $internalId = "TX" . time() . rand(100, 999); 
            $groupId = "WIFI" . date('Ymd'); 
            $amount = (float) $request->input('amount');
            $phone  = preg_replace('/[^0-9]/', '', $request->input('phone_number'));

            $payload = [
                "internal_id" => $internalId,
                "group_id"    => $groupId,
                "account" => [
                    "bank_code" => "0114",
                    "type"      => "CNTA",
                    "number"    => "01140182191820067459"
                ],
                "amount" => [
                    "amt"      => $amount,
                    "currency" => "VES"
                ],
                "concept" => "Pago WiFiExpres " . ($request->input('username') ?? 'Servicio'),
                "receiving_user" => [
                    "name" => "CLIENTE WIFIEXPRES", 
                    "otp"  => (string) $request->input('otp'),
                    "document_info" => [
                        "type"   => "V",
                        "number" => (string) $request->input('id_number')
                    ],
                    "account" => [
                        "bank_code" => (string) $request->input('bank_code'),
                        "type"      => "CELE",
                        "number"    => (string) $phone
                    ]
                ]
            ];

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/transaction/otp', $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['transaction_id'])) {
                
                // --- INICIO DE REGISTRO DE TRANSACCIÓN ---
                // Capturamos identity de varias posibles fuentes para asegurar su persistencia
                $identity = $request->input('identity') ?? $request->input('router_identity') ?? $request->input('router_mac');
                $planName = $request->input('plan') ?? $request->input('planSelected') ?? 'Servicio WiFi';

                $transaccion = \App\Models\Pagomovil::create([
                    'referencia'      => $data['transaction_id'],
                    'identity'        => $identity,
                    'telefono'        => $phone,
                    'user'            => $phone,
                    'banco'           => 'SyPago',
                    'plan'            => $planName,
                    'monto'           => $amount,
                    'externalcomment' => json_encode($data) . ' / SyPago Confirm',
                    'status'          => 'PAGADO',
                    'token'           => $data['transaction_id'],
                    'active'          => false,
                ]);

                // Buscar el router de forma flexible (por identidad o por MAC)
                $router = \App\Models\Router::where('identity', $identity)
                    ->orWhere('macAddress', $identity)
                    ->first();

                if ($router && $transaccion) {
                    $currentRate = ExchangeRateService::getBcvRate();
                    $costoUsd = round($amount / $currentRate, 4);

                    \App\Models\Sale::create([
                        'user_id'      => $router->user_id, // El dueño del router (Aliado)
                        'router_id'    => $router->id,
                        'type'         => 'pasarela',
                        'reference_id' => $transaccion->id, // ID del registro en pagomovils
                        'description'  => "Pago SyPago: Plan " . $planName . " - Ref: " . $data['transaction_id'],
                        'amount_bs'    => $amount, 
                        'amount_usd'   => $costoUsd,
                        'rate'         => $currentRate,
                    ]);
                }
                // --- FIN DE REGISTRO DE TRANSACCIÓN ---

                return response()->json([
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'message' => 'Validando código...',
                    'debug_sypago' => $data // <--- DEBUG
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => $data['message'] ?? 'Error al validar OTP.',
                'debug_sypago' => $data // <--- DEBUG
            ], 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error de conexión'], 500);
        }
    }

    public function checkStatus($transactionId)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false], 401);

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->get($this->baseUrl . "/api/v1/transaction/{$transactionId}");

            $data = $response->json();

            return response()->json([
                'success' => (($data['status'] ?? '') === 'ACCP'),
                'status'  => $data['status'] ?? 'PROC',
                'message' => $data['message'] ?? 'Procesando...',
                'debug_sypago' => $data // <--- DEBUG
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}