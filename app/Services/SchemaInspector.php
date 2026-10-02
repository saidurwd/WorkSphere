<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Throwable;

/**
 * Compares what the models declare against what the DATABASE ACTUALLY has.
 *
 * This exists because of a class of defect the test suite cannot see.
 *
 * `roles.description` was added in Phase 2 by EDITING a create-migration that had
 * already run on every real database. Laravel records a migration by name, so it is
 * never re-run, and the column was never created there — while the test suite,
 * which migrates from scratch on every run, always had it. More than a thousand
 * tests passed while saving a role failed with
 * `Unknown column 'description' in 'SET'`.
 *
 * Nothing in a fresh test database can detect that, because a fresh database is
 * built from the same files that carry the drift. The only independent record of
 * the real schema is the database itself — so this check runs against a live one,
 * and is reachable from the System Health screen.
 *
 * What it reports is a DIFFERENCE, not an error: a column present in the database
 * and absent from the model is reported too, because that is how a half-applied
 * migration announces itself.
 */
class SchemaInspector
{
    /**
     * Every mismatch between the models and the live schema.
     *
     * @return list<array{model: string, table: string, attribute: string, problem: string}>
     */
    public function mismatches(): array
    {
        $mismatches = [];

        foreach ($this->modelClasses() as $model) {
            try {
                $instance = new $model;
                $table = $instance->getTable();

                if (! Schema::hasTable($table)) {
                    $mismatches[] = [
                        'model' => $model,
                        'table' => $table,
                        'attribute' => '(table)',
                        'problem' => 'The table does not exist in this database.',
                    ];

                    continue;
                }

                $mismatches = array_merge($mismatches, $this->forModel($instance, $model, $table));
            } catch (Throwable $e) {
                // A model that cannot even be instantiated is reported rather than
                // swallowed: it is itself a drift symptom.
                $mismatches[] = [
                    'model' => $model,
                    'table' => '(unknown)',
                    'attribute' => '(model)',
                    'problem' => get_class($e).': '.$e->getMessage(),
                ];
            }
        }

        return $mismatches;
    }

    /**
     * @return array{checked: int, models: int, mismatches: list<array{model: string, table: string, attribute: string, problem: string}>}
     */
    public function report(): array
    {
        $mismatches = $this->mismatches();

        return [
            'models' => count($this->modelClasses()),
            'mismatches' => $mismatches,
            'healthy' => $mismatches === [],
        ];
    }

    /**
     * @return list<array{model: string, table: string, attribute: string, problem: string}>
     */
    protected function forModel(Model $instance, string $model, string $table): array
    {
        $mismatches = [];

        $guarded = $instance->getGuarded();

        foreach (array_unique(array_merge(
            $instance->getFillable(),
            array_keys($instance->getCasts()),
        )) as $attribute) {
            if ($attribute === $instance->getKeyName() || in_array($attribute, $guarded, true)) {
                continue;
            }

            if (! Schema::hasColumn($table, $attribute)) {
                $mismatches[] = [
                    'model' => $model,
                    'table' => $table,
                    'attribute' => $attribute,
                    // The two likely causes, named: a typo, or a column added by
                    // editing a migration that had already run here.
                    'problem' => 'Declared by the model but missing from the table. Either it is a typo, or the migration that adds it was edited after it had already run in this database.',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * @return list<class-string<Model>>
     */
    protected function modelClasses(): array
    {
        $classes = [];

        foreach ([
            base_path('app/Models'),
            base_path('Modules'),
        ] as $root) {
            foreach ($this->phpFilesIn($root) as $file) {
                $class = $this->classFromPath($file);

                if ($class === null || ! class_exists($class)) {
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
    protected function phpFilesIn(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $found = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $found[] = $file->getPathname();
            }
        }

        sort($found);

        return $found;
    }

    protected function classFromPath(string $absolute): ?string
    {
        $relative = str_replace(base_path().'/', '', $absolute);

        if (str_starts_with($relative, 'app/Models/')) {
            return 'App\\Models\\'.basename($relative, '.php');
        }

        if (preg_match('#^Modules/([^/]+)/app/Models/(.*)\.php$#', $relative, $m) === 1) {
            return "Modules\\{$m[1]}\\Models\\".str_replace('/', '\\', $m[2]);
        }

        return null;
    }
}
