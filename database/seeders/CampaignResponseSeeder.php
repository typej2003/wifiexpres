<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CampaignResponse;
use App\Models\AdvertisingCampaign;
use App\Models\UserMikrotik;

class CampaignResponseSeeder extends Seeder
{
    public function run()
    {
        // Intentamos localizar la campaña específica por ID o por nombre e identidad
        $campaign = AdvertisingCampaign::where('id', 1)
            ->orWhere(function($q) {
                $q->where('name', 'Solo para ellas')
                  ->where('router_identity', 'wifiExpres-hotspot');
            })->first();

        if (!$campaign) {
            $this->command->error("No se encontró la campaña 'Solo para ellas'. Asegúrate de que exista antes de ejecutar el seeder.");
            return;
        }

        // Obtenemos un pool de usuarios (priorizando mujeres dado el contexto de la campaña)
        $users = UserMikrotik::where('gender', 'F')->inRandomOrder()->limit(20)->get();

        // Si no hay suficientes mujeres en la DB, completamos con otros usuarios para llegar a 20
        if ($users->count() < 20) {
            $needed = 20 - $users->count();
            $extra = UserMikrotik::where('gender', '!=', 'F')->inRandomOrder()->limit($needed)->get();
            $users = $users->concat($extra);
        }

        if ($users->isEmpty()) {
            $this->command->error("No hay usuarios en UserMikrotik para generar respuestas. Ejecuta primero TicketLogSeeder.");
            return;
        }

        $options = $campaign->options ?? ["Buena", "Mala"];

        foreach ($users as $user) {
            CampaignResponse::create([
                'campaign_id'            => $campaign->id,
                'user_mikrotik_id'       => $user->id,
                'mac_address'            => $user->macaddress,
                'router_identity'        => $campaign->router_identity,
                'answer'                 => is_array($options) ? $options[array_rand($options)] : $options,
                'campaign_name'          => $campaign->name,
                'campaign_description'   => $campaign->description,
                'campaign_target_gender' => $campaign->target_gender,
                'campaign_age_range_id'  => $campaign->age_range_id,
                'campaign_media_type'    => $campaign->media_type,
                'campaign_media_path'    => $campaign->media_path,
                'campaign_question_text' => $campaign->question_text,
                'campaign_question_type' => $campaign->question_type,
                'campaign_options'       => $campaign->options,
            ]);
        }

        $this->command->info("Se han generado " . $users->count() . " respuestas para la campaña: " . $campaign->name);
    }
}