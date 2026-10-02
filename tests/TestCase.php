<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * The shared test base.
 *
 * Beyond booting the application, this SNAPSHOTS Eloquent's global strictness
 * flags before every test and restores them afterwards.
 *
 * Those flags are process-wide statics, not per-test state. A test that turns one
 * on and does not put it back changes the behaviour of every test that runs after
 * it — which is how one class of tests can make another class of tests pass or
 * fail depending on alphabetical order, and how a suite ends up with failures that
 * cannot be reproduced by running the file alone.
 *
 * This was found, not hypothesised: `BelongsToForeignKeyTest` resolves every
 * relation in the application, which turns on
 * `preventAccessingMissingAttributes` AND `automaticallyEagerLoadRelationships`.
 * It restored the first two and not the third, so every test after it ran with
 * relations silently eager-loaded. It looked like a bug in the N+1 measurement
 * suite until the flag was printed and followed back to its source.
 *
 * Restoring centrally means the next test that touches a flag does not have to
 * know the flag exists.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * The flags, captured at the end of `setUp()`.
     *
     * @var array<string, bool>
     */
    private array $modelFlags = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Captured AFTER `parent::setUp()` so that anything a base test case or a
        // trait deliberately configured for the run is the baseline this test
        // returns to — not the framework's out-of-the-box default.
        $this->modelFlags = self::currentModelFlags();
    }

    protected function tearDown(): void
    {
        self::restoreModelFlags($this->modelFlags);

        parent::tearDown();
    }

    /**
     * Run a callback and put Eloquent's global flags back however it ends.
     *
     * For a test that needs the flags off deliberately and does not want the
     * central restore to be the only thing standing between it and a leak.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withModelFlagsRestored(callable $callback): mixed
    {
        $flags = self::currentModelFlags();

        try {
            return $callback();
        } finally {
            self::restoreModelFlags($flags);
        }
    }

    /**
     * @return array<string, bool>
     */
    private static function currentModelFlags(): array
    {
        return [
            'preventsLazyLoading' => Model::preventsLazyLoading(),
            'preventsSilentlyDiscardingAttributes' => Model::preventsSilentlyDiscardingAttributes(),
            'preventsAccessingMissingAttributes' => Model::preventsAccessingMissingAttributes(),
            'automaticallyEagerLoadsRelationships' => Model::isAutomaticallyEagerLoadingRelationships(),
        ];
    }

    /**
     * @param  array<string, bool>  $flags
     */
    private static function restoreModelFlags(array $flags): void
    {
        Model::preventLazyLoading($flags['preventsLazyLoading'] ?? false);
        Model::preventSilentlyDiscardingAttributes($flags['preventsSilentlyDiscardingAttributes'] ?? false);
        Model::preventAccessingMissingAttributes($flags['preventsAccessingMissingAttributes'] ?? false);
        Model::automaticallyEagerLoadRelationships($flags['automaticallyEagerLoadsRelationships'] ?? false);
    }
}
