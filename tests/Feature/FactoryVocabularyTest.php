<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Database\Factories\TodoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every factory must produce values the model's casts can represent.
 *
 * This bug class is nasty because a factory row only fails when something casts
 * it: the write succeeds, the row looks fine, and the failure surfaces much later
 * as an unrelated intermittent error. It was found when `ObligationFactory`
 * generated `critical` for `priority` — a risk_level value — which the shared
 * `Priority` enum does not declare, so roughly a quarter of runs exploded.
 *
 * So: every factory is instantiated repeatedly, every attribute is read back
 * through the model, and any exception fails here rather than somewhere unrelated.
 */
class FactoryVocabularyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * How many times each factory is instantiated. Enough that a value drawn 1-in-4
     * is essentially certain to appear.
     */
    private const ROUNDS = 25;

    /**
     * @return array<string, array{class-string}>
     */
    public static function factoryProvider(): array
    {
        return [
            'task' => [TaskFactory::class],
            'todo' => [TodoFactory::class],
            'meeting' => [MeetingFactory::class],
            'obligation' => [ObligationFactory::class],
        ];
    }

    /**
     * @param  class-string  $factory
     */
    #[DataProvider('factoryProvider')]
    public function test_a_factory_only_produces_values_its_model_can_cast(string $factory): void
    {
        $failures = [];

        for ($round = 0; $round < self::ROUNDS; $round++) {
            $model = $factory::new()->create();

            // Reading each cast attribute forces the cast. Any un-castable value
            // throws here, in a test named for exactly that.
            try {
                $model->refresh();

                foreach (array_keys($model->getAttributes()) as $attribute) {
                    $isVocabularised = str_contains($attribute, 'priorit')
                        || str_contains($attribute, 'status')
                        || str_contains($attribute, 'visibility');

                    if (! $isVocabularised) {
                        continue;
                    }

                    // Reading through getAttribute() forces the cast. A value the
                    // enum cannot represent throws here rather than in whichever
                    // request happened to read the row weeks later.
                    $model->getAttribute($attribute);
                }
            } catch (\Throwable $e) {
                $failures[] = $factory.' round '.$round.': '.get_class($e).' — '.$e->getMessage();

                break;
            }

            $model->delete();
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_obligation_priority_only_uses_priority_values(): void
    {
        for ($round = 0; $round < self::ROUNDS; $round++) {
            $obligation = ObligationFactory::new()->create();

            $this->assertContains(
                $obligation->priority?->value ?? $obligation->priority,
                Priority::values(),
                'obligations.priority is cast to Priority, so the factory must only produce Priority values.',
            );
        }
    }

    public function test_meeting_priority_only_uses_priority_values(): void
    {
        for ($round = 0; $round < self::ROUNDS; $round++) {
            $meeting = MeetingFactory::new()->create();

            $this->assertContains($meeting->priority, Priority::values());
        }
    }

    public function test_task_priority_only_uses_priority_values(): void
    {
        for ($round = 0; $round < self::ROUNDS; $round++) {
            $this->assertContains(TaskFactory::new()->create()->priority, Priority::values());
        }
    }

    public function test_risk_level_is_a_separate_vocabulary_and_may_still_use_critical(): void
    {
        // `critical` belongs here. The dashboard and the reports both match on it,
        // and dropping it would silently lose the highest risk band.
        $levels = ['low', 'medium', 'high', 'critical'];

        $seen = [];

        for ($round = 0; $round < self::ROUNDS * 4; $round++) {
            $seen[] = ObligationFactory::new()->create()->risk_level;
        }

        $this->assertEqualsCanonicalizing($levels, array_values(array_unique($seen)));
        $this->assertContains(
            'critical',
            $seen,
            '`critical` is a risk_level value and must still be producible.',
        );
    }

    public function test_todo_statuses_and_visibility_come_from_the_enums(): void
    {
        for ($round = 0; $round < self::ROUNDS; $round++) {
            $todo = TodoFactory::new()->create();

            $this->assertInstanceOf(WorkItemStatus::class, $todo->status);
            $this->assertInstanceOf(Visibility::class, $todo->visibility);
            $this->assertInstanceOf(Priority::class, $todo->priority);
        }
    }

    public function test_a_factory_row_round_trips_through_a_reload(): void
    {
        // The reload is the point: a value that only breaks on read survives the
        // initial create and fails the next request.
        $models = [
            TaskFactory::class => Task::class,
            MeetingFactory::class => Meeting::class,
            ObligationFactory::class => Obligation::class,
            TodoFactory::class => Todo::class,
        ];

        foreach ($models as $factory => $modelClass) {
            $model = $factory::new()->create();
            $reloaded = $modelClass::query()->withoutGlobalScopes()->findOrFail($model->id);

            $this->assertNotNull($reloaded, "{$factory} did not reload.");
        }
    }

    public function test_critical_is_not_a_priority(): void
    {
        // The regression this test exists for: `critical` is a risk_level value.
        // If a future obligation vocabulary genuinely needs it as a priority, add
        // the case to Priority and this assertion will say so.
        $this->assertNotContains('critical', Priority::values());
    }
}
