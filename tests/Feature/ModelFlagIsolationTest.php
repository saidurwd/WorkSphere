<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The test base must not leak Eloquent's global strictness flags.
 *
 * Those flags are process-wide statics, so a test that switches one on and does not
 * put it back changes every test that runs after it. The failure this prevents is
 * not hypothetical and not subtle: `BelongsToForeignKeyTest` resolves every
 * relation in the application, which turns on
 * `automaticallyEagerLoadRelationships`, and it restored only the other two flags.
 * Every test after it therefore ran with relations silently eager-loaded, which
 * showed up as an N+1 measurement reporting zero queries per row — a test failing
 * because of alphabetical position.
 *
 * So the isolation property is asserted directly, in a way that fails if the
 * central restore in `Tests\TestCase` is ever removed.
 */
class ModelFlagIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The flags as they were before this test deliberately dirtied them.
     *
     * Recorded rather than hard-coded because the baseline is not "everything
     * false": `AppServiceProvider` turns on
     * `preventSilentlyDiscardingAttributes` for the testing environment, so a test
     * that asserts a fixed value would be asserting the provider's behaviour as
     * well as the harness's.
     */
    private static ?array $baseline = null;

    /**
     * A test that changes every flag and leaks them, which is exactly the mistake.
     *
     * Each flag is set to the OPPOSITE of its current value, so a leak is
     * detectable whichever way it defaulted. The leak is proven contained by the
     * following test rather than asserted here.
     */
    public function test_a_test_that_changes_every_model_flag(): void
    {
        self::$baseline = self::flags();

        Model::preventLazyLoading(! Model::preventsLazyLoading());
        Model::preventSilentlyDiscardingAttributes(! Model::preventsSilentlyDiscardingAttributes());
        Model::preventAccessingMissingAttributes(! Model::preventsAccessingMissingAttributes());
        Model::automaticallyEagerLoadRelationships(! Model::isAutomaticallyEagerLoadingRelationships());

        $this->assertNotSame(
            self::$baseline,
            self::flags(),
            'This test failed to dirty the flags, so it cannot be proving anything.',
        );
    }

    public function test_the_base_case_restores_every_flag(): void
    {
        // Runs immediately after the test above, so the flags were left dirty on
        // purpose. If the central restore in `Tests\TestCase` is removed, this is
        // the assertion that notices — and it notices as a diff against the real
        // baseline rather than against a guess at it.
        $this->assertSame(
            self::$baseline,
            self::flags(),
            'A previous test leaked Eloquent\'s global strictness flags into this one.',
        );
    }

    /**
     * @return array<string, bool>
     */
    private static function flags(): array
    {
        return [
            'preventsLazyLoading' => Model::preventsLazyLoading(),
            'preventsSilentlyDiscardingAttributes' => Model::preventsSilentlyDiscardingAttributes(),
            'preventsAccessingMissingAttributes' => Model::preventsAccessingMissingAttributes(),
            'automaticallyEagerLoadsRelationships' => Model::isAutomaticallyEagerLoadingRelationships(),
        ];
    }

    public function test_the_helper_restores_flags_even_when_the_body_throws(): void
    {
        $before = Model::isAutomaticallyEagerLoadingRelationships();

        try {
            $this->withModelFlagsRestored(function () use ($before): void {
                Model::automaticallyEagerLoadRelationships(! $before);

                throw new \RuntimeException('boom');
            });

            $this->fail('The exception should have propagated.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        // A `try/finally` that only runs on the success path is the same bug with
        // better manners.
        $this->assertSame($before, Model::isAutomaticallyEagerLoadingRelationships());
    }

    public function test_a_flag_left_on_during_a_test_is_undone_after_it(): void
    {
        // The realistic shape: a test that needs strictness for its own body.
        // `Model::preventSilentlyDiscardingAttributes()` is what
        // `AppServiceProvider` turns on for the `local` and `testing`
        // environments, so a test that switches it off must not leave it off for
        // the rest of the process.
        $this->assertTrue(
            Model::preventsSilentlyDiscardingAttributes(),
            'The application enables this flag for the testing environment; if it is off, '
            .'the provider stopped doing that and this assertion is measuring the wrong thing.',
        );

        Model::preventSilentlyDiscardingAttributes(false);

        $this->assertFalse(Model::preventsSilentlyDiscardingAttributes());
    }
}
