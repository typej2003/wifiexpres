<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl  = "https://pruebas.sypago.net:8086";
    private $clientId = "ddrs"; 
    private $secretKey = "NHnKKwoEaKlIkKjvfnFucPRUPuHGfSaA";

    private function getAccessToken()
    {
        try {
            // Intentamos con el formato exacto que pide el middleware de SyPago
            $response = Http::withoutVerifying()
                ->asJson()
                ->withHeaders([
                    'client_id' => $this->clientId, // Algunos WAF lo filtran si no va en el header
                    'Accept'    => 'application/json',
                ])
                ->post($this->baseUrl . '/api/v1/auth/token', [
                    'client_id' => trim($this->clientId),
                    'secret'    => trim($this->secretKey)
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            // Si falla el v1, intentamos sin el prefijo v1 por si acaso
            if ($response->status() == 404 || $response->status() == 400) {
                 $secondAttempt = Http::withoutVerifying()
                    ->asJson()
                    ->post($this->baseUrl . '/api/auth/token', [
                        'client_id' => trim($this->clientId),
                        'secret'    => trim($this->secretKey)
                    ]);
                 
                 if ($secondAttempt->successful()) {
                     return $secondAttempt->json()['access_token'] ?? null;
                 }
            }

            Log::error("SYPAGO AUTH FAIL [{$this->clientId}]: " . $response->status() . " - " . $response->body());
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
                'message' => 'Error de autenticación: ApiKeyNotFound. Verifique si el usuario principal está activo en el portal de pruebas.'
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