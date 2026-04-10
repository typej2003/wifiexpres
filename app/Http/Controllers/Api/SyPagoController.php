<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyPagoController extends Controller
{
    // Tu API KEY es un JWT, por lo tanto, la usaremos directamente como Token.
    private $baseUrl = "https://pruebas.sypago.net:8086";
    private $token = "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJmZXpQcl9HSWhIZ05jOVc1cU5Td2FIQXBRMVRqeUlqbWtpY0d5V1hHUjFzIn0.eyJleHAiOjE4NzAzNzIzNjksImlhdCI6MTc3NTc2NDM2OSwianRpIjoiYWFhYjM4MDMtZDc5NS00MjkxLWJkODgtMzBiY2IyNWExYTc0IiwiaXNzIjoiaHR0cHM6Ly9zeXBhZ28ubmV0OjgwODEvcmVhbG1zL3N5cGFnbyIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiIzMzQ0YTI0Ni0wMTIzLTQ3MWItODYwZi05MTNmNmFkYTJkMTciLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJzeXBhZ29fYXBpa2V5X2FkbWluIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1zeXBhZ28iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJvZmZsaW5lX2FjY2VzcyBzeXBhZ29fYXBpX2tleV9zY29wZTphYTQ4YWY1OS00Yzc0LTQzMDEtYWRiNy1jYTIzM2ZkZmVjZTguVXNlciBzeWFwcF9zY29wZSBwcm9maWxlIGVtYWlsIiwiZW1haWxfdmVyaWZpZWQiOmZhbHNlLCJjbGllbnRIb3N0IjoiMTcyLjIwLjAuMSIsInByZWZlcnJlZF91c2VybmFtZSI6InNlcnZpY2UtYWNjb3VudC1zeXBhZ29fYXBpa2V5X2FkbWluIiwiY2xpZW50QWRkcmVzcyI6IjE3Mi4yMC4wLjEiLCJjbGllbnRfaWQiOiJzeXBhZ29fYXBpa2V5X2FkbWluIn0.uW4Cya0lRTMhPMUbWXcQs2XurdDQbPQpxzJPTruPSjQLURcPkZNJdTlVqHEZOUpVfTNTZnle0dU02VzZym31Fq7ISUWoV00rFJ4Hh7SEFRScNrluGIj7y5FYZ-9dbKY1LLTFnG4-lnAAQMmucmsG3Yktnlylq5pfNXt4wxC3yuP_zoDIuCVKQjU1cgf1HUX1Qi72KaHH-w8JEZEVbZELvEUzV49A8kCKLMJhoZ9zDDaeCHmTZDHXV1mf8t8dpLbIYSwSAomS5BzrAp3Q3vBZ-zpIcggWIlwWvljwtKX6x7G0wfQnA1gXubccp7jUdOQeO6PCrs1Ej-TNgtDt76bW_w";

    public function requestSms(Request $request)
    {
        try {
            // Recoger datos del formulario
            $bankCode    = $request->input('bank_code');
            $idNumber    = $request->input('id_number');
            $phoneNumber = $request->input('phone_number');
            $amount      = (float) $request->input('amount');

            // DATOS DE TU COMERCIO (Verifica estos con SyPago)
            $myBankCode      = "0114"; 
            $myAccountNumber = "01140182191820067459"; 

            // Payload exacto según documentación
            $payload = [
                "creditor_account" => [
                    "bank_code" => $myBankCode,
                    "type"      => "CNTA",
                    "number"    => $myAccountNumber
                ],
                "debitor_document_info" => [
                    "type"   => "V",
                    "number" => $idNumber
                ],
                "debitor_account" => [
                    "bank_code" => $bankCode,
                    "type"      => "CELE",
                    "number"    => $phoneNumber
                ],
                "amount" => [
                    "amt"      => $amount,
                    "currency" => "VES"
                ]
            ];

            // Petición usando el Token directamente (como Portador/Bearer)
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/api/v1/request/otp', $payload);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Código SMS solicitado con éxito.',
                    'details' => $response->json()
                ]);
            }

            // Si falla, registramos el error para ver si el token fue aceptado o no
            Log::error("ERROR OTP SYPAGO: " . $response->status() . " - " . $response->body());
            
            return response()->json([
                'success' => false,
                'message' => 'Error en la pasarela: ' . ($response->json()['message'] ?? 'Respuesta no válida')
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("EXCEPCIÓN OTP: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno en el servidor.'
            ], 500);
        }
    }
}