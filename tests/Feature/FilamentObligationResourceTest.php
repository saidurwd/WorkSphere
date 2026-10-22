<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Department;
use Database\Factories\ObligationFactory;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Obligations\Filament\Resources\Obligations\Pages\CreateObligation;
use Modules\Obligations\Filament\Resources\Obligations\Pages\EditObligation;
use Modules\Obligations\Filament\Resources\Obligations\Pages\ListObligations;
use Modules\Obligations\Filament\Resources\Obligations\Pages\ViewObligation;
use Modules\Obligations\Filament\Resources\Obligations\RelationManagers\DocumentsRelationManager;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationCategory;
use Modules\Obligations\Models\ObligationDocument;
use Modules\Obligations\Models\ObligationRenewal;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Obligations\Models\ObligationType;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament Obligation resource (workspace panel).
 */
class FilamentObligationResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_without_obligation_view_you_see_what_you_own_or_are_responsible_for(): void
    {
        $user = $this->userWithPermissions(['obligation.create']);
        $owned = ObligationFactory::new()->create(['owner_user_id' => $user->id]);
        $responsible = ObligationFactory::new()->create();
        ObligationResponsibility::factory()->create(['obligation_id' => $responsible->id, 'user_id' => $user->id, 'active' => true]);
        $lapsed = ObligationFactory::new()->create();
        ObligationResponsibility::factory()->create(['obligation_id' => $lapsed->id, 'user_id' => $user->id, 'active' => false]);
        $stranger = ObligationFactory::new()->create();
        $this->actingAs($user);

        Livewire::test(ListObligations::class)
            ->assertCanSeeTableRecords([$owned, $responsible])
            ->assertCanNotSeeTableRecords([$lapsed, $stranger]);
    }

    public function test_obligation_view_and_super_admin_see_everything(): void
    {
        $all = ObligationFactory::new()->count(2)->create();

        $this->actingAs($this->userWithPermissions(['obligation.view']));
        Livewire::test(ListObligations::class)->assertCanSeeTableRecords($all);

        $this->actingAs($this->superAdmin());
        Livewire::test(ListObligations::class)->assertCanSeeTableRecords($all);
    }

    public function test_the_filters_narrow_the_list(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.view']));
        $high = ObligationFactory::new()->create(['priority' => 'high', 'status' => 'active', 'expiry_date' => now()->addDays(3)]);
        $low = ObligationFactory::new()->create(['priority' => 'low', 'status' => 'archived', 'expiry_date' => now()->addDays(200)]);
        $lapsed = ObligationFactory::new()->create(['priority' => 'low', 'status' => 'active', 'expiry_date' => now()->subDays(5)]);

        Livewire::test(ListObligations::class)
            ->filterTable('priority', 'high')
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$low, $lapsed]);

        Livewire::test(ListObligations::class)
            ->filterTable('expiry', ['period' => '7_days'])
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$low, $lapsed]);

        Livewire::test(ListObligations::class)
            ->filterTable('expiry', ['period' => 'expired'])
            ->assertCanSeeTableRecords([$lapsed])
            ->assertCanNotSeeTableRecords([$high, $low]);
    }

    private function validForm(): array
    {
        $user = $this->plainUser();

        return [
            'title' => 'Fire certificate',
            'obligation_type_id' => ObligationType::factory()->create(['active' => true])->id,
            'category_id' => ObligationCategory::factory()->create(['active' => true])->id,
            'company_id' => Company::factory()->create(['status' => 'active'])->id,
            'department_id' => Department::factory()->create(['status' => 'active'])->id,
            'owner_user_id' => $user->id,
            'start_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'renewal_required' => true,
            'auto_renew' => false,
            'priority' => 'high',
            'risk_level' => 'medium',
            'status' => 'active',
        ];
    }

    public function test_an_obligation_is_created_numbered_and_logged(): void
    {
        $creator = $this->userWithPermissions(['obligation.create']);
        $this->actingAs($creator);

        Livewire::test(CreateObligation::class)
            ->fillForm($this->validForm())
            ->call('create')
            ->assertHasNoFormErrors();

        $obligation = Obligation::query()->where('title', 'Fire certificate')->firstOrFail();
        $this->assertStringStartsWith('OBS-', $obligation->obligation_no);
        $this->assertSame($creator->id, $obligation->created_by);
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'CREATED']);
    }

    public function test_the_expiry_cannot_precede_the_start_and_a_title_is_required(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.create']));

        Livewire::test(CreateObligation::class)
            ->fillForm([...$this->validForm(), 'title' => null, 'start_date' => now()->toDateString(), 'expiry_date' => now()->subDay()->toDateString()])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'expiry_date']);
    }

    public function test_creating_needs_the_create_permission(): void
    {
        $this->actingAs($this->plainUser());

        $this->get('/obligations/create')->assertForbidden();
    }

    public function test_the_owner_edits_and_it_is_logged_but_a_viewer_cannot(): void
    {
        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner);
        Livewire::test(EditObligation::class, ['record' => $obligation->getKey()])
            ->fillForm(['title' => 'Renamed obligation'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed obligation', $obligation->fresh()->title);
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'UPDATED']);

        $this->actingAs($this->userWithPermissions(['obligation.view']));
        $this->get("/obligations/{$obligation->id}/edit")->assertForbidden();
    }

    public function test_a_stranger_cannot_open_an_obligation_they_have_no_stake_in(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.create']));
        $obligation = ObligationFactory::new()->create();

        $this->get("/obligations/{$obligation->id}")->assertNotFound();
    }

    public function test_deleting_is_for_the_owner_or_obligation_delete(): void
    {
        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);
        $this->actingAs($owner);

        Livewire::test(ListObligations::class)->callTableAction('delete', $obligation);
        $this->assertModelMissing($obligation);
    }

    public function test_the_owner_can_upload_and_delete_a_document_with_an_audit_trail(): void
    {
        Storage::fake('public');

        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);
        $this->actingAs($owner);

        Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $obligation, 'pageClass' => ViewObligation::class])
            ->callAction(TestAction::make('create')->table(), [
                'document_type' => 'LICENCE',
                'file' => UploadedFile::fake()->create('licence.pdf', 20, 'application/pdf'),
            ]);

        $document = ObligationDocument::query()->where('obligation_id', $obligation->id)->firstOrFail();
        $this->assertSame('LICENCE', $document->document_type);
        $this->assertSame($owner->id, $document->uploaded_by);
        $this->assertTrue(Storage::disk('public')->exists($document->file_path));
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'DOCUMENT_UPLOADED']);
        // Dual-written onto the shared attachments table.
        $this->assertDatabaseHas('attachments', ['attachable_id' => $obligation->id, 'path' => $document->file_path, 'original_name' => $document->file_name]);

        Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $obligation, 'pageClass' => ViewObligation::class])
            ->callAction(TestAction::make('delete')->table($document));

        $this->assertModelMissing($document);
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'DOCUMENT_DELETED']);
    }

    public function test_someone_without_document_rights_cannot_upload(): void
    {
        $viewer = $this->userWithPermissions(['obligation.view']);
        $obligation = ObligationFactory::new()->create();
        $this->actingAs($viewer);

        Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $obligation, 'pageClass' => ViewObligation::class])
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_renewing_moves_the_dates_resets_status_and_risk_and_records_it(): void
    {
        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create([
            'owner_user_id' => $owner->id,
            'status' => 'expired',
            'risk_level' => 'critical',
            'start_date' => now()->subYear(),
            'expiry_date' => now()->subDay(),
        ]);
        $previousExpiry = $obligation->expiry_date->toDateString();
        $this->actingAs($owner);

        Livewire::test(ViewObligation::class, ['record' => $obligation->getKey()])
            ->callAction('renew', [
                'new_start_date' => now()->toDateString(),
                'new_expiry_date' => now()->addYear()->toDateString(),
                'cost' => 2500,
            ])
            ->assertHasNoActionErrors();

        $obligation->refresh();
        $this->assertSame('active', $obligation->status);
        $this->assertSame('low', $obligation->risk_level);
        $this->assertSame(now()->addYear()->toDateString(), $obligation->expiry_date->toDateString());

        $renewal = ObligationRenewal::query()->where('obligation_id', $obligation->id)->firstOrFail();
        $this->assertSame($previousExpiry, $renewal->previous_expiry_date->toDateString());
        $this->assertSame($owner->id, $renewal->renewed_by);
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'RENEWED']);
    }

    public function test_a_renewal_must_expire_after_it_starts(): void
    {
        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id, 'start_date' => now()->subYear()]);
        $this->actingAs($owner);

        Livewire::test(ViewObligation::class, ['record' => $obligation->getKey()])
            ->callAction('renew', ['new_start_date' => now()->toDateString(), 'new_expiry_date' => now()->subDay()->toDateString()])
            ->assertHasActionErrors(['new_expiry_date']);

        $this->assertDatabaseCount('obligation_renewals', 0);
    }

    public function test_renewing_is_for_the_owner_or_obligation_renew(): void
    {
        $obligation = ObligationFactory::new()->create();
        $this->actingAs($this->userWithPermissions(['obligation.view']));

        Livewire::test(ViewObligation::class, ['record' => $obligation->getKey()])->assertActionHidden('renew');
    }

    public function test_an_active_responsibility_holder_may_view_but_not_edit_and_a_lapsed_one_may_not_view(): void
    {
        $holder = $this->userWithPermissions(['obligation.create']);
        $lapsed = $this->plainUser();
        $obligation = ObligationFactory::new()->create();
        ObligationResponsibility::factory()->create(['obligation_id' => $obligation->id, 'user_id' => $holder->id, 'active' => true]);
        ObligationResponsibility::factory()->create(['obligation_id' => $obligation->id, 'user_id' => $lapsed->id, 'active' => false]);

        $this->actingAs($holder);
        $this->get("/obligations/{$obligation->id}")->assertOk();
        $this->get("/obligations/{$obligation->id}/edit")->assertForbidden();

        $this->actingAs($lapsed);
        $this->get("/obligations/{$obligation->id}")->assertNotFound();
    }

    public function test_a_super_admin_may_edit_any_obligation_and_a_stranger_cannot_change_one(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->superAdmin());
        Livewire::test(EditObligation::class, ['record' => $obligation->getKey()])
            ->fillForm(['title' => 'Admin edit'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Admin edit', $obligation->fresh()->title);

        $this->actingAs($this->userWithPermissions(['obligation.create']));
        $this->get("/obligations/{$obligation->id}/edit")->assertNotFound();
    }

    public function test_obligation_view_alone_does_not_grant_update_or_delete(): void
    {
        $obligation = ObligationFactory::new()->create();
        $this->actingAs($this->userWithPermissions(['obligation.view']));

        $this->get("/obligations/{$obligation->id}")->assertOk();
        $this->get("/obligations/{$obligation->id}/edit")->assertForbidden();

        Livewire::test(ListObligations::class)->assertTableActionHidden('delete', $obligation);
    }

    public function test_an_anonymous_visitor_is_sent_to_sign_in(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->get("/obligations/{$obligation->id}")->assertRedirect('/login');
    }

    public function test_the_backfill_command_mirrors_older_documents_and_is_idempotent(): void
    {
        $obligation = ObligationFactory::new()->create();
        $document = ObligationDocument::factory()->create(['obligation_id' => $obligation->id]);

        $this->artisan('obligations:backfill-documents')->expectsOutputToContain('Mirrored 1')->assertSuccessful();
        $this->artisan('obligations:backfill-documents')->assertSuccessful();

        $this->assertSame(1, Attachment::query()->where('path', $document->file_path)->count());
    }

    public function test_a_renewal_certificate_is_mirrored_too(): void
    {
        Storage::fake('public');

        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id, 'start_date' => now()->subYear()]);
        $this->actingAs($owner);

        Livewire::test(ViewObligation::class, ['record' => $obligation->getKey()])
            ->callAction('renew', [
                'new_start_date' => now()->toDateString(),
                'new_expiry_date' => now()->addYear()->toDateString(),
                'document' => UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf'),
            ]);

        $certificate = $obligation->documents()->where('document_type', 'RENEWAL_CERTIFICATE')->firstOrFail();
        $this->assertDatabaseHas('attachments', ['attachable_id' => $obligation->id, 'path' => $certificate->file_path]);
    }
}
