<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
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

    private function usesMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    public function test_index_page_loads(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('dashboard.database-backups.index'));

        $response->assertStatus(200);
        $response->assertSee('Database Backup');
    }

    public function test_store_creates_a_backup_file(): void
    {
        $this->markTestSkippedIfNotMysql();

        $response = $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'), [
                'name' => 'smoke-test',
            ]);

        $response->assertRedirect(route('dashboard.database-backups.index'));
        $response->assertSessionHas('success');

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);
        $this->assertStringStartsWith('backups/smoke-test_', $files[0]);
    }

    public function test_backup_file_contains_expected_mysql_directives(): void
    {
        $this->markTestSkippedIfNotMysql();

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'));

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);

        $content = Storage::disk('local')->get($files[0]);

        $this->assertStringContainsString('FLUSH TABLES WITH READ LOCK', $content);
        $this->assertStringContainsString('LOCK TABLES', $content);
        $this->assertStringContainsString('DROP TABLE IF EXISTS', $content);
        $this->assertStringContainsString('DISABLE KEYS', $content);
        $this->assertStringContainsString('ENABLE KEYS', $content);
        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('UNLOCK TABLES', $content);
    }

    public function test_download_returns_sql_file(): void
    {
        $this->markTestSkippedIfNotMysql();

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'));

        $files = Storage::disk('local')->files('backups');
        $filename = basename($files[0]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('dashboard.database-backups.download', $filename));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/sql');
    }

    public function test_download_missing_file_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('dashboard.database-backups.download', 'does-not-exist.sql'));

        $response->assertStatus(404);
    }

    public function test_destroy_removes_backup_file(): void
    {
        $this->markTestSkippedIfNotMysql();

        $this->actingAs($this->adminUser())
            ->post(route('dashboard.database-backups.store'));

        $files = Storage::disk('local')->files('backups');
        $filename = basename($files[0]);

        $response = $this->actingAs($this->adminUser())
            ->delete(route('dashboard.database-backups.destroy', $filename));

        $response->assertRedirect(route('dashboard.database-backups.index'));
        Storage::disk('local')->assertMissing('backups/'.$filename);
    }

    private function markTestSkippedIfNotMysql(): void
    {
        if (! $this->usesMysql()) {
            $this->markTestSkipped('Database backup dump requires a MySQL connection.');
        }
    }
}
