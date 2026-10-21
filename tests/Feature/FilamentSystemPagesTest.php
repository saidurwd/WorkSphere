<?php

namespace Tests\Feature;

use App\Filament\Pages\System\SystemSettings;
use App\Filament\Resources\ApiTokens\Pages\ListApiTokens;
use App\Filament\Resources\FeatureFlags\Pages\CreateFeatureFlag;
use App\Filament\Resources\FeatureFlags\Pages\EditFeatureFlag;
use App\Filament\Resources\FeatureFlags\Pages\ListFeatureFlags;
use App\Models\FeatureFlag;
use App\Services\FeatureFlags;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament replacements for the Blade system administration screens.
 */
class FilamentSystemPagesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_settings_are_saved_with_their_declared_types(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(SystemSettings::class)
            ->fillForm([
                SystemSettings::fieldName('app.timezone') => 'Asia/Dhaka',
                SystemSettings::fieldName('security.session_lifetime') => '45',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $this->assertSame('Asia/Dhaka', $settings->get('app.timezone'));
        $this->assertSame(45, $settings->get('security.session_lifetime'));
    }

    public function test_a_setting_of_the_wrong_type_is_refused(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(SystemSettings::class)
            ->fillForm([SystemSettings::fieldName('security.session_lifetime') => 'abc'])
            ->call('save')
            ->assertHasFormErrors([SystemSettings::fieldName('security.session_lifetime')]);
    }

    public function test_the_settings_page_needs_its_own_permission(): void
    {
        $this->actingAs($this->adminWithout(['system.health']));

        $this->get('/panel/system/settings')->assertForbidden();
    }

    public function test_a_new_flag_is_off_until_it_is_toggled_on(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateFeatureFlag::class)
            ->fillForm(['key' => 'new-thing', 'name' => 'New thing', 'type' => 'boolean', 'rollout_percentage' => 100])
            ->call('create')
            ->assertHasNoFormErrors();

        $flags = app(FeatureFlags::class);
        $this->assertFalse($flags->enabled('new-thing'));

        $flag = FeatureFlag::query()->where('key', 'new-thing')->firstOrFail();

        Livewire::test(ListFeatureFlags::class)->callTableAction('toggle', $flag);
        $this->assertTrue($flags->enabled('new-thing'));

        Livewire::test(ListFeatureFlags::class)->callTableAction('toggle', $flag->fresh());
        $this->assertFalse($flags->enabled('new-thing'));
    }

    public function test_a_flag_key_must_be_identifier_shaped(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateFeatureFlag::class)
            ->fillForm(['key' => 'Not A Key!', 'name' => 'x', 'type' => 'boolean', 'rollout_percentage' => 100])
            ->call('create')
            ->assertHasFormErrors(['key']);

        $this->assertDatabaseCount('feature_flags', 0);
    }

    public function test_clearing_the_targeting_means_everyone_not_nobody(): void
    {
        $this->actingAs($this->superAdmin());
        $flags = app(FeatureFlags::class);

        $flags->put(['key' => 'scoped', 'name' => 'Scoped', 'target_roles' => ['admin'], 'is_enabled' => true]);
        $this->assertFalse($flags->enabled('scoped', $this->plainUser()));

        Livewire::test(EditFeatureFlag::class, ['record' => FeatureFlag::query()->where('key', 'scoped')->value('id')])
            ->fillForm(['target_roles' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($flags->definitions()['scoped']['target_roles']);
        $this->assertTrue($flags->enabled('scoped', $this->plainUser()));
    }

    public function test_deleting_a_flag_flushes_the_cache(): void
    {
        $this->actingAs($this->superAdmin());
        $flags = app(FeatureFlags::class);

        $flag = $flags->put(['key' => 'gone', 'name' => 'Gone', 'is_enabled' => true]);
        $this->assertTrue($flags->enabled('gone'));

        Livewire::test(ListFeatureFlags::class)->callTableAction('delete', $flag);

        $this->assertFalse($flags->enabled('gone'));
    }

    public function test_the_token_list_shows_only_your_own_tokens_and_never_a_value(): void
    {
        $mine = $this->superAdmin();
        $theirs = $this->superAdmin();

        $own = $mine->createToken('nightly', ['read']);
        $other = $theirs->createToken('theirs', ['read']);

        $this->actingAs($mine);

        Livewire::test(ListApiTokens::class)
            ->assertCanSeeTableRecords([$own->accessToken])
            ->assertCanNotSeeTableRecords([$other->accessToken])
            ->assertDontSee($own->plainTextToken);
    }

    public function test_a_token_can_be_revoked_by_its_owner(): void
    {
        $user = $this->superAdmin();
        $token = $user->createToken('one', ['read']);

        $this->actingAs($user);

        Livewire::test(ListApiTokens::class)->callTableAction('revoke', $token->accessToken);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_revoking_others_clears_every_token_on_the_account(): void
    {
        $user = $this->superAdmin();
        $user->createToken('one', ['read']);
        $user->createToken('two', ['read']);
        $theirs = $this->superAdmin()->createToken('theirs', ['read']);

        $this->actingAs($user);

        Livewire::test(ListApiTokens::class)->callAction('revokeOthers');

        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $theirs->accessToken->id]);
    }
}
