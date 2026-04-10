<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiKeyController extends Controller
{
    public function index()
    {
        $hasCredentials = AwsLocationService::hasCredentials();
        $keys = [];
        $error = null;

        if ($hasCredentials) {
            $service = new AwsLocationService();
            $result = $service->listApiKeys();
            $keys = $result['keys'];
            $error = $result['error'];
        }

        $companies = Company::latest()->get();

        // Build a map of which key is assigned to which company
        $assignedKeys = [];
        foreach ($companies as $company) {
            if ($company->aws_api_key) {
                $assignedKeys[$company->aws_api_key_name ?? ''] = $company;
            }
        }

        return view('admin.api-keys.index', compact('hasCredentials', 'keys', 'error', 'companies', 'assignedKeys'));
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'key_name'   => 'required|string',
            'company_id' => 'required|exists:companies,id',
        ]);

        $hasCredentials = AwsLocationService::hasCredentials();
        if (!$hasCredentials) {
            return back()->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $service = new AwsLocationService();
        $result = $service->describeKey($validated['key_name']);

        if ($result['error']) {
            return back()->with('error', 'Gagal mengambil detail key: ' . $result['error']);
        }

        $keyValue = $result['key']['key'] ?? null;
        if (!$keyValue) {
            return back()->with('error', 'API key value tidak ditemukan untuk key ini.');
        }

        $company = Company::findOrFail($validated['company_id']);
        $company->update([
            'aws_api_key'      => $keyValue,
            'aws_api_key_name' => $validated['key_name'],
            'aws_key_active'   => true,
        ]);

        return back()->with('success', "API Key \"{$validated['key_name']}\" berhasil di-assign ke {$company->name}.");
    }

    public function unassign(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $company->update([
            'aws_api_key'      => null,
            'aws_api_key_name' => null,
            'aws_key_active'   => true,
        ]);

        return back()->with('success', "API Key berhasil di-lepas dari {$company->name}.");
    }

    public function edit(string $keyName)
    {
        if (!AwsLocationService::hasCredentials()) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $service = new AwsLocationService();
        $result = $service->describeKey($keyName);

        if ($result['error']) {
            return redirect()->route('admin.api-keys.index')->with('error', 'Gagal mengambil detail key: ' . $result['error']);
        }

        $key = $result['key'];
        return view('admin.api-keys.edit', compact('key', 'keyName'));
    }

    public function update(Request $request, string $keyName)
    {
        if (!AwsLocationService::hasCredentials()) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:1000',
            'expiry_mode' => 'required|in:never,date,preset',
            'expire_date' => 'nullable|date_format:Y-m-d H:i|after:now',
            'preset_days' => 'nullable|integer|in:30,90,180,365',
        ]);

        $params = [
            'description'  => $validated['description'] ?? '',
            'force_update' => true,
        ];

        if ($validated['expiry_mode'] === 'never') {
            $params['no_expiry'] = true;
        } elseif ($validated['expiry_mode'] === 'date' && !empty($validated['expire_date'])) {
            $params['expire_time'] = \Carbon\Carbon::parse($validated['expire_date']);
        } elseif ($validated['expiry_mode'] === 'preset' && !empty($validated['preset_days'])) {
            $params['expire_time'] = now()->addDays((int) $validated['preset_days']);
        }

        $service = new AwsLocationService();
        $result = $service->updateKey($keyName, $params);

        if ($result['error']) {
            return back()->with('error', 'Gagal update API Key: ' . $result['error'])->withInput();
        }

        // Bust cache so usage page reloads fresh
        Cache::forget("aws_key_info:{$keyName}");

        return redirect()->route('admin.api-keys.index')->with('success', "API Key \"{$keyName}\" berhasil diperbarui.");
    }

    public function usage(Request $request, string $keyName)
    {
        if (!AwsLocationService::hasCredentials()) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $startDate = $request->query('start', now()->subDays(29)->format('Y-m-d'));
        $endDate = $request->query('end', now()->format('Y-m-d'));

        // Validate dates
        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            if ($end->lt($start)) {
                $startDate = now()->subDays(29)->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
            }
        } catch (\Exception $e) {
            $startDate = now()->subDays(29)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
        }

        $days = $start->diffInDays($end) + 1;

        $filterOperation = $request->query('operation');
        $operations = AwsLocationService::getOperations();
        if ($filterOperation && !in_array($filterOperation, $operations)) {
            $filterOperation = null;
        }

        $refresh = $request->query('refresh') === '1';
        $cacheTtl = 30 * 60; // 30 minutes

        $service = new AwsLocationService();

        // Cache key info
        $keyInfoCacheKey = "aws_key_info:{$keyName}";
        if ($refresh) {
            Cache::forget($keyInfoCacheKey);
        }
        $keyResult = Cache::remember($keyInfoCacheKey, $cacheTtl, fn() => $service->describeKey($keyName));
        $keyInfo = $keyResult['key'];
        $keyError = $keyResult['error'];

        // Cache metrics
        $metricsCacheKey = "aws_key_metrics:{$keyName}:{$startDate}:{$endDate}:" . ($filterOperation ?? 'all');
        if ($refresh) {
            Cache::forget($metricsCacheKey);
        }
        $metrics = Cache::remember($metricsCacheKey, $cacheTtl, fn() => $service->getKeyUsageMetrics($keyName, $startDate, $endDate, $filterOperation));

        $assignedCompany = Company::where('aws_api_key_name', $keyName)->first();
        $isCached = !$refresh;

        return view('admin.api-keys.usage', compact(
            'keyName', 'keyInfo', 'keyError', 'metrics', 'assignedCompany',
            'startDate', 'endDate', 'days', 'filterOperation', 'operations', 'isCached'
        ));
    }
}
