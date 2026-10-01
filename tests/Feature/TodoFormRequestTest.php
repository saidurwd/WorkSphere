<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\RecurrenceFrequency;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidatorInstance;
use Modules\Todos\Http\Requests\AssignTodoRequest;
use Modules\Todos\Http\Requests\RecurrenceTodoRequest;
use Modules\Todos\Http\Requests\StoreTodoCommentRequest;
use Modules\Todos\Http\Requests\StoreTodoRequest;
use Modules\Todos\Http\Requests\UpdateTodoRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Form Requests must validate against the enums, not a duplicated string list.
 *
 * The regression these guard against is quiet: an `in:pending,in_progress,…`
 * rule keeps working after a status is renamed and then silently rejects the new
 * value, or — worse — keeps accepting a value that no longer exists. Asserting
 * against the enum itself makes that impossible.
 */
class TodoFormRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validTodo(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Write the spec',
            'priority' => Priority::Medium->value,
            'visibility' => Visibility::Personal->value,
        ], $overrides);
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     */
    private function validate(string $requestClass, array $data): ValidatorInstance
    {
        /** @var FormRequest $request */
        $request = new $requestClass;

        return Validator::make($data, $request->rules());
    }

    /**
     * Validate through the request itself so cross-field logic in
     * `withValidator()` runs — `Validator::make()` skips it.
     *
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @return array<string, list<string>>
     */
    private function validateThroughRequest(string $requestClass, array $data): array
    {
        /** @var FormRequest $request */
        $request = new $requestClass;
        // merge(), not the constructor: Request's second constructor argument is
        // another Request instance, not an input array.
        $request->merge($data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        return [];
    }

    public function test_a_minimal_valid_payload_passes(): void
    {
        $validator = $this->validate(StoreTodoRequest::class, $this->validTodo());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_the_title_is_required(): void
    {
        $this->assertTrue($this->validate(StoreTodoRequest::class, $this->validTodo(['title' => '']))->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidEnumProvider(): array
    {
        return [
            'unknown priority' => ['priority'],
            'unknown visibility' => ['visibility'],
        ];
    }

    public function test_an_unknown_enum_value_is_rejected(): void
    {
        $this->assertTrue($this->validate(StoreTodoRequest::class, $this->validTodo(['priority' => 'blocker']))->fails());
        $this->assertTrue($this->validate(StoreTodoRequest::class, $this->validTodo(['visibility' => 'public']))->fails());
        $this->assertTrue($this->validate(StoreTodoRequest::class, $this->validTodo(['status' => 'done']))->fails());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function everyEnumValueProvider(): array
    {
        $cases = [];

        foreach (Priority::cases() as $case) {
            $cases["priority: {$case->value}"] = ['priority', $case->value];
        }

        foreach (Visibility::cases() as $case) {
            $cases["visibility: {$case->value}"] = ['visibility', $case->value];
        }

        foreach (WorkItemStatus::cases() as $case) {
            $cases["status: {$case->value}"] = ['status', $case->value];
        }

        return $cases;
    }

    /**
     * Every enum case must be accepted. A rule that rejects a real status turns
     * a valid form into a 422 with no way for the caller to tell why.
     */
    #[DataProvider('everyEnumValueProvider')]
    public function test_every_enum_case_is_accepted(string $field, string $value): void
    {
        $validator = $this->validate(StoreTodoRequest::class, $this->validTodo([$field => $value]));

        $this->assertFalse(
            $validator->errors()->has($field),
            "The {$field} rule rejects the real value '{$value}'.",
        );
    }

    public function test_no_rule_uses_a_hardcoded_enum_list(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../Modules/Todos/app/Http/Requests/StoreTodoRequest.php');

        $this->assertStringNotContainsString(
            "'in:",
            $source,
            'An in: list duplicates the enum vocabulary and silently drifts from it.',
        );

        $this->assertStringContainsString('Rule::enum(', $source);
    }

    public function test_due_date_must_not_precede_the_start_date(): void
    {
        $validator = $this->validate(StoreTodoRequest::class, $this->validTodo([
            'start_date' => '2026-10-10',
            'due_date' => '2026-10-01',
        ]));

        $this->assertTrue($validator->errors()->has('due_date'));
    }

    public function test_an_undated_todo_is_valid(): void
    {
        $this->assertFalse($this->validate(StoreTodoRequest::class, $this->validTodo())->fails());
    }

    public function test_update_rejects_status_outright(): void
    {
        // Status is a transition, not a field. Accepting it here would let a form
        // bypass the §3.2 graph entirely.
        //
        // `prohibited`, not merely absent: with no rule at all the field would
        // validate and be dropped by `safe()->all()`, so a client would be told
        // 200 while the To-Do stayed where it was. `prohibited` answers 422 and
        // names the field.
        $this->assertSame(['prohibited'], (new UpdateTodoRequest)->rules()['status']);

        $validator = $this->validate(UpdateTodoRequest::class, [
            'title' => 'Renamed',
            'priority' => 'high',
            'visibility' => 'personal',
            'status' => 'completed',
        ]);

        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_assigning_accepts_a_null_assignee(): void
    {
        // Returning a To-Do to the unassigned inbox is a real operation, so the key
        // must be allowed to be present-and-null. `required` would reject it — and
        // because rules run in order, `['required', 'nullable']` never reaches the
        // `nullable` at all.
        $validator = $this->validate(AssignTodoRequest::class, ['assignee_id' => null]);

        $this->assertFalse($validator->errors()->has('assignee_id'));
    }

    public function test_assigning_still_requires_the_key_to_be_present(): void
    {
        // `present`, not `sometimes`: omitting the field entirely would be an
        // ambiguous "leave it alone", and the endpoint has no other meaning to
        // give it.
        $validator = $this->validate(AssignTodoRequest::class, []);

        $this->assertTrue($validator->errors()->has('assignee_id'));
    }

    public function test_assigning_rejects_an_unknown_user(): void
    {
        $validator = $this->validate(AssignTodoRequest::class, ['assignee_id' => 999999]);

        $this->assertTrue($validator->errors()->has('assignee_id'));
    }

    public function test_update_requires_the_vocabulary_fields_it_edits(): void
    {
        $rules = (new UpdateTodoRequest)->rules();

        $this->assertTrue(in_array('required', $rules['priority'], true));
        $this->assertTrue(in_array('required', $rules['visibility'], true));
    }

    // ---- Recurrence ---------------------------------------------------------

    public function test_a_recurrence_rule_needs_a_frequency(): void
    {
        $validator = $this->validate(RecurrenceTodoRequest::class, [
            'start_date' => '2026-10-01',
            'max_occurrences' => 5,
        ]);

        $this->assertTrue($validator->errors()->has('frequency'));
    }

    public function test_an_unbounded_rule_is_refused(): void
    {
        // A rule with neither an end date nor a maximum would recur forever.
        $errors = $this->validateThroughRequest(RecurrenceTodoRequest::class, [
            'frequency' => RecurrenceFrequency::Monthly->value,
            'start_date' => '2026-10-01',
        ]);

        $this->assertArrayHasKey('end_date', $errors);
    }

    public function test_a_bounded_rule_validates_through_the_request_itself(): void
    {
        $errors = $this->validateThroughRequest(RecurrenceTodoRequest::class, [
            'frequency' => RecurrenceFrequency::Monthly->value,
            'start_date' => '2026-10-01',
            'max_occurrences' => 6,
        ]);

        $this->assertSame([], $errors);
    }

    public function test_a_bounded_rule_passes(): void
    {
        $validator = $this->validate(RecurrenceTodoRequest::class, [
            'frequency' => RecurrenceFrequency::Monthly->value,
            'interval' => 2,
            'start_date' => '2026-10-01',
            'end_date' => '2027-10-01',
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_weekdays_outside_one_to_seven_are_rejected(): void
    {
        $validator = $this->validate(RecurrenceTodoRequest::class, [
            'frequency' => RecurrenceFrequency::Weekly->value,
            'start_date' => '2026-10-01',
            'max_occurrences' => 4,
            'by_weekday' => [0, 8],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_the_built_rule_matches_the_documented_json_shape(): void
    {
        $instance = new RecurrenceTodoRequest;
        $instance->merge([
            'frequency' => 'monthly',
            'interval' => 1,
            'by_month_day' => 15,
            'start_date' => '2026-10-01',
            'end_date' => '2027-09-30',
            'max_occurrences' => 12,
        ]);

        $rule = $instance->recurrenceRule();

        $this->assertSame([
            'frequency' => 'monthly',
            'interval' => 1,
            'by_month_day' => 15,
            'start_date' => '2026-10-01',
            'end_date' => '2027-09-30',
            'max_occurrences' => 12,
        ], $rule);
    }

    public function test_a_cleared_rule_produces_null(): void
    {
        $instance = new RecurrenceTodoRequest;
        $instance->merge(['cleared' => true]);

        $this->assertNull($instance->recurrenceRule());
    }

    // ---- Comments -----------------------------------------------------------

    public function test_a_comment_needs_a_body(): void
    {
        $validator = $this->validate(StoreTodoCommentRequest::class, ['body' => '']);

        $this->assertTrue($validator->errors()->has('body'));
    }

    public function test_mentions_are_resolved_from_the_body_not_submitted(): void
    {
        $user = User::factory()->create(['name' => 'ada']);

        $instance = new StoreTodoCommentRequest;
        $instance->merge(['body' => 'ping @ada about this']);

        $this->assertSame([$user->id], $instance->mentionedUserIds());
    }

    public function test_a_mention_of_an_unknown_handle_resolves_to_nobody(): void
    {
        $instance = new StoreTodoCommentRequest;
        $instance->merge(['body' => 'ping @nobody-at-all']);

        $this->assertSame([], $instance->mentionedUserIds());
    }
}
