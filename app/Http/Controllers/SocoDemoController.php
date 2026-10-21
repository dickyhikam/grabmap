<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\PricingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Demo SOCO (review tempat) — dua jalur Places: Amazon Location dan API GrabMaps langsung.
 * Semua proxy meneruskan permintaan apa adanya; API key tetap di server.
 */
class SocoDemoController extends Controller
{
    private const AWS_OPS = ['suggest', 'search-text'];
    private const GRAB_OPS = ['autocomplete', 'search-text', 'search-nearby'];

    public function index()
    {
        // Harga per 1.000 request diambil dari katalog /pricing supaya angkanya tidak dobel sumber.
        $items = PricingItem::whereIn('api_name', [
            'Autocomplete, Suggest',
            'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby',
        ])->get();

        $prices = ['Suggest' => null, 'Core' => null, 'Advanced' => null, 'Stored' => null];
        foreach ($items as $item) {
            $prices[$item->tier ?: 'Suggest'] = (float) $item->als_price;
        }

        return view('soco.index', [
            'prices'  => $prices,
            'rate'    => (float) (ExchangeRate::current()?->rate ?? 0),
            'hasGrab' => filled(config('services.grabmaps.api_key')),
        ]);
    }

    public function aws(Request $request, string $op)
    {
        abort_unless(in_array($op, self::AWS_OPS, true), 404);

        $region = config('services.aws.region');
        $url = "https://places.geo.{$region}.amazonaws.com/v2/{$op}?key=" . config('services.aws.api_key');

        try {
            return $this->relay(Http::timeout(15)->post($url, $request->all()));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function awsPlace(Request $request, string $placeId)
    {
        $region = config('services.aws.region');
        $url = "https://places.geo.{$region}.amazonaws.com/v2/place/" . urlencode($placeId)
            . '?key=' . config('services.aws.api_key')
            . '&language=' . urlencode($request->query('language', 'id'));

        try {
            return $this->relay(Http::timeout(15)->get($url));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function grab(Request $request, string $op)
    {
        abort_unless(in_array($op, self::GRAB_OPS, true), 404);

        $key = config('services.grabmaps.api_key');
        if (blank($key)) {
            return response()->json(['error' => 'GRABMAPS_API_KEY is not set in .env'], 503);
        }

        try {
            return $this->relay(
                Http::timeout(15)->withToken($key)->get("https://maps.grab.com/api/v1/{$op}", $request->query())
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * AWS mengirim tier harga lewat header, bukan di badan respons — diteruskan supaya halaman bisa menampilkannya.
     */
    private function relay(\Illuminate\Http\Client\Response $response)
    {
        $out = response()->json($response->json(), $response->status());
        if ($bucket = $response->header('x-amz-geo-pricing-bucket')) {
            $out->header('X-Pricing-Bucket', $bucket);
        }

        return $out;
    }
}
