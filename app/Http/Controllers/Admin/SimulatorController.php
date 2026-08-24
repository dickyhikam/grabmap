<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Services\AwsLocationService;

/**
 * Simulator biaya AWS Location.
 *
 * Halaman ini sengaja memakai API key yang diketik user (bukan key dari .env) dan
 * menembak AWS langsung dari browser, supaya jumlah request yang terhitung persis
 * sama dengan yang ditagih AWS — tanpa distorsi dari proxy Laravel.
 */
class SimulatorController extends Controller
{
    public function index()
    {
        $rate = ExchangeRate::current();

        return view('admin.simulator.index', [
            'region'   => config('services.aws.region', 'ap-southeast-1'),
            'pricing'  => AwsLocationService::PRICING,
            'taxRate'  => (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11)),
            'usdRate'  => $rate ? (float) $rate->rate : 16000.0,
            'rateDate' => $rate?->rate_date?->format('d M Y'),
        ]);
    }
}
