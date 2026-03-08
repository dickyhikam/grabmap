<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
            'name'     => 'required|string|max:255',
            'slug'     => 'required|string|max:100|unique:companies,slug|regex:/^[a-z0-9\-]+$/',
            'logo'     => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'=> 'nullable|boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('logos'), $filename);
            $logoPath = 'logos/' . $filename;
        }

        $company = Company::create([
            'name'      => $validated['name'],
            'slug'      => $validated['slug'],
            'logo_path' => $logoPath,
            'is_active' => $request->boolean('is_active', true),
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
            'name'     => 'required|string|max:255',
            'slug'     => "required|string|max:100|unique:companies,slug,{$company->id}|regex:/^[a-z0-9\-]+$/",
            'logo'     => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'=> 'nullable|boolean',
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

        $company->update([
            'name'      => $validated['name'],
            'slug'      => $validated['slug'],
            'logo_path' => $logoPath,
            'is_active' => $request->boolean('is_active', true),
        ]);

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
}
