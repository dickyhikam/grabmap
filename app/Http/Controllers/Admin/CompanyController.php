<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    private const FEATURES = ['search', 'route', 'reverse_geocode', 'route_matrix'];

    public function index()
    {
        $companies = Company::withCount('features')->latest()->get();
        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:companies,slug|regex:/^[a-z0-9\-]+$/',
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'    => 'nullable|boolean',
            'aws_api_key'  => 'nullable|string|max:1000',
            'aws_key_active' => 'nullable|boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('logos'), $filename);
            $logoPath = 'logos/' . $filename;
        }

        $apiKey = $request->input('aws_api_key');

        $company = Company::create([
            'name'           => $validated['name'],
            'slug'           => $validated['slug'],
            'logo_path'      => $logoPath,
            'is_active'      => $request->boolean('is_active', true),
            'aws_api_key'    => $apiKey && $apiKey !== '********' ? $apiKey : null,
            'aws_key_active' => $request->boolean('aws_key_active', true),
        ]);

        $enabledFeatures = $request->input('features', []);
        $featureSettings = $request->input('feature_settings', []);
        foreach (self::FEATURES as $key) {
            $company->features()->create([
                'feature_key' => $key,
                'is_enabled'  => in_array($key, $enabledFeatures),
                'settings'    => $featureSettings[$key] ?? null,
            ]);
        }

        return redirect()->route('admin.companies.index')
            ->with('success', "Company \"{$company->name}\" berhasil dibuat.");
    }

    public function edit(Company $company)
    {
        $company->load('features');
        return view('admin.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'slug'         => "required|string|max:100|unique:companies,slug,{$company->id}|regex:/^[a-z0-9\-]+$/",
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'    => 'nullable|boolean',
            'aws_api_key'  => 'nullable|string|max:1000',
            'aws_key_active' => 'nullable|boolean',
        ]);

        $logoPath = $company->logo_path;
        if ($request->hasFile('logo')) {
            if ($company->logo_path && file_exists(public_path($company->logo_path))) {
                unlink(public_path($company->logo_path));
            }
            $file = $request->file('logo');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('logos'), $filename);
            $logoPath = 'logos/' . $filename;
        }

        $updateData = [
            'name'           => $validated['name'],
            'slug'           => $validated['slug'],
            'logo_path'      => $logoPath,
            'is_active'      => $request->boolean('is_active', true),
            'aws_key_active' => $request->boolean('aws_key_active', true),
        ];

        $apiKeyInput = $request->input('aws_api_key');
        if ($apiKeyInput && $apiKeyInput !== '********') {
            $updateData['aws_api_key'] = $apiKeyInput;
        } elseif ($apiKeyInput === '' || $apiKeyInput === null) {
            $updateData['aws_api_key'] = null;
        }

        $company->update($updateData);

        $enabledFeatures = $request->input('features', []);
        $featureSettings = $request->input('feature_settings', []);
        foreach (self::FEATURES as $key) {
            $company->features()->updateOrCreate(
                ['feature_key' => $key],
                [
                    'is_enabled' => in_array($key, $enabledFeatures),
                    'settings'   => $featureSettings[$key] ?? null,
                ]
            );
        }

        return redirect()->route('admin.companies.index')
            ->with('success', "Company \"{$company->name}\" berhasil diperbarui.");
    }

    public function toggleStatus(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Company \"{$company->name}\" berhasil {$status}.");
    }

    public function usage(Request $request, Company $company)
    {
        $keyName = $company->aws_api_key_name;

        // Default rentang: bulan berjalan (selaras dengan siklus tagihan AWS).
        $startDate = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->query('end', now()->format('Y-m-d'));
        $refresh   = $request->query('refresh') === '1';

        $metrics   = ['total' => 0, 'daily' => [], 'operations' => [], 'error' => null];
        $fetchedAt = null;

        // Ambil dari CloudWatch (data AWS asli) lewat API key milik company — model manual refresh.
        if ($keyName) {
            $service  = new AwsLocationService();
            $snapshot = $service->getCachedUsage($keyName, $startDate, $endDate, null, $refresh);
            $metrics  = $snapshot['metrics'];
            $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;
        }

        $totalCost  = AwsLocationService::estimateCost($metrics['operations'] ?? []);
        $activeRate = ExchangeRate::current();
        $idrRate    = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate    = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));

        return view('admin.companies.usage', compact(
            'company', 'keyName', 'metrics', 'fetchedAt', 'totalCost',
            'idrRate', 'taxRate', 'activeRate', 'startDate', 'endDate'
        ));
    }

    /**
     * Dokumen tagihan (invoice/perincian) siap cetak / Simpan-PDF dari browser.
     * Memakai snapshot CloudWatch yang sama dengan halaman usage (tidak menembak AWS lagi).
     */
    public function invoice(Request $request, Company $company)
    {
        $keyName = $company->aws_api_key_name;

        $startDate = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->query('end', now()->format('Y-m-d'));

        $metrics = ['total' => 0, 'daily' => [], 'operations' => [], 'error' => null];
        $fetchedAt = null;

        if ($keyName) {
            $service  = new AwsLocationService();
            $snapshot = $service->getCachedUsage($keyName, $startDate, $endDate, null, false);
            $metrics  = $snapshot['metrics'];
            $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;
        }

        $operations = $metrics['operations'] ?? [];
        $subtotal   = AwsLocationService::estimateCost($operations);
        $activeRate = ExchangeRate::current();
        $idrRate    = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate    = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));
        $tax        = $subtotal * $taxRate;
        $grand      = $subtotal + $tax;

        // Nomor invoice deterministik dari periode + slug company.
        $invoiceNo = 'INV/' . \Carbon\Carbon::parse($endDate)->format('Ym') . '/' . strtoupper($company->slug);
        $issuedAt  = now();
        $backUrl   = route('admin.companies.usage', ['company' => $company, 'start' => $startDate, 'end' => $endDate]);

        return view('admin.companies.invoice', compact(
            'company', 'keyName', 'metrics', 'operations', 'fetchedAt',
            'subtotal', 'tax', 'grand', 'idrRate', 'taxRate', 'activeRate',
            'startDate', 'endDate', 'invoiceNo', 'issuedAt', 'backUrl'
        ));
    }
}
