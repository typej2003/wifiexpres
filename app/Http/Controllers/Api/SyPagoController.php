<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyPagoController extends Controller
{
    private $baseUrl   = "https://app.sypago.net:8086";
    private $clientId  = "ddrs"; 
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
     * PASO 1: Solicitar OTP (SMS)
     */
    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Error de Autenticación'], 401);

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

            return response()->json([
                'success' => $response->successful(), 
                'data' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PASO 2: Confirmar Pago con OTP
     * Retorna el transaction_id para empezar el monitoreo
     */
    public function confirmPayment(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Token expirado'], 401);

        try {
            // Generación de IDs según formato de tu ejemplo
            $internalId = time() . "-" . rand(1000, 9999); 
            $groupId    = "ci_" . date('Ymd');

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
                "concept" => "Pago WiFiExpres - " . ($request->input('username') ?? 'Cliente'),
                "receiving_user" => [
                    "name" => null,
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

            $data = $response->json();

            if ($response->successful() && isset($data['transaction_id'])) {
                return response()->json([
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'message' => 'Procesando pago...'
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => $data['message'] ?? 'Error al iniciar transacción.',
                'sypago_raw' => $data
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error de conexión'], 500);
        }
    }

    /**
     * PASO 3: Consultar Estatus (Polling cada 10s desde el JS)
     */
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
                
                // Si el estatus es rechazado, buscamos el motivo
                $message = "Procesando...";
                if ($status === 'ACCP') $message = "¡Pago Exitoso!";
                if (!empty($data['rejected_code'])) $message = $this->getRejectedMessage($data['rejected_code']);

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
            'TKCM'  => 'El código OTP es incorrecto.',
            'AM04'  => 'Usted no posee saldo suficiente.',
            'MBE01' => 'El cliente pagador no está afiliado a C2P.',
            'AB01'  => 'Tiempo de espera agotado.',
            'AC06'  => 'Cuenta bloqueada o inactiva.',
        ];
        return $codes[$code] ?? "Transacción rechazada ($code).";
    }
}