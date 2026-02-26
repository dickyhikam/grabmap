<?php

namespace App\Http\Controllers;

use App\Models\PricingCategory;
use App\Models\PricingItem;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        $categories = PricingCategory::with('items')
            ->orderBy('sort_order')
            ->get();

        return view('pricing.index', compact('categories'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'volume' => 'required|integer|min:0|max:10000000',
        ]);

        $volume = $request->input('volume');
        $categories = PricingCategory::with('items')->orderBy('sort_order')->get();

        $results = [];
        foreach ($categories as $category) {
            $categoryResult = [
                'name' => $category->name,
                'slug' => $category->slug,
                'items' => [],
            ];

            foreach ($category->items as $item) {
                $thousandUnits = $volume / 1000;

                $alsCost = $item->als_price !== null
                    ? round($thousandUnits * (float) $item->als_price, 2)
                    : null;

                $googleCost = null;
                if ($item->google_price !== null) {
                    $billableVolume = max(0, $volume - $item->google_free_threshold);
                    $googleCost = round(($billableVolume / 1000) * (float) $item->google_price, 2);
                }

                $savings = null;
                if ($alsCost !== null && $googleCost !== null && $googleCost > 0) {
                    $savings = round((1 - ($alsCost / $googleCost)) * 100, 1);
                }

                $categoryResult['items'][] = [
                    'id' => $item->id,
                    'api_name' => $item->api_name,
                    'tier' => $item->tier,
                    'als_cost' => $alsCost,
                    'google_cost' => $googleCost,
                    'savings_percent' => $savings,
                    'als_only' => $item->als_only,
                ];
            }

            $results[] = $categoryResult;
        }

        return response()->json([
            'volume' => $volume,
            'results' => $results,
        ]);
    }

    // ---- Admin CRUD ----

    public function adminIndex()
    {
        $categories = PricingCategory::with('items')->orderBy('sort_order')->get();
        return view('pricing.admin', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pricing_category_id' => 'required|exists:pricing_categories,id',
            'api_name' => 'required|string|max:255',
            'tier' => 'nullable|string|max:50',
            'als_price' => 'nullable|numeric|min:0',
            'google_price' => 'nullable|numeric|min:0',
            'google_free_threshold' => 'nullable|integer|min:0',
            'als_only' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['als_only'] = $request->boolean('als_only');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        PricingItem::create($validated);

        return redirect()->route('pricing.admin')->with('success', 'Pricing item added.');
    }

    public function update(Request $request, PricingItem $item)
    {
        $validated = $request->validate([
            'pricing_category_id' => 'required|exists:pricing_categories,id',
            'api_name' => 'required|string|max:255',
            'tier' => 'nullable|string|max:50',
            'als_price' => 'nullable|numeric|min:0',
            'google_price' => 'nullable|numeric|min:0',
            'google_free_threshold' => 'nullable|integer|min:0',
            'als_only' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['als_only'] = $request->boolean('als_only');

        $item->update($validated);

        return redirect()->route('pricing.admin')->with('success', 'Pricing item updated.');
    }

    public function destroy(PricingItem $item)
    {
        $item->delete();
        return redirect()->route('pricing.admin')->with('success', 'Pricing item deleted.');
    }
}
