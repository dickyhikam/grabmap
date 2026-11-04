<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyActivity;
use App\Models\CompanyClarification;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Board pipeline perusahaan ala Trello: kolom = tahap, kartu = perusahaan.
 *
 * Perubahan pada kartu (geser, tambah cepat, PIC, catatan) dikirim lewat fetch
 * dan dibalas potongan HTML kartu/panel yang dirender di sini — tampilannya
 * cukup satu sumber, yaitu Blade. Pengelolaan kolom jarang dilakukan, jadi
 * cukup formulir biasa yang memuat ulang halaman.
 */
class PipelineController extends Controller
{
    public function index()
    {
        $stages = $this->stages();
        $first  = $stages->first();

        // Perusahaan yang belum punya tahap (mis. tahapnya sempat hilang) ditaruh
        // di kolom pertama supaya tidak lenyap dari board.
        $byStage = $this->cardQuery()->get()->groupBy(
            fn (Company $c) => $stages->contains('id', $c->pipeline_stage_id) ? $c->pipeline_stage_id : $first?->id
        );

        return view('admin.pipeline.index', [
            // Kategori tanpa kolom tidak ditampilkan — tidak ada tempat kartu mendarat.
            'groups'  => $stages->groupBy('category'),
            'byStage' => $byStage,
            'usage'   => $this->recentRequests(),
            'users'   => $this->users(),
        ]);
    }

    /** Isi panel samping untuk satu perusahaan. */
    public function show(Company $company): JsonResponse
    {
        return response()->json(['panel' => $this->renderPanel($company)]);
    }

    /**
     * Kartu digeser. `order` = urutan uuid di kolom tujuan seperti yang terlihat
     * di layar; kolom asal tidak perlu dirapikan karena urutannya relatif.
     */
    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company' => ['required', 'uuid', 'exists:companies,uuid'],
            'stage'   => ['required', 'uuid', 'exists:pipeline_stages,uuid'],
            'order'   => ['array'],
            'order.*' => ['uuid'],
        ]);

        $company = Company::where('uuid', $validated['company'])->firstOrFail();
        $stage   = PipelineStage::where('uuid', $validated['stage'])->firstOrFail();

        DB::transaction(function () use ($company, $stage, $validated) {
            $company->moveToStage($stage);

            // Hanya perusahaan yang memang ada di kolom tujuan yang diurutkan —
            // uuid lain di permintaan diabaikan.
            $order = array_values(array_unique($validated['order'] ?? [$company->uuid]));
            $ids   = Company::where('pipeline_stage_id', $stage->id)->whereIn('uuid', $order)->pluck('id', 'uuid');

            foreach ($order as $position => $uuid) {
                if (isset($ids[$uuid])) {
                    Company::whereKey($ids[$uuid])->toBase()->update(['pipeline_position' => $position]);
                }
            }
        });

        return response()->json($this->cardPayload($company));
    }

    /** Simpan isian panel: tahap, PIC, dan langkah berikutnya. */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'stage'           => ['required', 'uuid', 'exists:pipeline_stages,uuid'],
            'owner_id'        => ['nullable', 'integer', 'exists:users,id'],
            'next_action'     => ['nullable', 'string', 'max:255'],
            'next_action_due' => ['nullable', 'date_format:Y-m-d'],
        ], [], [
            'next_action'     => __('pipeline.next_action'),
            'next_action_due' => __('pipeline.due'),
        ]);

        DB::transaction(function () use ($company, $validated) {
            $stage = PipelineStage::where('uuid', $validated['stage'])->firstOrFail();

            // Pindah lewat panel tidak punya posisi tujuan — taruh di dasar kolom.
            if ($company->moveToStage($stage)) {
                $company->update([
                    'pipeline_position' => (int) Company::where('pipeline_stage_id', $stage->id)->max('pipeline_position') + 1,
                ]);
            }

            $company->fill([
                'owner_id'        => $validated['owner_id'] ?? null,
                'next_action'     => $validated['next_action'] ?? null,
                'next_action_due' => $validated['next_action_due'] ?? null,
            ]);

            $meta = [];
            if ($company->isDirty('owner_id')) {
                $meta['owner'] = User::find($company->owner_id)?->name;
            }
            if ($company->isDirty(['next_action', 'next_action_due'])) {
                $meta['next_action'] = $company->next_action;
                $meta['due']         = $company->next_action_due?->toDateString();
            }

            if ($meta) {
                $company->save();
                // Kunci selalu ada di meta supaya riwayat bisa membedakan
                // "PIC dikosongkan" dari "PIC tidak berubah".
                CompanyActivity::log($company, 'updated', null, $meta);
            }
        });

        return $this->panelAndCard($company);
    }

    public function note(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => __('pipeline.note')]);

        CompanyActivity::log($company, 'note', trim($validated['body']));

        return $this->panelAndCard($company);
    }

    /** Profil klien: kebutuhan, website, PIC dari tim Grab, dan tautan notulen meeting. */
    public function updateProfile(Request $request, Company $company): JsonResponse
    {
        // Website boleh diketik tanpa skema ("bca.co.id") — lengkapi sebelum divalidasi.
        $website = trim((string) $request->input('website'));
        if ($website !== '' && !preg_match('~^https?://~i', $website)) {
            $request->merge(['website' => 'https://' . $website]);
        }

        $validated = $request->validate([
            'needs'             => ['nullable', 'string', 'max:5000'],
            'website'           => ['nullable', 'url', 'max:255'],
            'pic_external'      => ['nullable', 'string', 'max:120'],
            'mom_links'         => ['nullable', 'array', 'max:20'],
            'mom_links.*.label' => ['nullable', 'string', 'max:120'],
            'mom_links.*.url'   => ['nullable', 'url', 'max:1000'],
        ], [], [
            'needs'             => __('pipeline.needs'),
            'website'           => __('pipeline.website'),
            'pic_external'      => __('pipeline.pic_external'),
            'mom_links.*.url'   => __('pipeline.mom_url'),
        ]);

        // Baris tautan tanpa URL dianggap baris kosong yang belum dihapus.
        $links = collect($validated['mom_links'] ?? [])
            ->filter(fn ($link) => !empty($link['url']))
            ->map(fn ($link) => ['label' => trim((string) ($link['label'] ?? '')) ?: null, 'url' => $link['url']])
            ->values()
            ->all();

        $company->fill([
            'needs'        => trim((string) ($validated['needs'] ?? '')) ?: null,
            'website'      => $validated['website'] ?? null,
            'pic_external' => trim((string) ($validated['pic_external'] ?? '')) ?: null,
            'mom_links'    => $links ?: null,
        ]);

        if ($changed = array_keys($company->getDirty())) {
            $company->save();
            CompanyActivity::log($company, 'updated', null, ['profile' => $changed]);
        }

        return $this->panelAndCard($company);
    }

    // ─── Klarifikasi ──────────────────────────────────────────────────

    public function storeClarification(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ], [], ['question' => __('pipeline.clar_question')]);

        $clarification = $company->clarifications()->create([
            'question'   => trim($validated['question']),
            'status'     => 'open',
            'created_by' => $request->user()?->id,
        ]);

        CompanyActivity::log($company, 'clarify', null, ['action' => 'added', 'question' => Str::limit($clarification->question, 140)]);

        return $this->panelAndCard($company);
    }

    public function updateClarification(Request $request, Company $company, CompanyClarification $clarification): JsonResponse
    {
        abort_unless($clarification->company_id === $company->id, 404);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'answer'   => ['nullable', 'string', 'max:2000'],
            'status'   => ['required', Rule::in(CompanyClarification::STATUSES)],
        ], [], [
            'question' => __('pipeline.clar_question'),
            'answer'   => __('pipeline.clar_answer'),
        ]);

        $clarification->fill([
            'question' => trim($validated['question']),
            'answer'   => trim((string) ($validated['answer'] ?? '')) ?: null,
            'status'   => $validated['status'],
        ]);

        // Yang dicatat di riwayat hanya perubahan status — perbaikan ketikan tidak.
        if ($clarification->isDirty('status')) {
            CompanyActivity::log($company, 'clarify', null, [
                'action'   => 'status',
                'status'   => $clarification->status,
                'question' => Str::limit($clarification->question, 140),
            ]);
        }
        $clarification->save();

        return $this->panelAndCard($company);
    }

    public function destroyClarification(Company $company, CompanyClarification $clarification): JsonResponse
    {
        abort_unless($clarification->company_id === $company->id, 404);

        CompanyActivity::log($company, 'clarify', null, ['action' => 'removed', 'question' => Str::limit($clarification->question, 140)]);
        $clarification->delete();

        return $this->panelAndCard($company);
    }

    // ─── Kolom ────────────────────────────────────────────────────────

    public function storeStage(Request $request)
    {
        $validated = $request->validate($this->stageRules());

        PipelineStage::create($validated + [
            'position' => (int) PipelineStage::max('position') + 1,
        ]);

        return back()->with('success', __('pipeline.stage_added', ['name' => $validated['name']]));
    }

    public function updateStage(Request $request, PipelineStage $stage)
    {
        $stage->update($request->validate($this->stageRules()));

        return back()->with('success', __('pipeline.stage_saved', ['name' => $stage->name]));
    }

    /**
     * Kolom hanya boleh dihapus saat kosong — memindahkan perusahaan secara diam-diam
     * ke kolom lain akan memalsukan riwayatnya. Kolom terakhir juga dijaga supaya
     * perusahaan baru selalu punya tempat mendarat.
     */
    public function destroyStage(PipelineStage $stage)
    {
        if ($stage->companies()->exists()) {
            return back()->with('error', __('pipeline.stage_not_empty', ['name' => $stage->name]));
        }

        if (PipelineStage::count() <= 1) {
            return back()->with('error', __('pipeline.stage_last'));
        }

        $stage->delete();

        return back()->with('success', __('pipeline.stage_deleted', ['name' => $stage->name]));
    }

    public function orderStages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['uuid'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values(array_unique($validated['order'])) as $position => $uuid) {
                PipelineStage::where('uuid', $uuid)->update(['position' => $position]);
            }
        });

        return response()->json(['ok' => true]);
    }

    // ─── Bantuan ──────────────────────────────────────────────────────

    /** Semua yang ditampilkan kartu, dalam satu kueri per board. */
    private function cardQuery(): Builder
    {
        return Company::query()
            ->with('owner:id,name')
            ->withCount([
                'apiKeys',
                'ownedAwsAccounts',
                'awsInvites as pending_invites_count' => fn ($q) => $q->usable(),
                'usageShares as active_shares_count'  => fn ($q) => $q->where('share_enabled', true),
                'activities as notes_count'           => fn ($q) => $q->where('type', 'note'),
                'clarifications as open_clarifications_count' => fn ($q) => $q->where('status', '!=', 'clarified'),
            ])
            ->orderBy('pipeline_position')
            ->orderBy('id');
    }

    /** Request 7 hari terakhir (termasuk hari ini, tanggal WIB) per perusahaan. */
    private function recentRequests(): array
    {
        return Company::requestsSince(now()->wib()->subDays(6)->toDateString());
    }

    private function stages()
    {
        return PipelineStage::ordered();
    }

    private function users()
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    private function stageRules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'color'       => ['required', Rule::in(PipelineStage::COLORS)],
            'category'    => ['required', Rule::in(array_keys(PipelineStage::CATEGORIES))],
        ];
    }

    /**
     * Kartu yang sudah dirender + uuid tahapnya — board memakai uuid itu untuk
     * memindahkan kartu ke kolom yang benar saat tahap diganti dari panel.
     */
    private function cardPayload(Company $company): array
    {
        $company = $this->cardQuery()->with('stage')->findOrFail($company->id);

        return [
            'card'  => view('admin.pipeline.card', [
                'company'  => $company,
                'requests' => $this->recentRequests()[$company->id] ?? 0,
            ])->render(),
            'stage' => $company->stage?->getRouteKey(),
        ];
    }

    private function renderPanel(Company $company): string
    {
        $company = $this->cardQuery()
            ->with([
                'stage',
                'clarifications',
                'activities' => fn ($q) => $q->with('user:id,name')->limit(50),
            ])
            ->findOrFail($company->id);

        return view('admin.pipeline.panel', [
            'company'  => $company,
            'requests' => $this->recentRequests()[$company->id] ?? 0,
            'stages'   => $this->stages(),
            'users'    => $this->users(),
        ])->render();
    }

    private function panelAndCard(Company $company): JsonResponse
    {
        return response()->json(['panel' => $this->renderPanel($company)] + $this->cardPayload($company));
    }
}
