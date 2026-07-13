<?php

namespace App\Http\Controllers;

use App\Models\RouteLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Shareable A→B route links — open inside the full home map.
 *  - GET  /route?from=lat,lng&to=lat,lng&mode=Car  → coordinate link (#1: snapshot, no geocode on open)
 *  - GET  /r/{code}                                → short link (#3), ETA recomputed live on every open
 *  - POST /api/route-links                         → create a short link from coordinates
 *
 * Both GET routes render home.index with a `routePreset`; the home page auto-places the
 * A/B markers and runs its existing calculateRoute(), so the route (line + distance + live
 * ETA + alternatives) is drawn with the same UI as the main map. Coordinates travel inside
 * the URL — the page never geocodes on open.
 */
class RouteLinkController extends Controller
{
    private array $allowedModes = ['Car', 'Truck', 'Pedestrian', 'Scooter'];

    public function show(Request $request)
    {
        $from = $this->parseLatLng($request->query('from'));
        $to   = $this->parseLatLng($request->query('to'));

        if (!$from || !$to) {
            // Malformed link → land on the normal map instead of an error page.
            return redirect('/');
        }

        return view('home.index', [
            'routePreset' => [
                'from'      => $from,
                'to'        => $to,
                'mode'      => $this->normalizeMode($request->query('mode')),
                'fromLabel' => $this->clean($request->query('fromLabel')),
                'toLabel'   => $this->clean($request->query('toLabel')),
                'code'      => null,
            ],
        ]);
    }

    public function showShort(string $code)
    {
        $link = RouteLink::where('code', $code)->first();

        if (!$link) {
            return redirect('/');
        }

        $link->increment('opens');

        return view('home.index', [
            'routePreset' => [
                'from'      => ['lat' => (float) $link->from_lat, 'lng' => (float) $link->from_lng],
                'to'        => ['lat' => (float) $link->to_lat,   'lng' => (float) $link->to_lng],
                'mode'      => $link->mode,
                'fromLabel' => $link->from_label,
                'toLabel'   => $link->to_label,
                'code'      => $link->code,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $from = $this->parseLatLng($request->input('from'));
        $to   = $this->parseLatLng($request->input('to'));

        if (!$from || !$to) {
            return response()->json(['error' => 'Koordinat from/to tidak valid.'], 422);
        }

        $link = RouteLink::create([
            'code'       => $this->uniqueCode(),
            'from_lat'   => $from['lat'],
            'from_lng'   => $from['lng'],
            'to_lat'     => $to['lat'],
            'to_lng'     => $to['lng'],
            'mode'       => $this->normalizeMode($request->input('mode')),
            'from_label' => $this->clean($request->input('fromLabel')),
            'to_label'   => $this->clean($request->input('toLabel')),
        ]);

        return response()->json([
            'code' => $link->code,
            'url'  => url('/r/' . $link->code),
        ], 201);
    }

    /** Accepts "lat,lng" string or [lat, lng] array. Returns ['lat'=>, 'lng'=>] or null. */
    private function parseLatLng($val): ?array
    {
        $lat = $lng = null;

        if (is_string($val) && str_contains($val, ',')) {
            [$a, $b] = array_map('trim', explode(',', $val, 2));
            $lat = is_numeric($a) ? (float) $a : null;
            $lng = is_numeric($b) ? (float) $b : null;
        } elseif (is_array($val) && isset($val[0], $val[1])) {
            $lat = is_numeric($val[0]) ? (float) $val[0] : null;
            $lng = is_numeric($val[1]) ? (float) $val[1] : null;
        }

        if ($lat === null || $lng === null) {
            return null;
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function normalizeMode(?string $mode): string
    {
        $mode = ucfirst(strtolower((string) $mode));

        return in_array($mode, $this->allowedModes, true) ? $mode : 'Car';
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (RouteLink::where('code', $code)->exists());

        return $code;
    }

    private function clean($s): ?string
    {
        if (!is_string($s)) {
            return null;
        }
        $s = trim($s);

        return $s === '' ? null : mb_substr($s, 0, 120);
    }
}
