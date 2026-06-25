<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * UI to generate a GTFS transfers.txt from the Working Sheet Excel.
 * The heavy lifting is done in Python (scripts/transfers/transfers_web.py);
 * this controller just takes the form -> runs python -> returns JSON.
 */
class TransfersToolController extends Controller
{
    private const SCRIPT  = 'scripts/transfers/transfers_web.py';
    private const TMP_DIR = 'transfers_tmp';

    public function index()
    {
        return view('tools.transfers');
    }

    /** Step 1: upload + settings -> list of candidate pairs (no API). */
    public function preview(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        $token = (string) Str::uuid();
        $rel   = $request->file('excel')->storeAs(self::TMP_DIR, $token . '.xlsx');
        $abs   = Storage::disk('local')->path($rel);

        $payload = array_merge($this->settings($request), [
            'action' => 'preview',
            'excel'  => $abs,
        ]);

        $out = $this->runPython($payload);
        if (!($out['ok'] ?? false)) {
            return response()->json($out, 422);
        }
        $out['file_token'] = $token;   // used by the generate step
        return response()->json($out);
    }

    /** Step 2: generate transfers.txt (uses AWS Routes v2). */
    public function generate(Request $request)
    {
        $request->validate([
            'file_token'    => 'required|uuid',
            'overrides'     => 'sometimes|array',
            'provider'      => 'sometimes|in:aws,geotools',
            'geotools_curl' => 'sometimes|nullable|string|max:80000',
        ]);

        $rel = self::TMP_DIR . '/' . $request->string('file_token') . '.xlsx';
        if (!Storage::disk('local')->exists($rel)) {
            return response()->json(['ok' => false, 'error' => 'Upload session expired, please re-upload the file.'], 422);
        }

        $token    = (string) $request->string('file_token');
        $provider = $request->input('provider', 'aws');

        $payload = array_merge($this->settings($request), [
            'action'        => 'generate',
            'excel'         => Storage::disk('local')->path($rel),
            // cache per provider so AWS/geo-tools durations don't mix
            'cache_path'    => Storage::disk('local')->path(self::TMP_DIR . '/' . $token . '.' . $provider . '.cache.json'),
            'overrides'     => $request->input('overrides', []),
            'provider'      => $provider,
            'geotools_curl' => $request->input('geotools_curl', ''),
            'region'        => config('services.aws.region'),
            'api_key'       => config('services.aws.api_key'),   // key stays server-side
        ]);

        return response()->json($this->runPython($payload, 240));
    }

    /** Collect settings from the form with their defaults. */
    private function settings(Request $request): array
    {
        return [
            'travel_mode'    => $request->input('travel_mode', 'Pedestrian'),
            'max_transfer_m' => (int)   $request->input('max_transfer_m', 500),
            'default_type'   => (int)   $request->input('default_type', 2),
            'max_walk_sec'   => (int)   $request->input('max_walk_sec', 900),
            'detour_factor'  => (float) $request->input('detour_factor', 3.0),
            'auto_type3'     => $request->boolean('auto_type3'),
            'min_floor_sec'  => (int)   $request->input('min_floor_sec', 30),
            'access_buffer_sec' => (int) $request->input('access_buffer_sec', 180),
        ];
    }

    /** Jalanin transfers_web.py, kirim payload via stdin, ambil JSON dari stdout. */
    private function runPython(array $payload, int $timeout = 120): array
    {
        $result = Process::path(base_path())
            ->timeout($timeout)
            ->input(json_encode($payload))
            ->run([$this->pythonBin(), base_path(self::SCRIPT)]);

        if ($result->failed()) {
            return ['ok' => false, 'error' => 'Failed to run Python: ' . trim($result->errorOutput() ?: $result->output())];
        }

        $decoded = json_decode($result->output(), true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Invalid Python output', 'raw' => mb_substr($result->output(), 0, 500)];
        }
        return $decoded;
    }

    /**
     * Resolve a Python interpreter that actually has pandas + openpyxl + requests.
     * A web server's PATH often differs from a login shell and resolves `python3`
     * to /usr/bin/python3 (which lacks openpyxl), so prefer known-good absolute
     * paths. Override with PYTHON_BIN in .env if your interpreter lives elsewhere.
     */
    private function pythonBin(): string
    {
        $configured = env('PYTHON_BIN');
        if ($configured && is_executable($configured)) {
            return $configured;
        }
        foreach (['/opt/homebrew/bin/python3', '/usr/local/bin/python3', '/usr/bin/python3'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }
        return 'python3';
    }
}
