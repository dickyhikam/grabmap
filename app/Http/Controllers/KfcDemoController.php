<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Studi kasus KFC, layanan HMD (home delivery): pencarian alamat yang dipengaruhi posisi user.
 *
 * Klien melaporkan "Jl. swakarsa no.4" dari Jakarta Pusat memunculkan
 * "No. 4C" di Jakarta Timur di atas "No. 4" di Bekasi. Halaman ini
 * membandingkan Suggest dan SearchText dengan BiasPosition (GPS user) versus
 * BoundingBox area layanan, lalu menunjukkan urut ulang berdasarkan nomor
 * rumah yang persis sama. Semua panggilan lewat server supaya API key aman.
 */
class KfcDemoController extends Controller
{
    /** Area layanan yang boleh dipilih halaman, [minLng, minLat, maxLng, maxLat]. */
    public const AREAS = [
        'jabodetabek' => [106.4, -6.8, 107.3, -5.9],
        'jakarta'     => [106.68, -6.38, 106.98, -6.08],
        'indonesia'   => [95.0, -11.0, 141.0, 6.0],
    ];

    public function index()
    {
        return response()
            ->view('kfc.index', ['areas' => self::AREAS])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'q'    => ['required', 'string', 'max:200'],
            'api'  => ['required', 'in:suggest,text'],
            'mode' => ['required', 'in:bias,bbox'],
            'area' => ['nullable', 'in:' . implode(',', array_keys(self::AREAS))],
            'lat'  => ['required_if:mode,bias', 'nullable', 'numeric', 'between:-90,90'],
            'lng'  => ['required_if:mode,bias', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        $body = ['QueryText' => $data['q'], 'MaxResults' => 6, 'Language' => 'id'];
        if ($data['mode'] === 'bias') {
            $body['BiasPosition'] = [round((float) $data['lng'], 6), round((float) $data['lat'], 6)];
            $body['Filter'] = ['IncludeCountries' => ['IDN']];
        } else {
            $body['Filter'] = ['BoundingBox' => self::AREAS[$data['area'] ?? 'jabodetabek']];
        }
        // Tanpa "Core", Suggest hanya membawa label alamat, tidak ada koordinat.
        if ($data['api'] === 'suggest') {
            $body['AdditionalFeatures'] = ['Core'];
        }

        $region = config('services.aws.region');
        $op = $data['api'] === 'suggest' ? 'suggest' : 'search-text';
        $url = "https://places.geo.{$region}.amazonaws.com/v2/{$op}?key=" . config('services.aws.api_key');

        try {
            $res = Http::timeout(15)->post($url, $body);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
        if ($res->failed()) {
            return response()->json([
                'error' => $res->json('message') ?: $res->json('Message') ?: "AWS {$res->status()}",
                'request' => $body,
            ], $res->status());
        }

        $items = collect($res->json('ResultItems') ?? [])
            ->map(fn ($r) => $data['api'] === 'suggest' ? (($r['Place'] ?? []) + ['Title' => $r['Title'] ?? '']) : $r)
            ->filter(fn ($p) => isset($p['Position']))
            ->map(fn ($p) => $this->normalize($p))
            ->values();

        return response()->json([
            'op'      => $op === 'suggest' ? 'Suggest' : 'SearchText',
            'bucket'  => $res->header('x-amz-geo-pricing-bucket') ?: null,
            'request' => $body,
            'places'  => $items,
        ]);
    }

    private function normalize(array $p): array
    {
        $a = $p['Address'] ?? [];
        $label = $a['Label'] ?? '';

        // Kota/kabupaten hanya ada di dalam Label, tidak sebagai komponen Address.
        $city = null;
        foreach (array_map('trim', explode(',', $label)) as $part) {
            if (preg_match('/^(Kota|Kabupaten|Kab\.)\s/i', $part)) {
                $city = $part;
                break;
            }
        }

        return [
            'id'          => $p['PlaceId'] ?? null,
            'name'        => $p['Title'] ?? '',
            'label'       => $label,
            'street'      => $a['Street'] ?? null,
            'number'      => $a['AddressNumber'] ?? null,
            'subdistrict' => $a['SubDistrict'] ?? null,
            'district'    => $a['District'] ?? null,
            'city'        => $city ?? ($a['Locality'] ?? null),
            'type'        => $p['PlaceType'] ?? null,
            'distance'    => $p['Distance'] ?? null,
            'lat'         => $p['Position'][1],
            'lng'         => $p['Position'][0],
        ];
    }
}
