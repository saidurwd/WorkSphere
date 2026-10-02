<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * Every column a model declares must exist in the migrations.
 *
 * A model that writes a column no migration creates fails at RUNTIME, on the one
 * request that touches it. This catches a typo (`discription`), a column that
 * exists in no migration at all, and a model casting a column that was never
 * added.
 *
 * WHAT THIS DOES NOT CATCH, STATED PLAINLY
 *
 * It cannot catch a column added by EDITING a migration that had already run.
 * `roles.description` was exactly that: Phase 2 added it to
 * `2026_09_29_152053_create_roles_table`, which every real database had already
 * recorded, so the column was never created there — while the test suite, which
 * migrates from scratch on every run, always had it. Over a thousand tests passed
 * while saving a role failed with `Unknown column 'description' in 'SET'`.
 *
 * No test can catch that, because a fresh database is built from the same files
 * that carry the drift. The independent record of the real schema is the database
 * itself, which is why `php artisan doctor:schema` exists and what the System
 * Health screen runs.
 */
class SchemaFillsModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every model column the application declares, and whether the SCHEMA as
     * written provides it.
     *
     * @return list<string>
     */
    public function test_every_fillable_column_exists_in_the_migrations(): void
    {
        $missing = [];

        foreach ($this->modelClasses() as $model) {
            $instance = new $model;

            // Guarded attributes are excluded by definition: Eloquent refuses them
            // before a query is ever built.
            $guarded = $instance->getGuarded();

            foreach ($instance->getFillable() as $attribute) {
                if ($attribute === 'id' || in_array($attribute, $guarded, true)) {
                    continue;
                }

                if (! Schema::hasColumn($instance->getTable(), $attribute)) {
                    $missing[] = $model.'::$fillable declares "'.$attribute.'" but no migration creates `'
                        .$instance->getTable().'.'.$attribute.'`.';
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These model attributes have no column. If the column was added by editing a '
            ."migration that had already run, no real database will ever have it:\n".implode("\n", $missing),
        );
    }

    /**
     * Every cast column exists too.
     *
     * A cast on a missing column is quieter than a fillable one: the read returns
     * null and the failure surfaces somewhere else entirely.
     *
     * @return list<string>
     */
    public function test_every_cast_column_exists(): void
    {
        $missing = [];

        foreach ($this->modelClasses() as $model) {
            $instance = new $model;

            // `getCasts()` handles both spellings. `method_exists()` would be true
            // for a model's PROTECTED `casts()` and calling it from here would fail,
            // while the property spelling has no method at all.
            $casts = $instance->getCasts();

            foreach (array_keys($casts) as $attribute) {
                // `getCasts()` injects the primary key for incrementing models, so
                // `id` appears even when it is not declared.
                if ($attribute === $instance->getKeyName()) {
                    continue;
                }

                if (! Schema::hasColumn($instance->getTable(), $attribute)) {
                    $missing[] = $model.' casts "'.$attribute.'" but `'.$instance->getTable().'` has no such column.';
                }
            }
        }

        $this->assertSame([], $missing, implode("\n", $missing));
    }

    /**
     * The specific regression, named so a failure points straight at it.
     */
    public function test_roles_can_be_written_with_a_description(): void
    {
        $role = Role::query()->create([
            'name' => 'Auditor',
            'slug' => 'auditor-'.uniqid(),
            'description' => 'Read-only across the platform.',
        ]);

        $role->update(['description' => 'Updated.']);

        $this->assertSame('Updated.', $role->fresh()->description);
    }

    /**
     * Migrations are never edited once they can have run.
     *
     * Not mechanically checkable — a file's history is not visible from the
     * application — so this asserts the property that makes editing safe in the
     * first place: a migration that adds a column to an EXISTING table uses
     * `Schema::table()`, not a re-opened `Schema::create()`.
     *
     * The check is narrow and load-bearing rather than a lint: a migration that
     * calls `Schema::create()` for a table another migration also creates is the
     * signature of a create-migration being edited after the fact.
     *
     * @return list<string>
     */
    public function test_no_migration_creates_a_table_another_migration_also_creates(): void
    {
        $creators = [];

        foreach ($this->migrationFiles() as $file) {
            $source = (string) file_get_contents($file);

            if (preg_match_all("/Schema::create\(\s*'([a-z0-9_]+)'/i", $source, $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $table) {
                $creators[$table][] = [
                    'file' => basename($file),
                    // A create guarded by `hasTable()` is a deliberate, safe
                    // re-declaration — the framework's own `create_users_table`
                    // migration ships one. An UNGUARDED duplicate is the drift
                    // signature.
                    'guarded' => str_contains($source, 'hasTable'),
                ];
            }
        }

        $duplicated = [];

        foreach ($creators as $table => $files) {
            foreach (array_slice($files, 1) as $file) {
                if ($file['guarded'] === false) {
                    $duplicated[] = $table.' is created by '.implode(' and ', array_column($files, 'file'));
                }
            }
        }

        // An unguarded duplicate create is only ever the result of a create
        // migration being edited after it had run: the second copy is skipped as
        // already applied, so whatever it adds never reaches a real database.
        $this->assertSame([], $duplicated, implode("\n", $duplicated));
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        $classes = [];

        foreach (['app/Models', 'Modules'] as $root) {
            foreach ($this->phpFilesIn($root) as $file) {
                $class = $this->classFromPath($file);

                if ($class === null) {
                    continue;
                }

                $reflection = new ReflectionClass($class);

                if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                    continue;
                }

                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    /**
     * @return list<string>
     */
    private function phpFilesIn(string $root): array
    {
        if (! File::isDirectory(base_path($root))) {
            return [];
        }

        $found = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path($root), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $found[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        sort($found);

        return $found;
    }

    private function classFromPath(string $relative): ?string
    {
        if (str_starts_with($relative, 'app/Models/')) {
            return 'App\\Models\\'.basename($relative, '.php');
        }

        if (preg_match('#^Modules/([^/]+)/app/Models/(.*)\.php$#', $relative, $m) === 1) {
            return "Modules\\{$m[1]}\\Models\\".str_replace('/', '\\', $m[2]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        return File::glob(base_path('database/migrations/*.php')) ?: [];
    }
}
