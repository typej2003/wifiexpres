<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            // Limpiar teléfono de espacios o guiones
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone_number'));
            
            // Usamos float directo para evitar que el API rechace el formato string de number_format
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

            // Si falla, registramos qué dice SyPago exactamente
            if (!$response->successful()) {
                Log::error("SYPAGO SMS ERROR:", ['payload' => $payload, 'response' => $data]);
            }

            return response()->json([
                'success' => $response->successful(), 
                'message' => $data['message'] ?? ($response->successful() ? 'SMS enviado' : 'Error al solicitar SMS'),
                'data' => $data
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
            $groupId    = "G" . date('Ymd');
            $amount     = (float) $request->input('amount');
            $phone      = preg_replace('/[^0-9]/', '', $request->input('phone_number'));

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

            Log::info("SYPAGO PAYLOAD CONFIRM:", $payload);

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/transaction/otp', $payload);

            $data = $response->json();
            Log::info("SYPAGO RESPUESTA CONFIRM:", $data);

            if ($response->successful() && isset($data['transaction_id'])) {
                return response()->json([
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'message' => 'Validando código...'
                ]);
            }

            $errMsg = $data['message'] ?? 'Error al validar OTP.';
            if (isset($data['rejected_code'])) {
                $errMsg = $this->getRejectedMessage($data['rejected_code']);
            }

            return response()->json([
                'success' => false, 
                'message' => $errMsg,
                'sypago_raw' => $data
            ], 400);

        } catch (\Exception $e) {
            Log::error("SYPAGO CONFIRM EXCEPTION: " . $e->getMessage());
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

            if ($response->successful()) {
                $status = $data['status'] ?? 'PROC';
                $message = ($status === 'ACCP') ? "¡Pago Exitoso!" : "Procesando...";
                
                if (!empty($data['rejected_code'])) {
                    $message = $this->getRejectedMessage($data['rejected_code']);
                }

                return response()->json([
                    'success' => ($status === 'ACCP'),
                    'status'  => $status,
                    'message' => $message,
                    'data'    => $data
                ]);
            }

            return response()->json(['success' => false, 'status' => 'ERROR'], 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getRejectedMessage($code)
    {
        $codes = [
            'TKCM'  => 'El código OTP es incorrecto o ya expiró.',
            'AM04'  => 'Fondos insuficientes en la cuenta.',
            'MBE01' => 'El cliente no está afiliado a Pago Móvil C2P.',
            'AB01'  => 'Tiempo de espera agotado con el banco.',
            'AC06'  => 'Cuenta bloqueada o inactiva.',
            'CH03'  => 'Monto fuera de los límites permitidos.',
        ];
        return $codes[$code] ?? "Transacción rechazada ($code).";
    }
}