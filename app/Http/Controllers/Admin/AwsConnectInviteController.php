<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwsAccount;
use App\Models\AwsConnectInvite;
use App\Models\Company;
use App\Services\AwsRoleConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Undangan untuk klien menghubungkan akun AWS-nya sendiri lewat AssumeRole.
 * Admin membuat link, klien mengikuti tutorial di halaman publiknya.
 */
class AwsConnectInviteController extends Controller
{
    public function index()
    {
        $invites = AwsConnectInvite::with(['company', 'sourceAccount', 'awsAccount', 'creator', 'usageShare'])
            ->latest()
            ->get();

        return view('admin.aws-accounts.invites', [
            'invites'        => $invites,
            'sourceAccounts' => AwsAccount::assumeRoleSources(),
            'companies'      => Company::orderBy('name')->get(['id', 'name']),
            'sourcePolicy'   => AwsConnectInvite::sourcePolicy(),
        ]);
    }

    public function store(Request $request, AwsRoleConnector $connector)
    {
        $sourceIds = AwsAccount::assumeRoleSources()->pluck('id')->all();

        $validated = $request->validate([
            'label'             => ['required', 'string', 'max:100'],
            'company_id'        => ['nullable', 'integer', 'exists:companies,id'],
            'source_account_id' => ['required', 'integer', Rule::in($sourceIds)],
            'region'            => ['required', 'string', 'max:32', 'regex:/^[a-z0-9\-]+$/'],
            'days'              => ['required', 'integer', 'min:1', 'max:30'],
        ], [
            'source_account_id.in' => __('awsconnect.err_source'),
            'region.regex'         => __('awsaccounts.err_region'),
        ]);

        $source = AwsAccount::findOrFail($validated['source_account_id']);

        // Account ID yang ditulis di trust policy diambil dari AWS, bukan dari isian
        // manual — salah satu digit saja membuat role klien tidak pernah bisa dipakai.
        try {
            $trusted = $connector->callerAccountId($source);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('awsconnect.err_identity', [
                'name'  => $source->name,
                'error' => $e instanceof \Aws\Exception\AwsException ? ($e->getAwsErrorMessage() ?: $e->getMessage()) : $e->getMessage(),
            ]));
        }

        $invite = AwsConnectInvite::issue(
            $source,
            $trusted,
            $validated['label'],
            $validated['company_id'] ?? null,
            $validated['region'],
            (int) $validated['days'],
            $request->user()?->id,
        );

        Log::info('AWS connect invite created', [
            'invite' => $invite->id,
            'label'  => $invite->label,
            'by'     => $request->user()?->email,
        ]);

        // Dibuat dari halaman detail perusahaan: kembali ke sana, link-nya tampil di kartunya.
        $target = $request->boolean('from_company') && $invite->company
            ? redirect()->route('admin.companies.show', $invite->company)
            : redirect()->route('admin.aws-invites.index');

        return $target
            ->with('success', __('awsconnect.created', ['label' => $invite->label]))
            ->with('new_invite', $invite->id);
    }

    /** Matikan link yang belum terpakai. Akun yang sudah terbentuk tidak ikut terhapus. */
    public function destroy(AwsConnectInvite $invite)
    {
        if ($invite->isUsed()) {
            return back()->with('error', __('awsconnect.err_used'));
        }

        $invite->delete();

        return back()->with('success', __('awsconnect.revoked', ['label' => $invite->label]));
    }
}
