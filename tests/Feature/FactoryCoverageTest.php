<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Meetings\Models\MeetingVersion;
use Modules\Obligations\Models\EscalationRule;
use Modules\Tasks\Models\TaskTransfer;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoLink;
use Tests\TestCase;

/**
 * Factory coverage for EVERY model — Phase 13 target.
 *
 * The suite's credibility rests on fixtures being constructible, so this asserts
 * the property directly rather than trusting it: every model in the application
 * must resolve to a factory, and that factory must be able to build a row that
 * satisfies the schema.
 *
 * Two halves, deliberately separated:
 *
 * - **Resolution** is checked by REFLECTION over the source tree, so a model in a
 *   module nobody imports still counts. An import-based sweep would miss exactly
 *   the models nobody has touched yet.
 * - **Construction** actually inserts a row. A factory can resolve perfectly and
 *   still be unusable — a NOT NULL column with no default, or a unique index that
 *   a second `create()` collides on. Only inserting catches that.
 */
class FactoryCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Directories that hold models. `database/` and `vendor/` are excluded by
     * construction — this only walks the application's own namespace roots.
     *
     * @return list<string>
     */
    private function modelPaths(): array
    {
        return ['app/Models', 'Modules'];
    }

    /**
     * @return list<class-string<Model>>
     */
    private function allModelClasses(): array
    {
        $classes = [];

        foreach ($this->modelPaths() as $path) {
            foreach ($this->phpFilesIn($path) as $file) {
                $class = $this->classFromPath($file);

                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($class);

                if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                    continue;
                }

                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    private function classFromPath(string $file): ?string
    {
        $relative = str_replace(base_path().'/', '', $file);

        if (str_starts_with($relative, 'app/Models/')) {
            return 'App\\Models\\'.basename($file, '.php');
        }

        // Modules/<Name>/app/Models/<Sub>/<File>.php -> Modules\<Name>\Models\<Sub>\<File>
        if (preg_match('#^Modules/([^/]+)/app/Models/(.*)\.php$#', $relative, $matches) === 1) {
            $subpath = str_replace('/', '\\', $matches[2]);

            return "Modules\\{$matches[1]}\\Models\\{$subpath}";
        }

        return null;
    }

    /**
     * Recursive, because PHP's `glob()` treats a double-star segment as a single
     * directory level — so a model nested one folder deep would be invisible to a
     * factory sweep that claims to have checked everything.
     *
     * @return list<string>
     */
    private function phpFilesIn(string $relativePath): array
    {
        $root = base_path($relativePath);

        if (! File::isDirectory($root)) {
            return [];
        }

        $paths = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && ! str_contains($file->getFilename(), 'Factory')) {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }

    public function test_every_model_resolves_a_factory(): void
    {
        $missing = [];

        foreach ($this->allModelClasses() as $class) {
            // `Factory::factoryForModel()` rather than `$model->newFactory()`:
            // it is the resolution Laravel itself performs, including the
            // `Database\Factories\<Basename>Factory` convention that reaches
            // module models from a namespace that has no matching directory.
            $factory = Factory::factoryForModel($class);

            if ($factory === null) {
                $missing[] = $class;

                continue;
            }

            // A factory that names a model other than the one it was reached from
            // is worse than none: `Model::factory()` would silently build a
            // different row and every assertion downstream would be about the wrong
            // table.
            $this->assertSame(
                $class,
                $factory->modelName(),
                "{$class}::factory() builds a different model.",
            );
        }

        $this->assertSame([], $missing, 'These models have no factory.');
    }

    public function test_every_factory_can_actually_build_a_row(): void
    {
        foreach ($this->allModelClasses() as $class) {
            $model = $class::factory()->create();

            $this->assertTrue(
                $model->exists,
                "{$class}::factory()->create() did not persist a row.",
            );

            $this->assertNotNull(
                $model->getKey(),
                "{$class}::factory()->create() produced a row with no key.",
            );
        }
    }

    public function test_every_factory_can_build_ten_rows(): void
    {
        // The single-row case hides a unique index that a second row collides on,
        // and a test suite that only ever builds one row at a time would not find
        // it until some unrelated test happened to make two.
        foreach ($this->allModelClasses() as $class) {
            $rows = $class::factory()->count(10)->create();

            $this->assertCount(
                10,
                $rows,
                "{$class}::factory() cannot build ten rows — a unique constraint is not respected.",
            );
        }
    }

    public function test_the_factory_sweep_actually_finds_the_models(): void
    {
        // A coverage test that finds nothing is a coverage test that passes for the
        // wrong reason. This asserts the sweep sees a known floor of models, so a
        // path change that silently empties it fails loudly instead of reporting
        // "100% coverage" of nothing.
        $models = $this->allModelClasses();

        $this->assertGreaterThanOrEqual(50, count($models), 'The model sweep is finding too few models to be credible.');

        foreach ([
            User::class,
            Role::class,
            Todo::class,
            TodoLink::class,
            MeetingVersion::class,
            EscalationRule::class,
            TaskTransfer::class,
        ] as $expected) {
            $this->assertContains($expected, $models, "{$expected} is missing from the model sweep.");
        }
    }

    public function test_a_factory_states_are_usable(): void
    {
        // `TodoFactory` exposes named states and the rest of the suite depends on
        // them. A state that returns the factory unchanged would silently turn a
        // focused test into a generic one.
        $todo = Todo::factory()->titleOnly('A specific title')->create();

        $this->assertSame('A specific title', $todo->title);
        $this->assertNull($todo->description, 'titleOnly() must clear the optional description.');
    }

    /**
     * A factory is only a fixture if it is reachable from the model without an
     * import of its own. `Model::factory()` must work for every model — this
     * asserts the resolution Laravel itself performs, not the convention we
     * believe is in place.
     */
    public function test_every_model_resolves_through_the_framework_factory_helper(): void
    {
        foreach ($this->allModelClasses() as $class) {
            $this->assertInstanceOf(
                Factory::class,
                $class::factory(),
                "{$class}::factory() does not return a factory.",
            );
        }
    }

    /**
     * A model whose table name does not exist can never be built or queried. The
     * convention pluralises the class name, so a singular table — `meeting_tag_map`
     * among them — needs an explicit `$table`, and nothing else in the suite would
     * notice its absence while the model is unreachable dead code.
     */
    public function test_every_models_table_actually_exists(): void
    {
        foreach ($this->allModelClasses() as $class) {
            $instance = new $class;

            $this->assertTrue(
                Schema::hasTable($instance->getTable()),
                "{$class} maps to table {$instance->getTable()}, which does not exist.",
            );
        }
    }
}
