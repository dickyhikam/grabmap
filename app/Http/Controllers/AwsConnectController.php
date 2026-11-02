<?php

namespace App\Http\Controllers;

use App\Models\ApiKeyUsageShare;
use App\Models\AwsAccount;
use App\Models\AwsConnectInvite;
use App\Services\AwsRoleConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Halaman publik untuk klien menghubungkan akun AWS-nya lewat AssumeRole.
 * Tanpa login — tokennya rahasia, sekali pakai, dan punya masa berlaku.
 */
class AwsConnectController extends Controller
{
    public function show(Request $request, string $token)
    {
        $this->applyLocale($request);

        $invite = $this->findInvite($token);

        return view('aws-connect.show', [
            'invite'          => $invite,
            'permissionJson'  => $this->json(AwsConnectInvite::permissionPolicy()),
            'trustJson'       => $this->json($invite->trustPolicy()),
        ]);
    }

    /** Template CloudFormation yang sudah berisi akun Grab & External ID undangan ini. */
    public function template(string $token)
    {
        $invite = $this->findInvite($token);
        abort_if($invite->isUsed() || $invite->isExpired(), 410);

        return response($invite->cloudFormationTemplate(), 200, [
            'Content-Type'        => 'application/x-yaml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="grabmaps-usage-role.yaml"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    public function store(Request $request, string $token, AwsRoleConnector $connector)
    {
        $this->applyLocale($request);

        $invite = $this->findInvite($token);
        abort_if($invite->isUsed() || $invite->isExpired(), 410);

        $validated = $request->validate([
            'role_arn' => ['required', 'string', 'max:2048', 'regex:/^arn:aws:iam::\d{12}:role\/[\w+=,.@\/-]{1,512}$/'],
            'region'   => ['required', 'string', 'max:32', 'regex:/^[a-z0-9\-]+$/'],
        ], [
            'role_arn.regex' => __('awsconnect.err_arn'),
            'region.regex'   => __('awsaccounts.err_region'),
        ]);

        $candidate = new AwsAccount([
            'name'              => $invite->label,
            'account_number'    => AwsAccount::accountIdFromRoleArn($validated['role_arn']),
            'role_arn'          => $validated['role_arn'],
            'external_id'       => $invite->external_id,
            'source_account_id' => $invite->source_account_id,
            'region'            => $validated['region'],
            'is_active'         => true,
            'is_default'        => false,
        ]);
        $candidate->setRelation('sourceAccount', $invite->sourceAccount);

        $result = $connector->verify($candidate);

        if (!$result['ok']) {
            Log::warning('AWS connect verification failed', [
                'invite' => $invite->id,
                'step'   => $result['step'],
                'error'  => $result['error'],
                'ip'     => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'step'    => $result['step'],
                    'title'   => $result['step'] === 'assume' ? __('awsconnect.err_assume') : __('awsconnect.err_list'),
                    'hint'    => $result['step'] === 'assume'
                        ? __('awsconnect.err_assume_hint', ['role' => AwsConnectInvite::ROLE_NAME])
                        : __('awsconnect.err_list_hint'),
                    'error'   => $result['error'],
                ], 422);
            }

            return back()->withInput()->with('connect_error', [
                'step'  => $result['step'],
                'error' => $result['error'],
            ]);
        }

        $account = DB::transaction(function () use ($invite, $candidate, $request, $result) {
            // Dua kiriman bersamaan tidak boleh sama-sama membuat akun.
            abort_if(AwsConnectInvite::lockForUpdate()->find($invite->id)?->used_at !== null, 410);

            $company = $invite->company;

            $candidate->name = $this->uniqueName($invite->label);
            $candidate->owner_company_id = $company?->id;
            $candidate->last_verified_at = now();
            if (!empty($result['resources'])) {
                $candidate->resources = $result['resources'];
                $candidate->resources_checked_at = now();
            }
            $candidate->save();

            // Company yang belum punya akun langsung memakai akun ini; yang sudah
            // punya tidak disentuh — key-nya bisa saja berasal dari akun lain.
            if ($company && !$company->aws_account_id) {
                $company->update(['aws_account_id' => $candidate->id]);
            }

            // Link dashboard tanpa cakupan key = seluruh key perusahaan, jadi key
            // yang dibuat klien belakangan ikut tampil tanpa perlu link baru.
            $share = $company
                ? ApiKeyUsageShare::enableForCompany($company->id, 'aws-connect', null, __('awsconnect.share_label'))
                : null;

            $invite->update([
                'used_at'        => now(),
                'used_ip'        => $request->ip(),
                'aws_account_id' => $candidate->id,
                'keys_found'     => $result['keys'],
                'usage_share_id' => $share?->id,
            ]);

            return $candidate;
        });

        // Key klien dicatat sekarang supaya dashboard langsung mengenalnya; tarikan
        // CloudWatch 91 hari dikerjakan sesudah respons terkirim agar klien tidak menunggu.
        $account->syncOwnerKeys($result['key_names'] ?? null);

        if ($company = $invite->company) {
            // terminating() jalan sesudah respons terkirim, tanpa antrean: tidak
            // bergantung pada worker yang mungkin tidak berjalan.
            app()->terminating(fn () => $connector->pullInitialUsage($company));
        }

        Log::info('AWS account connected via AssumeRole', [
            'invite'  => $invite->id,
            'account' => $account->id,
            'role'    => $account->role_arn,
            'ip'      => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('aws-connect.show', $token)]);
        }

        return redirect()->route('aws-connect.show', $token);
    }

    private function findInvite(string $token): AwsConnectInvite
    {
        return AwsConnectInvite::with(['sourceAccount', 'company', 'usageShare', 'awsAccount'])
            ->where('token', $token)
            ->firstOr(fn () => abort(404));
    }

    /** Nama akun unik di tabel — label undangan bisa sama dengan akun yang sudah ada. */
    private function uniqueName(string $label): string
    {
        $name = $label;
        for ($i = 2; AwsAccount::where('name', $name)->exists(); $i++) {
            $name = "{$label} ({$i})";
        }

        return $name;
    }

    private function json(array $doc): string
    {
        return json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /** Bahasa halaman publik: ?lang= → pilihan sebelumnya → bawaan aplikasi. */
    private function applyLocale(Request $request): void
    {
        $supported = ['en', 'id'];

        $lang = $request->query('lang');
        if (!in_array($lang, $supported, true)) {
            $lang = $request->session()->get('report_lang');
        }

        if (!in_array($lang, $supported, true)) {
            $lang = config('app.locale');
        }

        $request->session()->put('report_lang', $lang);
        app()->setLocale($lang);
    }
}
