<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl  = "https://pruebas.sypago.net:8086";
    
    /** * ESTRATEGIA PARA SUBUSUARIOS:
     * Si 'ddrs@typej' falló, el estándar suele ser 'usuario.subusuario'
     * Intenta cambiarlo a "ddrs.typej" o solo "typej" si tiene su propio API Key.
     */
    private $clientId = "ddrs.typej"; 
    private $secretKey = "U4MsDxzX8V6Qu8+vC4L4VHzBXaarEVVDnnJuRQIVH8s=";

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

            // IMPORTANTE: Si falla, imprimiremos el clientId usado para verificar en el log
            Log::error("SYPAGO AUTH FAIL con ID [{$this->clientId}]: " . $response->status() . " - " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("SYPAGO AUTH EXCEPTION: " . $e->getMessage());
            return null;
        }
    }

    public function requestSms(Request $request)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo autenticar el subusuario.'
            ], 400);
        }

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

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->asJson()
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}