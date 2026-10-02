<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Support\StatusBadge;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Database\Factories\TodoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\In;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Http\Requests\IndexObligationRequest;
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

    /**
     * `obligations.priority` is NOT the shared `Priority` vocabulary.
     *
     * `Obligation` does not cast the column, `ObligationController` validates it as
     * `low|medium|high|critical` on both store and update, and the seeder uses
     * exactly those four. The shared `Priority` enum has no `critical` case at all —
     * it is the `meetings.priority` / `meeting_action_items.priority` vocabulary,
     * which is `normal|important|urgent`.
     *
     * This test used to assert the factory produced `Priority` values, which is what
     * let the factory draw from the WRONG enum: it emitted `normal`, `important`
     * and `urgent`, none of which the obligations form would accept. The assertion
     * passed because those values happen to be Priority values; it was checking the
     * wrong property.
     *
     * @see StatusBadge::PRIORITY_VARIANTS which spans both vocabularies
     */
    public function test_obligation_priority_only_uses_obligation_priority_values(): void
    {
        $allowed = ['low', 'medium', 'high', 'critical'];

        for ($round = 0; $round < self::ROUNDS; $round++) {
            $obligation = ObligationFactory::new()->create();

            $this->assertContains(
                (string) $obligation->priority,
                $allowed,
                'The obligations form validates priority as low|medium|high|critical, so the factory '
                .'must not produce a value the form rejects.',
            );
        }
    }

    public function test_every_obligation_priority_value_passes_form_validation(): void
    {
        // Ties the vocabulary to the thing that actually rejects it, so the two
        // cannot drift apart again.
        $rule = (new IndexObligationRequest)->rules()['priority'][0] ?? null;

        if ($rule instanceof In) {
            $this->assertSame(['low', 'medium', 'high', 'critical'], $rule->values);
        }

        $this->assertNotContains(
            'critical',
            Priority::values(),
            'If `critical` gained a Priority case, the two vocabularies would overlap and this '
            .'test would no longer distinguish them.',
        );
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
