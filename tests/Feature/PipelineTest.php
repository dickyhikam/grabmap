<?php

namespace Tests\Feature;

use App\Models\ApiKeyUsageDaily;
use App\Models\Company;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions = ['*']): User
    {
        $role = $permissions === ['*']
            ? Role::where('slug', 'admin')->first()
            : Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'permissions' => $permissions]);

        return User::factory()->create([
            'role_id'           => $role->id,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    private function stage(string $name): PipelineStage
    {
        return PipelineStage::where('name', $name)->firstOrFail();
    }

    public function test_new_company_lands_in_first_column_and_board_renders(): void
    {
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);

        $this->assertSame($this->stage('Prospek')->id, $company->pipeline_stage_id);
        $this->assertSame('created', $company->activities()->first()->type);

        $this->actingAs($this->user())->get(route('admin.pipeline.index'))
            ->assertOk()
            ->assertSee('PT Maju')
            ->assertSee('Subscribed');
    }

    public function test_moving_a_card_changes_stage_order_and_history(): void
    {
        $a = Company::create(['name' => 'A', 'slug' => 'a']);
        $b = Company::create(['name' => 'B', 'slug' => 'b']);
        $demo = $this->stage('Meeting');

        $this->actingAs($this->user())
            ->postJson(route('admin.pipeline.move'), ['company' => $a->uuid, 'stage' => $demo->uuid, 'order' => [$a->uuid]])
            ->assertOk()
            ->assertJsonPath('stage', $demo->uuid);

        // B menyusul ke kolom yang sama, ditaruh di atas A.
        $this->postJson(route('admin.pipeline.move'), ['company' => $b->uuid, 'stage' => $demo->uuid, 'order' => [$b->uuid, $a->uuid]])
            ->assertOk();

        $this->assertSame(['B', 'A'], $demo->companies()->pluck('name')->all());

        $moved = $a->activities()->where('type', 'moved')->first();
        $this->assertSame(['from' => 'Prospek', 'to' => 'Meeting'], $moved->meta);
    }

    public function test_add_company_button_uses_the_company_form_and_returns_to_the_board(): void
    {
        $this->actingAs($this->user());
        $poc = $this->stage('POC');

        $this->get(route('admin.companies.create', ['stage' => $poc->uuid, 'from' => 'pipeline']))
            ->assertOk()
            ->assertSee('name="pipeline_stage"', false)
            ->assertSee('name="from" value="pipeline"', false);

        $response = $this->post(route('admin.companies.store'), [
            'name' => 'PT Maju', 'slug' => 'pt-maju', 'is_active' => 1, 'sc_mode' => 'inherit',
            'pipeline_stage' => $poc->uuid, 'from' => 'pipeline',
        ]);

        $company = Company::where('slug', 'pt-maju')->firstOrFail();
        $response->assertRedirect(route('admin.pipeline.index', ['c' => $company->uuid]));
        $this->assertSame($poc->id, $company->pipeline_stage_id);

        // Dari daftar perusahaan biasa: tetap kembali ke daftar, dan masuk kolom pertama.
        $this->post(route('admin.companies.store'), ['name' => 'PT Lain', 'slug' => 'pt-lain', 'is_active' => 1, 'sc_mode' => 'inherit'])
            ->assertRedirect(route('admin.companies.index'));
        $this->assertSame($this->stage('Prospek')->id, Company::where('slug', 'pt-lain')->value('pipeline_stage_id'));
    }

    public function test_dropping_a_card_remembers_where_the_client_stopped(): void
    {
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju', 'pipeline_stage_id' => $this->stage('POC')->id]);
        $this->actingAs($this->user());

        $this->postJson(route('admin.pipeline.move'), ['company' => $company->uuid, 'stage' => $this->stage('Drop')->uuid])
            ->assertOk()
            ->assertSee('POC');
        $this->assertSame('POC', $company->fresh()->closed_from);

        // Dihidupkan lagi: catatan "berhenti di" ikut hilang.
        $this->postJson(route('admin.pipeline.move'), ['company' => $company->uuid, 'stage' => $this->stage('Meeting')->uuid])->assertOk();
        $this->assertNull($company->fresh()->closed_from);
    }

    public function test_clarifications_can_be_added_answered_and_removed(): void
    {
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);
        $this->actingAs($this->user());

        $this->postJson(route('admin.pipeline.clarifications.store', $company), ['question' => 'Apakah ada routing truk?'])->assertOk();
        $clar = $company->clarifications()->firstOrFail();
        $this->assertSame('open', $clar->status);
        $this->assertSame(1, $company->fresh()->clarifications()->where('status', '!=', 'clarified')->count());

        $this->putJson(route('admin.pipeline.clarifications.update', [$company, $clar]), [
            'question' => 'Apakah ada routing truk?', 'answer' => 'Belum didukung.', 'status' => 'clarified',
        ])->assertOk();
        $this->assertSame(['clarified', 'Belum didukung.'], [$clar->fresh()->status, $clar->fresh()->answer]);
        $this->assertSame('status', $company->activities()->where('type', 'clarify')->first()->meta['action']);

        // Klarifikasi milik perusahaan lain tidak bisa diubah lewat URL perusahaan ini.
        $other = Company::create(['name' => 'PT Lain', 'slug' => 'pt-lain']);
        $this->deleteJson(route('admin.pipeline.clarifications.destroy', [$other, $clar]))->assertNotFound();

        $this->deleteJson(route('admin.pipeline.clarifications.destroy', [$company, $clar]))->assertOk();
        $this->assertSame(0, $company->clarifications()->count());
    }

    public function test_profile_saves_needs_website_pic_and_mom_links(): void
    {
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);
        $this->actingAs($this->user());

        $this->putJson(route('admin.pipeline.profile', $company), [
            'needs'        => 'Routing truk',
            'website'      => 'maju.co.id',
            'pic_external' => 'Mba Novia',
            'mom_links'    => [
                ['label' => 'MOM Intro', 'url' => 'https://docs.google.com/document/d/abc'],
                ['label' => 'baris kosong', 'url' => ''],
            ],
        ])->assertOk();

        $company->refresh();
        $this->assertSame('https://maju.co.id', $company->website);
        $this->assertSame('Mba Novia', $company->pic_external);
        $this->assertSame([['label' => 'MOM Intro', 'url' => 'https://docs.google.com/document/d/abc']], $company->mom_links);
        $this->assertEqualsCanonicalizing(['needs', 'website', 'pic_external', 'mom_links'], $company->activities()->where('type', 'updated')->first()->meta['profile']);
    }

    public function test_team_sheet_import_matches_existing_companies_and_is_not_duplicated(): void
    {
        $kai = Company::create(['name' => 'PT Kereta Api Indonesia (KAI)', 'slug' => 'pt-kereta-api-indonesia']);

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->fromArray([
            ['Company Name', 'Company Website', 'Needs', 'Status', 'Stage', 'Need to Clarify / Support', 'Response', 'Link to MOM', 'Notes', 'PIC GFB'],
            ['PT KAI', '', 'API GrabMaps', 'Completed', 'Subscribed', 'POC ke KAI Access', 'Waiting feedback POC', '', '- Roll out 100%', ''],
            ['Waresix', 'https://waresix.com/', 'Truck routing', 'Drop', 'POC', "1. Truck routing?\n2. Real-time traffic?", 'Clarified', 'MOM Waresix', '', 'GFB team : Mba Novia'],
        ]);
        $sheet->getCell('H3')->getHyperlink()->setUrl('https://docs.google.com/document/d/waresix');
        $file = tempnam(sys_get_temp_dir(), 'sheet') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($file);

        $this->artisan('pipeline:import-sheet', ['file' => $file])->assertSuccessful();
        $this->artisan('pipeline:import-sheet', ['file' => $file])->assertSuccessful();
        unlink($file);

        $this->assertSame(2, Company::count());
        $this->assertSame('Subscribed', $kai->fresh()->stage->name);
        $this->assertSame('waiting', $kai->clarifications()->value('status'));
        $this->assertSame(1, $kai->activities()->where('type', 'note')->count());

        $waresix = Company::where('name', 'Waresix')->firstOrFail();
        $this->assertFalse($waresix->is_active);
        $this->assertSame(['Drop', 'POC'], [$waresix->stage->name, $waresix->closed_from]);
        $this->assertSame(['Truck routing?', 'Real-time traffic?'], $waresix->clarifications()->pluck('question')->all());
        $this->assertSame('clarified', $waresix->clarifications()->value('status'));
        $this->assertSame('Mba Novia', $waresix->pic_external);
        $this->assertSame('https://docs.google.com/document/d/waresix', $waresix->mom_links[0]['url']);
    }

    public function test_panel_saves_owner_and_next_step_and_logs_only_real_changes(): void
    {
        $user = $this->user();
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);
        $payload = [
            'stage'           => $this->stage('Prospek')->uuid,
            'owner_id'        => $user->id,
            'next_action'     => 'Kirim penawaran',
            'next_action_due' => '2026-11-10',
        ];

        $this->actingAs($user)->putJson(route('admin.pipeline.update', $company), $payload)
            ->assertOk()
            ->assertJsonStructure(['panel', 'card', 'stage']);

        $company->refresh();
        $this->assertSame($user->id, $company->owner_id);
        $this->assertSame('2026-11-10', $company->next_action_due->toDateString());

        // Simpan ulang tanpa perubahan tidak menambah riwayat.
        $this->putJson(route('admin.pipeline.update', $company), $payload)->assertOk();
        $this->assertSame(1, $company->activities()->where('type', 'updated')->count());

        $this->postJson(route('admin.pipeline.note', $company), ['body' => 'Klien minta demo ulang'])->assertOk();
        $this->assertSame('Klien minta demo ulang', $company->activities()->where('type', 'note')->value('body'));
    }

    public function test_column_description_is_saved_and_shown_on_the_board(): void
    {
        $this->actingAs($this->user());
        $stage = $this->stage('Meeting');

        $this->assertNotNull($stage->description);

        $this->from(route('admin.pipeline.index'))
            ->put(route('admin.pipeline.stages.update', $stage), [
                'name' => 'Meeting', 'color' => 'indigo', 'category' => 'presales', 'description' => 'Klien sudah mencoba halaman demo.',
            ])
            ->assertSessionHas('success');

        $this->get(route('admin.pipeline.index'))->assertSee('Klien sudah mencoba halaman demo.');
    }

    public function test_columns_are_grouped_by_category(): void
    {
        $this->actingAs($this->user());

        $this->assertSame(
            ['presales', 'presales', 'onboarding', 'onboarding', 'live', 'closed'],
            PipelineStage::ordered()->pluck('category')->all(),
        );

        // Kolom baru di kategori "belum trial" tetap di depan kolom onboarding,
        // walau posisinya paling belakang — dan kolom pertama tetap Prospek.
        $this->post(route('admin.pipeline.stages.store'), ['name' => 'Kualifikasi', 'color' => 'slate', 'category' => 'presales']);
        $this->assertSame(['Prospek', 'Meeting', 'Kualifikasi', 'POC', 'Integrasi'], PipelineStage::ordered()->take(5)->pluck('name')->all());
        $this->assertSame('Prospek', PipelineStage::firstStage()->name);

        $this->get(route('admin.pipeline.index'))
            ->assertOk()
            ->assertSeeInOrder([__('pipeline.categories.presales'), __('pipeline.categories.onboarding'), __('pipeline.categories.live'), __('pipeline.categories.closed')]);
    }

    public function test_only_empty_columns_can_be_deleted(): void
    {
        $this->actingAs($this->user());
        Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);

        $this->from(route('admin.pipeline.index'))
            ->delete(route('admin.pipeline.stages.destroy', $this->stage('Prospek')))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('pipeline_stages', ['name' => 'Prospek']);

        $this->from(route('admin.pipeline.index'))
            ->delete(route('admin.pipeline.stages.destroy', $this->stage('Drop')))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('pipeline_stages', ['name' => 'Drop']);
    }

    public function test_view_only_role_can_open_the_board_but_not_move_cards(): void
    {
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);
        $viewer = $this->user(['companies.view']);

        $this->actingAs($viewer)->get(route('admin.pipeline.index'))->assertOk();
        $this->getJson(route('admin.pipeline.show', $company))->assertOk();

        $this->postJson(route('admin.pipeline.move'), ['company' => $company->uuid, 'stage' => $this->stage('Meeting')->uuid])
            ->assertForbidden();
        $this->assertSame($this->stage('Prospek')->id, $company->fresh()->pipeline_stage_id);
    }

    public function test_recent_requests_include_keys_on_env_credentials(): void
    {
        $company = Company::create(['name' => 'PT Maju', 'slug' => 'pt-maju']);
        // Akun null = kredensial .env — pencocokan akun harus aman terhadap NULL.
        $company->apiKeys()->create(['aws_account_id' => null, 'key_name' => 'key-maju', 'is_primary' => true]);

        ApiKeyUsageDaily::store(null, 'key-maju', [
            '2026-10-01' => ['SearchText' => 500],
            '2026-11-01' => ['SearchText' => 120, 'GetTile' => 30],
        ]);

        $this->assertSame([$company->id => 150], Company::requestsSince('2026-10-27'));
    }
}
