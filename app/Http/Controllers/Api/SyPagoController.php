<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    private $baseUrl  = "https://pruebas.sypago.net:8086";
    private $clientId = "ddrs@typej";
    private $apiKey   = "U4MsDxzX8V6Qu8+vC4L4VHzBXaarEVVDnnJuRQIVH8s=";

    public function requestSms(Request $request)
    {
        // FORZAR ZONA HORARIA EN TIEMPO DE EJECUCIÓN
        date_default_timezone_set('America/Caracas');

        try {
            $token = trim($this->apiKey);

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

            // Petición con el Token
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'client_id'     => $this->clientId,
                    'Accept'        => 'application/json',
                ])
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data'    => $response->json()
                ]);
            }

            Log::error("SYPAGO OTP ERROR " . $response->status() . ": " . $response->body());
            
            return response()->json([
                'success' => false,
                'message' => 'Error 401: El servidor sigue detectando desfase horario.',
                'server_time_detected' => date('Y-m-d H:i:s')
            ], 401);

        } catch (\Exception $e) {
            Log::error("SYPAGO REQUEST EXCEPTION: " . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}