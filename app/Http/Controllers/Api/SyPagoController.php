<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SyPagoController extends Controller
{
    private $apiKey = "TU_API_KEY_DE_SYPAGO"; 
    private $urlBase = "https://api.sypago.net/v1"; // Reemplazar por la URL real de SyPago

    // PASO 1: Disparar el SMS desde el Banco
    public function requestSms(Request $request) {
        // 1. Guardas al usuario temporalmente en tu DB (como ya lo haces)
        // ... tu lógica de registro pre-pago ...

        return response()->json(['success' => true, 'message' => 'Llego a requestSms']);

        // 2. Llamas a SyPago para solicitar el C2P
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->post($this->urlBase . '/c2p/request', [
            'bank_code'    => $request->bank_code,
            'id_number'    => $request->id_number,
            'phone_number' => $request->phone_number,
            'amount'       => $request->amount,
            'description'  => "Plan WiFi: " . $request->plan
        ]);

        if ($response->successful()) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'No se pudo generar el SMS']);
    }

    // PASO 2: Confirmar el pago con el OTP
    public function confirmPayment(Request $request) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->post($this->urlBase . '/c2p/confirm', [
            'otp'          => $request->otp,
            'id_number'    => $request->id_number,
            'phone_number' => $request->phone_number,
            'bank_code'    => $request->bank_code,
            'amount'       => $request->amount,
            'target_account' => "01140182191820067459" 
        ]);

        $resData = $response->json();

        if ($response->successful() && $resData['status'] == 'approved') {
            // ACTIVAR EL PLAN AQUÍ (o retornar éxito para que el front lo haga)
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'OTP inválido o fondos insuficientes']);
    }
}