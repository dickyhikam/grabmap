<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwsAccount;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan akun AWS (kredensial IAM). Satu aplikasi bisa memegang beberapa akun —
 * misal akun demo milik sendiri dan akun milik klien — karena API key dan metrik
 * CloudWatch selalu terikat ke akun tempat key itu dibuat.
 */
class AwsAccountController extends Controller
{
    public function index()
    {
        $accounts = AwsAccount::withCount('companies')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $envConfigured = !empty(config('aws.credentials.key')) && !empty(config('aws.credentials.secret'));

        return view('admin.aws-accounts.index', compact('accounts', 'envConfigured'));
    }

    public function create()
    {
        return view('admin.aws-accounts.form', ['currentDefault' => $this->currentDefault()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAccount($request, null, true);

        $isFirst = AwsAccount::count() === 0;

        $account = AwsAccount::create([
            'name'              => $validated['name'],
            'account_number'    => $validated['account_number'] ?? null,
            'access_key_id'     => $validated['access_key_id'],
            'secret_access_key' => $validated['secret_access_key'],
            'region'            => $validated['region'],
            'is_active'         => $request->boolean('is_active', true),
            'is_default'        => false,
            'notes'             => $validated['notes'] ?? null,
        ]);

        // Akun pertama otomatis jadi default; selebihnya hanya kalau diminta.
        if ($isFirst || $request->boolean('is_default')) {
            $account->makeDefault();
        }

        return redirect()->route('admin.aws-accounts.index')
            ->with('success', "Akun AWS \"{$account->name}\" berhasil ditambahkan.");
    }

    public function edit(AwsAccount $awsAccount)
    {
        return view('admin.aws-accounts.form', [
            'account'        => $awsAccount,
            'currentDefault' => $this->currentDefault(),
        ]);
    }

    /** Akun yang saat ini bertanda default — dipakai untuk konfirmasi pemindahan. */
    private function currentDefault(): ?AwsAccount
    {
        return AwsAccount::query()->where('is_default', true)->first();
    }

    public function update(Request $request, AwsAccount $awsAccount)
    {
        $validated = $this->validateAccount($request, $awsAccount, false);

        $data = [
            'name'           => $validated['name'],
            'account_number' => $validated['account_number'] ?? null,
            'access_key_id'  => $validated['access_key_id'],
            'region'         => $validated['region'],
            'is_active'      => $request->boolean('is_active', true),
            'notes'          => $validated['notes'] ?? null,
        ];

        // Secret hanya ditimpa kalau diisi ulang — form menampilkannya kosong.
        if (!empty($validated['secret_access_key'])) {
            $data['secret_access_key'] = $validated['secret_access_key'];
            $data['last_verified_at']  = null;
        }

        $awsAccount->update($data);

        if ($request->boolean('is_default')) {
            $awsAccount->makeDefault();
        }

        return redirect()->route('admin.aws-accounts.index')
            ->with('success', "Akun AWS \"{$awsAccount->name}\" berhasil diperbarui.");
    }

    public function destroy(AwsAccount $awsAccount)
    {
        $companyCount = $awsAccount->companies()->count();
        if ($companyCount > 0) {
            return back()->with('error', "Akun \"{$awsAccount->name}\" masih dipakai {$companyCount} company. Pindahkan dulu company-nya sebelum menghapus.");
        }

        $wasDefault = $awsAccount->is_default;
        $name = $awsAccount->name;
        $awsAccount->delete();

        // Jangan sampai tidak ada akun default sama sekali.
        if ($wasDefault) {
            AwsAccount::defaultAccount()?->makeDefault();
        }

        return redirect()->route('admin.aws-accounts.index')
            ->with('success', "Akun AWS \"{$name}\" berhasil dihapus.");
    }

    /** Jadikan akun ini default untuk halaman-halaman yang tidak menyebut akun. */
    public function setDefault(AwsAccount $awsAccount)
    {
        if (!$awsAccount->is_active) {
            return back()->with('error', 'Akun nonaktif tidak bisa dijadikan default.');
        }

        // Dibaca sebelum dipindah, supaya pesannya bisa menyebut akun yang dilepas.
        $previous = $this->currentDefault();

        $awsAccount->makeDefault();

        $message = "\"{$awsAccount->name}\" sekarang jadi akun default.";
        if ($previous && $previous->id !== $awsAccount->id) {
            $message .= " \"{$previous->name}\" dilepas dari default.";
        }

        return back()->with('success', $message);
    }

    /** Uji kredensial dengan satu panggilan ListKeys — murah dan tidak mengubah apa pun. */
    public function test(AwsAccount $awsAccount)
    {
        if (!$awsAccount->hasCredentials()) {
            return back()->with('error', "Akun \"{$awsAccount->name}\" belum punya access key / secret.");
        }

        $result = AwsLocationService::forAccount($awsAccount)->testConnection();

        if (!$result['ok']) {
            return back()->with('error', "Koneksi ke \"{$awsAccount->name}\" gagal: {$result['error']}");
        }

        $awsAccount->update(['last_verified_at' => now()]);

        return back()->with('success', "Koneksi ke \"{$awsAccount->name}\" berhasil — {$result['keys']} API key terbaca.");
    }

    private function validateAccount(Request $request, ?AwsAccount $account, bool $secretRequired): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('aws_accounts', 'name')->ignore($account?->id),
            ],
            'account_number'    => ['nullable', 'string', 'regex:/^\d{12}$/'],
            'access_key_id'     => ['required', 'string', 'max:128'],
            'secret_access_key' => [$secretRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'region'            => ['required', 'string', 'max:32', 'regex:/^[a-z0-9\-]+$/'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ], [
            'account_number.regex' => 'Account ID AWS terdiri dari 12 digit angka.',
            'region.regex'         => 'Format region tidak valid (contoh: ap-southeast-1).',
            'secret_access_key.required' => 'Secret access key wajib diisi saat menambah akun.',
        ]);
    }
}
