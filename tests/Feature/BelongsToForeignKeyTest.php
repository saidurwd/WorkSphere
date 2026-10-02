<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Inferred foreign keys must exist.
 *
 * `belongsTo(SomeModel::class)` infers the foreign key from the method name;
 * `hasMany(Related::class)` infers it from the *related* model's basename. When
 * the column is named something else the relation silently never resolves.
 *
 * Two such bugs shipped and were only found by running the code:
 * `MeetingTemplate::agendaItems()` and `MeetingType::templates()`. This is the
 * general check so the next one fails a test rather than a request.
 *
 * The foreign keys are read from Eloquent's own resolved relations rather than
 * parsed out of the source, so the answer is the one the framework would
 * actually use at runtime.
 *
 * Resolving those relations flips global model state. It used to restore only two
 * of the three flags it disturbs — `preventAccessingMissingAttributes` and
 * `preventSilentlyDiscardingAttributes` — and left
 * `automaticallyEagerLoadRelationships` switched on for the rest of the process.
 * That leak made every test after this one run with relations silently
 * eager-loaded, which is what finally made the N+1 measurement suite fail with a
 * number that made no sense.
 *
 * `Tests\TestCase` now restores all four centrally, so nothing has to remember
 * which ones a given piece of code touches. The local `finally` stays because this
 * test wants the flags off for its OWN duration, not merely restored afterwards.
 */
class BelongsToForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_inferred_foreign_key_exists(): void
    {
        $this->withModelFlagsRestored(function (): void {
            $problems = [];

            foreach ($this->models() as $modelClass) {
                foreach ($this->inferredRelations($modelClass) as $method => $foreignKey) {
                    if (Schema::connection((new $modelClass)->getConnectionName())
                        ->hasColumn((new $modelClass)->getTable(), $foreignKey)) {
                        continue;
                    }

                    $problems[] = sprintf(
                        '%s::%s() infers `%s`, which does not exist on `%s`. Name it explicitly.',
                        class_basename($modelClass),
                        $method,
                        $foreignKey,
                        (new $modelClass)->getTable(),
                    );
                }
            }

            $this->assertSame([], $problems, implode("\n", $problems));
        });
    }

    /**
     * @return array<class-string<Model>, class-string<Model>>
     */
    private function models(): array
    {
        $models = [];

        foreach (array_merge(
            glob(base_path('app/Models/*.php')) ?: [],
            glob(base_path('Modules/*/app/Models/*.php')) ?: [],
        ) as $file) {
            $source = (string) file_get_contents($file);

            if (! preg_match('/namespace ([^;]+);/', $source, $ns)) {
                continue;
            }

            if (! preg_match('/(?:class|enum) (\w+)/', $source, $cn)) {
                continue;
            }

            $class = $ns[1].'\\'.$cn[1];

            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[$class] = $class;
            }
        }

        return $models;
    }

    /**
     * Every relation whose foreign key Eloquent had to infer — that is, every
     * `belongsTo`/`hasMany` declared with no explicit key.
     *
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    private function inferredRelations(string $modelClass): array
    {
        $model = new $modelClass;

        if (! Schema::connection($model->getConnectionName())->hasTable($model->getTable())) {
            return [];
        }

        $keys = [];

        foreach ((new \ReflectionClass($modelClass))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // An explicit foreign key is a second argument, so a relation method
            // taking zero or one argument is by definition inferred. Requiring
            // exactly one skipped every parameterless relation — which is most of
            // them, and is why this guard proved nothing when first written.
            if ($method->getNumberOfParameters() > 1) {
                continue;
            }

            try {
                $relation = $model->{$method->getName()}();
            } catch (\Throwable) {
                // A relation whose target cannot be resolved cannot be checked.
                continue;
            }

            if (! $relation instanceof BelongsTo && ! $relation instanceof HasMany) {
                continue;
            }

            $keys[$method->getName()] = $relation->getForeignKeyName();
        }

        return $keys;
    }
}
