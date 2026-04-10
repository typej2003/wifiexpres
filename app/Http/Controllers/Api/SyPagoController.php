<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl  = "https://pruebas.sypago.net:8086";
    
    // Tus nuevas credenciales
    private $clientId = "ddrs@typej";
    private $secretKey = "U4MsDxzX8V6Qu8+vC4L4VHzBXaarEVVDnnJuRQIVH8s=";

    /**
     * Paso 1: Obtener el Access Token dinámicamente
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withoutVerifying()
                ->asJson()
                ->post($this->baseUrl . '/api/v1/auth/token', [
                    'client_id' => $this->clientId,
                    'secret'    => $this->secretKey
                ]);

            if ($response->successful()) {
                // Extraemos el access_token del JSON de respuesta
                return $response->json()['access_token'] ?? null;
            }

            Log::error("SYPAGO AUTH FAIL: " . $response->status() . " - " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Paso 2: Solicitar OTP usando el Token generado
     */
    public function requestSms(Request $request)
    {
        // 1. Obtenemos el token dinámico
        $token = $this->getAccessToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el token de acceso. Revisa client_id y secret.'
            ], 401);
        }

        try {
            // 2. Preparamos el payload del OTP
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

            // 3. Enviamos la solicitud de OTP con el nuevo Bearer Token
            $response = Http::withoutVerifying()
                ->withToken($token) // Esto pone Authorization: Bearer {token}
                ->asJson()
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP enviado con éxito.',
                    'data'    => $response->json()
                ]);
            }

            Log::error("SYPAGO OTP ERROR: " . $response->status() . " - " . $response->body());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar OTP.',
                'status'  => $response->status(),
                'detail'  => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}