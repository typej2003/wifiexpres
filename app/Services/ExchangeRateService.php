<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    /**
     * Obtiene la tasa del BCV dinámicamente desde Settings o API.
     */
    public static function getBcvRate()
    {
        // 1. Intentamos obtener la configuración desde la Caché para no saturar la DB
        $settings = Cache::remember('setting', 3600, function() {
            return Setting::first(); 
        });

        // Valores por defecto si no existe registro en la tabla settings
        $apiActive = ($settings->api_bcv ?? 'NO') === 'SI';
        $manualRate = (float) ($settings->dollar_rate ?? 36.00);

        // 2. Si el switch en la DB está en 'NO', devolvemos el valor manual del formulario
        if (!$apiActive) {
            return $manualRate;
        }

        // 3. Si está en 'SI', intentamos consultar la API externa
        try {
            // Usamos un timeout corto de 3 segundos
            $response = Http::timeout(3)->get('https://pydolarvenezuela-api.vercel.app/api/v1/dollar/unit/bcv');

            if ($response->successful()) {
                $data = $response->json();
                
                // Verificamos que la estructura de la API sea la esperada
                if (isset($data['price'])) {
                    return (float) $data['price'];
                }
            }

            // Si la API falla o la estructura cambia, usamos el manual de respaldo
            return $manualRate;

        } catch (\Exception $e) {
            // Si no hay internet o la API cae, registramos el aviso y usamos el manual
            Log::warning("Servicio BCV: API no disponible, usando tasa manual de respaldo ({$manualRate}). Motivo: " . $e->getMessage());
            return $manualRate;
        }
    }

    /**
     * Método de utilidad para convertir montos de USD a Bs
     */
    public static function convertToBs($amountUsd)
    {
        return $amountUsd * self::getBcvRate();
    }
}