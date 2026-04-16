<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyPagoController extends Controller
{
    //private $baseUrl   = "https://sypago.net:8086"; 
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
     * PASO 2: Solicitar OTP
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
                'sypago_raw' => $response->json(),
                'data' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PASO 3: Confirmar Pago con OTP
     */
    public function confirmPayment(Request $request)
    {
        $token = $this->getAccessToken();
        if (!$token) return response()->json(['success' => false, 'message' => 'Token expirado'], 401);

        try {
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
                    "web_hook_endpoint" => "https://wifiexpres.com/api/sypago-webhook" 
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

            // LOG DE CONTROL PARA DEPURACIÓN
            Log::info("INTENTO DE COBRO SYPAGO - OTP ENVIADO: " . $request->input('otp'));

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/transaction/otp', $payload);

            $data = $response->json();

            if ($response->successful()) {
                
                // VALIDACIÓN CRUCIAL: ¿Viene un código de rechazo aunque el HTTP sea 200?
                if (isset($data['RejectedCode']) && !empty($data['RejectedCode'])) {
                    $motivo = $this->getRejectedMessage($data['RejectedCode']);
                    
                    Log::warning("SYPAGO RECHAZADO EN RESPUESTA: {$data['RejectedCode']}", $data);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $motivo,
                        'rejected_code' => $data['RejectedCode'],
                        'sypago_raw' => $data 
                    ], 200); 
                }

                // ÉXITO REAL SI HAY TRANSACTION_ID
                if (isset($data['transaction_id'])) {
                    Log::info("SYPAGO PAGO EXITOSO", $data);
                    return response()->json([
                        'success' => true,
                        'transaction_id' => $data['transaction_id'],
                        'sypago_raw' => $data 
                    ]);
                }
            }

            // Errores de Formato o Fallos de la Pasarela
            return response()->json([
                'success' => false, 
                'message' => $data['message'] ?? 'Datos incorrectos o error en la pasarela.',
                'sypago_raw' => $data
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO CRITICAL EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error de conexión con SyPago'], 500);
        }
    }

    /**
     * Diccionario completo de códigos de rechazo
     */
    private function getRejectedMessage($code)
    {
        $codes = [
            'WAIT'  => 'Operación en espera de validación de código.',
            'AG09'  => 'Pago no recibido.',
            'AC00'  => 'Operación en espera de respuesta del receptor.',
            'AB01'  => 'Tiempo de espera agotado.',
            'AB07'  => 'Agente fuera de línea.',
            'AC01'  => 'Número de cuenta incorrecto.',
            'AC04'  => 'Cuenta cancelada.',
            'AC06'  => 'Cuenta bloqueada.',
            'AC09'  => 'Moneda no válida.',
            'AG10'  => 'Agente suspendido o excluido.',
            'AM02'  => 'Monto de la transacción no permitido.',
            'AM03'  => 'Moneda no permitida.',
            'AM04'  => 'Usted no posee saldo suficiente.',
            'AM05'  => 'Operación duplicada.',
            'BE01'  => 'Los datos del cliente no corresponden a la cuenta.',
            'BE20'  => 'Longitud del nombre inválida.',
            'CH20'  => 'Número de decimales incorrecto.',
            'DU01'  => 'Identificación de mensaje duplicado.',
            'ED05'  => 'Liquidación fallida.',
            'FF05'  => 'Código del producto incorrecto.',
            'FF07'  => 'Código del subproducto incorrecto.',
            'RC08'  => 'El banco no existe en el sistema.',
            'TKCM'  => 'El código OTP es incorrecto.',
            'TM01'  => 'Operación fuera del horario permitido.',
            'VE01'  => 'Rechazo técnico de la plataforma.',
            'DT03'  => 'Fecha de procesamiento no válida.',
            'TECH'  => 'Error técnico al procesar liquidación.',
            'AG01'  => 'Transacción restringida.',
            'MD09'  => 'Afiliación inactiva.',
            'MD15'  => 'Monto incorrecto.',
            'MD21'  => 'Cobro no permitido.',
            'CUST'  => 'Cancelación solicitada por el deudor.',
            'DS02'  => 'Operación cancelada.',
            'MD01'  => 'No posee afiliación a este servicio.',
            'MD22'  => 'Afiliación suspendida.',
            'MBE01' => 'El cliente pagador no está afiliado a C2P.',
        ];

        return $codes[$code] ?? "Transacción rechazada por el banco (Código: $code).";
    }
}