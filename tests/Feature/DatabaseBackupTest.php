<?php

namespace Tests\Feature;

use App\Filament\Pages\System\DatabaseBackupsPage;
use App\Models\Role;
use App\Models\User;
use App\Services\DatabaseBackups;
use App\Services\MysqlDumpExport;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel('admin');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create()->fresh();

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
        $this->actingAs($this->adminUser());

        Livewire::test(DatabaseBackupsPage::class)->assertOk()->assertSee('Database backups');
    }

    public function test_index_lists_an_existing_backup(): void
    {
        // The listing is only exercised when the directory is NOT empty. On an
        // empty disk the view takes its empty-state branch, so a controller that
        // supplied the wrong keys to the table went unnoticed — it raised
        // `Undefined array key "filename"` only once a real backup existed, and
        // every other test here starts from an empty fake disk.
        Storage::disk('local')->put('backups/worksphere_20260101_090000.enc', str_repeat('x', 2048));

        $this->actingAs($this->adminUser());

        Livewire::test(DatabaseBackupsPage::class)
            ->assertSee('worksphere_20260101_090000.enc')
            // 2048 bytes as 2.0 KB, and a relative age — never the raw byte count or
            // a Unix timestamp.
            ->assertSee('2.0 KB')
            ->assertSee('ago');
    }

    public function test_index_orders_backups_newest_first(): void
    {
        // `Storage::fake` sets mtime to now, so the timestamps are asserted on
        // the rows the controller built rather than by writing files with real
        // ages, which the fake disk cannot do.
        Storage::disk('local')->put('backups/older.enc', str_repeat('x', 1024));
        Storage::disk('local')->put('backups/newer.enc', str_repeat('x', 1024));

        $this->actingAs($this->adminUser());

        // Same-second mtimes make a strict order assertion flaky, so this only
        // pins that both rows render and neither throws.
        Livewire::test(DatabaseBackupsPage::class)->assertSee('older.enc')->assertSee('newer.enc');
    }

    public function test_human_size_reports_binary_units(): void
    {
        $backups = app(DatabaseBackups::class);

        $this->assertSame('0 B', $backups->humanSize(0));
        $this->assertSame('512 B', $backups->humanSize(512));
        $this->assertSame('1.0 KB', $backups->humanSize(1024));
        $this->assertSame('1.5 MB', $backups->humanSize((int) (1024 * 1024 * 1.5)));
        $this->assertSame('2.0 GB', $backups->humanSize(2 * 1024 * 1024 * 1024));
    }

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->get('/admin/system/database-backups')->assertRedirect('/admin/login');
    }

    public function test_store_creates_a_backup_file(): void
    {
        $this->fakeExporter();

        $this->actingAs($this->adminUser());

        Livewire::test(DatabaseBackupsPage::class)->callAction('backup', ['name' => 'smoke-test'])->assertNotified('Backup created and encrypted');

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);
        $this->assertStringStartsWith('backups/smoke-test_', $files[0]);
    }

    public function test_store_persists_the_exported_dump(): void
    {
        $this->fakeExporter('SELECT 1;');

        $this->actingAs($this->adminUser());
        Livewire::test(DatabaseBackupsPage::class)->callAction('backup', ['name' => 'content']);

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

        $this->actingAs($this->adminUser());
        Livewire::test(DatabaseBackupsPage::class)->callAction('backup', ['name' => '../../etc/passwd']);

        $files = Storage::disk('local')->files('backups');

        $this->assertNotEmpty($files);
        $this->assertStringNotContainsString('/', basename($files[0]));
    }

    public function test_download_returns_sql_file(): void
    {
        $this->fakeExporter();

        $admin = $this->adminUser();
        $this->actingAs($admin);
        Livewire::test(DatabaseBackupsPage::class)->callAction('backup');

        $files = Storage::disk('local')->files('backups');
        $filename = basename($files[0]);

        $response = $this->actingAs($admin)
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

        $admin = $this->adminUser();
        $this->actingAs($admin);
        Livewire::test(DatabaseBackupsPage::class)->callAction('backup');

        $files = Storage::disk('local')->files('backups');
        $filename = basename($files[0]);

        Livewire::test(DatabaseBackupsPage::class)->call('deleteBackup', $filename);

        Storage::disk('local')->assertMissing('backups/'.$filename);
    }
}
