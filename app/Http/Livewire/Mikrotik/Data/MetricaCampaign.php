<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\AdvertisingCampaign;
use App\Models\CampaignResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MetricaCampaign extends Component
{
    public $aliadoId;
    public $campaignId;
    public $stats;
    public $isAdmin = false;

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
        if (!$this->isAdmin) {
            $this->aliadoId = Auth::id();
        }
    }

    public function updatedAliadoId()
    {
        $this->campaignId = null;
        $this->stats = null;
    }

    public function updatedCampaignId($value)
    {
        if (!$value) {
            $this->stats = null;
            return;
        }

        $campaign = AdvertisingCampaign::find($value);
        if (!$campaign) return;

        $responses = CampaignResponse::where('campaign_id', $value)->get();

        $data = [
            'total' => $responses->count(),
            'question' => $campaign->question_text,
            'type' => $campaign->question_type,
            'labels' => [],
            'values' => [],
        ];

        if ($campaign->question_type !== 'simple' && !empty($campaign->options)) {
            $counts = [];
            foreach ($campaign->options as $option) {
                $counts[$option] = 0;
            }

            foreach ($responses as $response) {
                $answer = $response->answer;
                // Validamos si la respuesta es JSON (selección múltiple) o string simple
                $decoded = json_decode($answer, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $ans) {
                        if (isset($counts[$ans])) $counts[$ans]++;
                    }
                } else {
                    if (isset($counts[$answer])) $counts[$answer]++;
                }
            }

            $data['labels'] = array_keys($counts);
            $data['values'] = array_values($counts);
        }

        $this->stats = $data;
        $this->dispatchBrowserEvent('updateChart', $data);
    }

    public function render()
    {
        $aliados = $this->isAdmin ? User::where('role', 'aliado')->get() : [];
        
        $campaigns = collect();
        if ($this->aliadoId) {
            $campaigns = AdvertisingCampaign::where('user_id', $this->aliadoId)->get();
        }

        return view('livewire.mikrotik.data.metrica-campaign', [
            'aliados' => $aliados,
            'campaigns' => $campaigns
        ])->layout('layouts.app');
    }
}