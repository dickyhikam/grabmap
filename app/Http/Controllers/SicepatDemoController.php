<?php

namespace App\Http\Controllers;

use App\Console\Commands\ImportSicepatSample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Demo SiCepat: hasil uji akurasi geocoding mereka (titik hasil API vs titik POD
 * kurir) di atas peta GrabMaps, untuk internal. Datanya diimpor dari Excel
 * SiCepat dengan `php artisan demo:sicepat-import`. Isinya alamat pelanggan,
 * jadi halaman ditandai noindex supaya tidak masuk mesin pencari.
 */
class SicepatDemoController extends Controller
{
    public function index()
    {
        $raw = Storage::disk('local')->get(ImportSicepatSample::PATH);
        $data = $raw ? json_decode($raw, true) : null;

        return response()
            ->view('sicepat.index', [
                'rows'       => $data['rows'] ?? [],
                'source'     => $data['source'] ?? null,
                'importedAt' => !empty($data['imported_at']) ? \Carbon\Carbon::parse($data['imported_at']) : null,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Cari teks lewat Amazon Location SearchText (data GrabMaps lewat AWS),
     * condong ke titik yang dikirim halaman. Hasil diringkas jadi
     * {name, address, lat, lng}. API key tetap di server.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q'   => ['required', 'string', 'max:200'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $near = isset($data['lat'], $data['lng']) ? [(float) $data['lat'], (float) $data['lng']] : null;

        try {
            return $this->searchAws($data['q'], $near);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function searchAws(string $q, ?array $near)
    {
        $region = config('services.aws.region');
        $url = "https://places.geo.{$region}.amazonaws.com/v2/search-text?key=" . config('services.aws.api_key');

        $body = ['QueryText' => $q, 'MaxResults' => 5, 'Language' => 'id', 'Filter' => ['IncludeCountries' => ['IDN']]];
        if ($near) {
            $body['BiasPosition'] = [$near[1], $near[0]];
        }

        $res = Http::timeout(15)->post($url, $body);
        if ($res->failed()) {
            return response()->json(['error' => $res->json('message') ?: "AWS {$res->status()}"], $res->status());
        }

        return response()->json([
            'bucket'   => $res->header('x-amz-geo-pricing-bucket') ?: null,
            'places'   => collect($res->json('ResultItems') ?? [])
                ->filter(fn ($p) => isset($p['Position']))
                ->map(fn ($p) => [
                    'name'    => $p['Title'] ?? '',
                    'address' => $p['Address']['Label'] ?? '',
                    'type'    => $p['PlaceType'] ?? null,
                    'lat'     => $p['Position'][1],
                    'lng'     => $p['Position'][0],
                ])->values(),
        ]);
    }
}
