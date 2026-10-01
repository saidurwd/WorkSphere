<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\MysqlDumpExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();

        $adminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin'],
        );

        $user->roles()->attach($adminRole->id);

        return $user;
    }

    /**
     * The real dump needs a live MySQL server. The controller takes its exporter
     * by injection, so the request path is exercised against a deterministic dump
     * and the SQL that dump contains is asserted separately in
     * {@see MysqlDumpExportTest}.
     */
    private function fakeExporter(string $sql = '-- fake dump'): MysqlDumpExport
    {
        $exporter = $this->createMock(MysqlDumpExport::class);
        $exporter->method('dump')->willReturn($sql);

        $this->app->instance(MysqlDumpExport::class, $exporter);

        return $exporter;
    }

    public function test_index_page_loads(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('dashboard.database-backups.index'));

        $response->assertStatus(200);
        $response->assertSee('Database Backup');
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.database-backups.index'))
            ->assertRedirect(route('login'));
    }

    public function test_store_creates_a_backup_file(): void
    {
        $this->fakeExporter();

        $response = $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'), [
                'name' => 'smoke-test',
            ]);

        // Phase 11 made this `back()`: the store is an action on the index
        // screen, which a plain `admin` can no longer reach now that backups are
        // super-admin only. A redirect to a forbidden page would be a dead end.
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);
        $this->assertStringStartsWith('backups/smoke-test_', $files[0]);
    }

    public function test_store_persists_the_exported_dump(): void
    {
        $this->fakeExporter('SELECT 1;');

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'), ['name' => 'content']);

        $files = Storage::disk('local')->files('backups');

        $this->assertNotEmpty($files);

        // Phase 11: the dump is ENCRYPTED at rest. A stored file readable in
        // plaintext would make a stolen backup a full data disclosure, so the
        // stored bytes are asserted NOT to be the dump and the decrypted value
        // asserted to be it.
        $stored = Storage::disk('local')->get($files[0]);

        $this->assertStringNotContainsString('SELECT 1;', $stored);
        $this->assertSame('SELECT 1;', Crypt::decryptString($stored));
    }

    public function test_store_sanitises_the_supplied_backup_name(): void
    {
        $this->fakeExporter();

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'), ['name' => '../../etc/passwd']);

        $files = Storage::disk('local')->files('backups');

        $this->assertNotEmpty($files);
        $this->assertStringNotContainsString('/', basename($files[0]));
    }

    public function test_download_returns_sql_file(): void
    {
        $this->fakeExporter();

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'));

        $files = Storage::disk('local')->files('backups');
        $filename = basename($files[0]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('dashboard.database-backups.download', $filename));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/sql');

        // The download is the one place the plaintext exists again. This test
        // uses the default fake payload, which is what was stored.
        $this->assertSame('-- fake dump', $response->streamedContent());
    }

    public function test_download_missing_file_returns_404(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('dashboard.database-backups.download', 'does-not-exist.sql'))
            ->assertStatus(404);
    }

    public function test_destroy_removes_backup_file(): void
    {
        $this->fakeExporter();

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'));

        $files = Storage::disk('local')->files('backups');
        $filename = basename($files[0]);

        $response = $this->actingAs($this->adminUser())
            ->delete(route('dashboard.database-backups.destroy', $filename));

        $response->assertRedirect();
        Storage::disk('local')->assertMissing('backups/'.$filename);
    }
}
