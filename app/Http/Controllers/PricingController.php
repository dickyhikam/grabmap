<?php

namespace App\Http\Controllers;

use App\Models\PricingCategory;
use App\Models\PricingItem;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    protected function getItemTranslationKey(string $apiName, ?string $tier): ?string
    {
        $map = config('pricing_translations', []);
        if (! isset($map[$apiName])) {
            return null;
        }
        $tiers = $map[$apiName];
        return $tiers[$tier ?? '*'] ?? $tiers['*'] ?? null;
    }

    public function index(Request $request)
    {
        $supportedLocales = array_keys(config('pricing_locales', ['en' => [], 'id' => []]));
        $lang = $request->get('lang');
        if (in_array($lang, $supportedLocales)) {
            app()->setLocale($lang);
            session(['pricing_lang' => $lang]);
        } elseif (session('pricing_lang')) {
            app()->setLocale(session('pricing_lang'));
        }

        $categories = PricingCategory::with('items')
            ->orderBy('sort_order')
            ->get();

        foreach ($categories as $cat) {
            $cat->name_translated = __('pricing.categories.' . $cat->slug . '.name');
            $cat->description_translated = __('pricing.categories.' . $cat->slug . '.description');
            foreach ($cat->items as $item) {
                $key = $this->getItemTranslationKey($item->api_name, $item->tier);
                $item->api_name_translated = $key ? __('pricing.items.' . $key . '.name') : $item->api_name;
                $item->description_translated = $key ? __('pricing.items.' . $key . '.description') : $item->description;
            }
        }

        // Mata uang lokal untuk locale yang sedang dipakai. Kursnya hanya nilai
        // awal — pengunjung boleh menggantinya di halaman. Khusus IDR diambil
        // dari kurs aktif modul tagihan supaya tidak beda dengan invoice.
        $currency = config('pricing_currencies.' . app()->getLocale());
        if ($currency && $currency['code'] === 'IDR') {
            $rate = \App\Models\ExchangeRate::current();
            if ($rate) {
                $currency['rate'] = (float) $rate->rate;
            }
        }

        return view('pricing.index', compact('categories', 'currency'));
    }

    public function calculate(Request $request)
    {
        if (filter_var(env('PRICING_MAINTENANCE', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'error' => 'Pricing service under maintenance',
                'message' => 'The pricing comparison is temporarily disabled while we correct our data.',
            ], 503);
        }

        $request->validate([
            'volume' => 'required|integer|min:0|max:10000000',
        ]);

        if (session('pricing_lang')) {
            app()->setLocale(session('pricing_lang'));
        }

        $volume = $request->input('volume');
        $categories = PricingCategory::with('items')->orderBy('sort_order')->get();

        $results = [];
        foreach ($categories as $category) {
            $categoryResult = [
                'name' => __('pricing.categories.' . $category->slug . '.name'),
                'slug' => $category->slug,
                'items' => [],
            ];

            foreach ($category->items as $item) {
                // Tiered items: only apply the tier that matches the volume
                if ($item->tier_group !== null) {
                    $inRange = $volume >= ($item->tier_min ?? 0)
                        && ($item->tier_max === null || $volume < $item->tier_max);
                    if (! $inRange) {
                        $key = $this->getItemTranslationKey($item->api_name, $item->tier);
                        $apiNameTranslated = $key ? __('pricing.items.' . $key . '.name') : $item->api_name;
                        $categoryResult['items'][] = [
                            'id' => $item->id,
                            'api_name' => $apiNameTranslated,
                            'tier' => $item->tier,
                            'tier_group' => $item->tier_group,
                            'als_cost' => null,
                            'google_cost' => null,
                            'savings_percent' => null,
                            'als_only' => $item->als_only,
                        ];
                        continue;
                    }
                }

                $thousandUnits = $volume / 1000;

                $alsCost = $item->als_price !== null
                    ? round($thousandUnits * (float) $item->als_price, 2)
                    : null;

                $googleCost = null;
                if ($item->google_price !== null) {
                    // Kuota gratis Google sengaja tidak dipotong: sisi ALS ditagih dari
                    // request pertama, jadi kalau satu sisi dapat potongan kuota,
                    // perbandingannya jadi timpang dan baris yang volumenya masih di
                    // bawah kuota terlihat seperti tidak terhitung sama sekali.
                    $googleCost = round(($volume / 1000) * (float) $item->google_price, 2);
                }

                $savings = null;
                if ($alsCost !== null && $googleCost !== null && $googleCost > 0) {
                    $savings = round((1 - ($alsCost / $googleCost)) * 100, 1);
                }

                $key = $this->getItemTranslationKey($item->api_name, $item->tier);
                $apiNameTranslated = $key ? __('pricing.items.' . $key . '.name') : $item->api_name;
                $categoryResult['items'][] = [
                    'id' => $item->id,
                    'api_name' => $apiNameTranslated,
                    'tier' => $item->tier,
                    'tier_group' => $item->tier_group,
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
            'tier_group' => 'nullable|string|max:80',
            'tier_min' => 'nullable|integer|min:0',
            'tier_max' => 'nullable|integer|min:0',
            'als_price' => 'nullable|numeric|min:0',
            'google_price' => 'nullable|numeric|min:0',
            'google_free_threshold' => 'nullable|integer|min:0',
            'als_only' => 'sometimes|boolean',
            'is_recommended' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['als_only'] = $request->boolean('als_only');
        $validated['is_recommended'] = $request->boolean('is_recommended');
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
            'tier_group' => 'nullable|string|max:80',
            'tier_min' => 'nullable|integer|min:0',
            'tier_max' => 'nullable|integer|min:0',
            'als_price' => 'nullable|numeric|min:0',
            'google_price' => 'nullable|numeric|min:0',
            'google_free_threshold' => 'nullable|integer|min:0',
            'als_only' => 'sometimes|boolean',
            'is_recommended' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['als_only'] = $request->boolean('als_only');
        $validated['is_recommended'] = $request->boolean('is_recommended');

        $item->update($validated);

        return redirect()->route('pricing.admin')->with('success', 'Pricing item updated.');
    }

    public function destroy(PricingItem $item)
    {
        $item->delete();
        return redirect()->route('pricing.admin')->with('success', 'Pricing item deleted.');
    }
}
