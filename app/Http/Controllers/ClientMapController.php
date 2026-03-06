<?php

namespace App\Http\Controllers;

use App\Models\Company;

class ClientMapController extends Controller
{
    public function show(string $slug)
    {
        $company = Company::with('features')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $features = $company->getFeaturesMap();

        return view('client.map', compact('company', 'features'));
    }
}
