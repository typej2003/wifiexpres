<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextBeeService
{
    protected string $apiKey;
    protected string $deviceId;
    protected string $baseUrl;

    public function __construct()
    {
        // Cargamos las credenciales configuradas en el archivo .env
        $this->apiKey = env('TEXTBEE_API_KEY', '');
        $this->deviceId = env('TEXTBEE_DEVICE_ID', '');
        $this->baseUrl = 'https://api.textbee.dev/api/v1';
    }

    /**
     * Envía un mensaje de texto SMS a través del teléfono Android vinculado.
     *
     * @param string $phone Número de teléfono con código de país (ej: +584120000000 o +34600000000)
     * @param string $message Mensaje de texto a enviar (máximo 160 caracteres preferiblemente)
     * @return array Array con el estado de la operación ['success' => bool, 'message' => string]
     */
    public function sendSms(string $phone, string $message): array
    {
        // Validar que tengamos las credenciales necesarias
        if (empty($this->apiKey) || empty($this->deviceId)) {
            Log::error("TextBee Service: Faltan configurar las credenciales TEXTBEE_API_KEY o TEXTBEE_DEVICE_ID en el archivo .env");
            return [
                'success' => false,
                'message' => 'Faltan configurar las credenciales del servicio en el servidor.'
            ];
        }

        try {
            // Realizamos la petición HTTP POST a la API oficial de TextBee
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/gateway/devices/{$this->deviceId}/send-sms", [
                'recipients' => [$phone],
                'message' => $message,
            ]);

            // Si la respuesta es exitosa (código 200-299)
            if ($response->successful()) {
                Log::info("TextBee SMS enviado con éxito a: {$phone}");
                return [
                    'success' => true,
                    'message' => 'El SMS ha sido enviado exitosamente a la cola de tu teléfono.'
                ];
            }

            // Si la API de TextBee responde con un error estructurado
            Log::error("TextBee API Error: " . $response->body());
            return [
                'success' => false,
                'message' => 'Error de TextBee: ' . ($response->json()['message'] ?? 'No se pudo procesar el envío.')
            ];

        } catch (\Exception $e) {
            // Capturar errores de conexión de red o caídas del servidor de TextBee
            Log::error("TextBee Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'No se pudo establecer conexión con el servidor de TextBee: ' . $e->getMessage()
            ];
        }
    }
}
