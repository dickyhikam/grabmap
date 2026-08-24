<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKeyBudget;
use App\Models\ExchangeRate;
use App\Models\Setting;
use Illuminate\Http\Request;

class CostSettingController extends Controller
{
    public function index()
    {
        $rates = ExchangeRate::orderByDesc('rate_date')->orderByDesc('id')->paginate(20);
        $activeRate = ExchangeRate::current();
        $taxPercent = round(((float) Setting::get('tax_rate', config('aws.tax_rate', 0.11))) * 100, 2);
        $budgetAlert = (float) Setting::get('budget_alert_usd', 170);

        // Batas per API key hanya dibaca dari tabel lokal — halaman ini tidak
        // menembak AWS sama sekali. Pengaturannya sendiri ada di formulir API key.
        $keyBudgets = ApiKeyBudget::with('awsAccount')
            ->orderByDesc('limit_usd')
            ->get();

        return view('admin.cost-settings.index', compact('rates', 'activeRate', 'taxPercent', 'budgetAlert', 'keyBudgets'));
    }

    /**
     * Ubah angka bergaya Indonesia ("16.500" / "16.512,45") jadi angka yang
     * dimengerti validator. Titik dianggap pemisah ribuan hanya kalau memang
     * memisah kelompok tiga angka — kalau tidak ("16500.75"), titiknya dibaca
     * sebagai koma desimal, jadi ketikan gaya Inggris pun tidak jadi salah.
     */
    private static function normalizeNumber(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $value;
        }

        $hasComma = str_contains($value, ',');

        if ($hasComma) {
            // Koma = desimal, jadi titik pasti pemisah ribuan.
            return str_replace(',', '.', str_replace('.', '', $value));
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            return str_replace('.', '', $value);       // 16.500 → 16500
        }

        return $value;                                  // sudah polos, biarkan
    }

    public function storeRate(Request $request)
    {
        $request->merge(['rate' => self::normalizeNumber($request->input('rate'))]);

        $data = $request->validate([
            'rate'      => ['required', 'numeric', 'min:1', 'max:1000000'],
            'rate_date' => ['required', 'date'],
            'source'    => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note'      => ['nullable', 'string', 'max:1000'],
        ]);

        $rate = ExchangeRate::create([
            'rate'       => $data['rate'],
            'rate_date'  => $data['rate_date'],
            'source'     => $data['source'],
            'reference'  => $data['reference'] ?? null,
            'note'       => $data['note'] ?? null,
            'is_active'  => false,
            'created_by' => optional($request->user())->name ?? 'admin',
        ]);

        // Default: kurs baru langsung dijadikan aktif (kecuali dicentang tidak).
        if (!$request->boolean('keep_current_active')) {
            $rate->makeActive();
        }

        return redirect()->route('admin.cost-settings.index')
            ->with('success', 'Kurs Rp ' . number_format((float) $data['rate'], 0, ',', '.') . '/USD tersimpan.');
    }

    public function activate(ExchangeRate $rate)
    {
        $rate->makeActive();

        return redirect()->route('admin.cost-settings.index')
            ->with('success', 'Kurs Rp ' . number_format((float) $rate->rate, 0, ',', '.') . '/USD (' . $rate->rate_date->format('d M Y') . ') dijadikan aktif.');
    }

    public function updateTax(Request $request)
    {
        $data = $request->validate([
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::put('tax_rate', $data['tax_percent'] / 100, optional($request->user())->name ?? 'admin');

        return redirect()->route('admin.cost-settings.index')
            ->with('success', 'PPN diset ke ' . rtrim(rtrim(number_format($data['tax_percent'], 2), '0'), '.') . '%.');
    }

    public function updateBudget(Request $request)
    {
        $data = $request->validate([
            'budget_alert_usd' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        Setting::put('budget_alert_usd', $data['budget_alert_usd'], optional($request->user())->name ?? 'admin');

        return redirect()->route('admin.cost-settings.index')
            ->with('success', 'Ambang peringatan budget diset ke $' . number_format((float) $data['budget_alert_usd'], 2) . '.');
    }
}
