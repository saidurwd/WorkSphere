<?php

namespace Tests\Feature;

use SimpleXMLElement;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The module test directories are actually part of the suite.
 *
 * Phase 13's first finding: `phpunit.xml` registered only `tests/`, so four module
 * test directories existed, were empty, and nothing noticed. Registering them is
 * worth nothing unless it stays registered — a deleted `<directory>` entry is
 * silent, and the suite would go on reporting green while a fifth of the codebase
 * stopped being tested.
 *
 * This reads `phpunit.xml` as XML rather than as text, because a substring check
 * cannot tell `Modules/X/tests/Feature/` from `Modules/X/tests/Unit/`: deleting one
 * of the pair leaves the other, the text still matches, and the guard passes while
 * half the module's tests silently vanish. (That exact false negative is why this
 * test parses the file.)
 */
class ModuleTestWiringTest extends TestCase
{
    /** The directory each module must contribute, per suite. */
    private const SUITES = ['Unit', 'Feature'];

    /**
     * @return list<string>
     */
    private function modules(): array
    {
        $modules = [];

        foreach (glob(base_path('Modules/*')) ?: [] as $path) {
            if (is_dir($path) && is_file($path.'/composer.json')) {
                $modules[] = basename($path);
            }
        }

        sort($modules);

        return $modules;
    }

    /**
     * The directories `phpunit.xml` registers, keyed by suite name.
     *
     * @return array<string, list<string>>
     */
    private function configuredDirectories(): array
    {
        $xml = new SimpleXMLElement((string) file_get_contents(base_path('phpunit.xml')));

        $suites = [];

        foreach ($xml->testsuites->testsuite as $suite) {
            $name = (string) $suite['name'];

            $directories = [];

            foreach ($suite->directory as $directory) {
                $directories[] = trim((string) $directory);
            }

            $suites[$name] = $directories;
        }

        return $suites;
    }

    public function test_phpunit_declares_the_two_suites(): void
    {
        $suites = $this->configuredDirectories();

        // The suite names are load-bearing: `composer test` and the CI workflow
        // both select by them, so a renamed suite silently runs nothing.
        $this->assertSame(['Unit', 'Feature'], array_keys($suites));
    }

    public function test_every_module_and_suite_is_registered(): void
    {
        $suites = $this->configuredDirectories();

        foreach (self::SUITES as $suite) {
            foreach ($this->modules() as $module) {
                $expected = "Modules/{$module}/tests/{$suite}";

                $this->assertContains(
                    $expected,
                    $suites[$suite] ?? [],
                    "phpunit.xml does not register {$expected} for the {$suite} suite.",
                );
            }
        }
    }

    public function test_every_registered_module_directory_holds_a_real_test(): void
    {
        // Per MODULE, not per suite: a module legitimately has only feature tests,
        // and an empty `Unit` directory registered in phpunit.xml is harmless. What
        // is not harmless is a module whose whole test tree is empty — that makes
        // the wiring look done while contributing nothing.
        foreach ($this->test_files() as $module => $bySuite) {
            $this->assertNotEmpty(
                array_merge(...array_values($bySuite)),
                "Modules/{$module} contributes no tests at all.",
            );
        }
    }

    public function test_no_test_file_sits_outside_a_registered_directory(): void
    {
        $configured = $this->configuredDirectories();

        foreach ($this->modules() as $module) {
            foreach (self::SUITES as $suite) {
                $expected = "Modules/{$module}/tests/{$suite}";

                $this->assertContains(
                    $expected,
                    $configured[$suite] ?? [],
                    "Tests exist at {$expected} but phpunit.xml does not register it.",
                );
            }
        }
    }

    public function test_every_module_test_file_is_autoloadable(): void
    {
        // The composer mapping is `Modules\X\Tests\` onto the LOWERCASE `tests/`
        // directory, which is not the PSR-4 convention. If the mapping is lost, a
        // class stops loading and PHPUnit reports the directory as empty rather
        // than as an error — so the class names are checked directly.
        foreach ($this->test_classes() as $module => $classes) {
            $this->assertNotEmpty($classes, "Modules/{$module} has no test classes.");

            foreach ($classes as $class) {
                $this->assertTrue(
                    class_exists($class),
                    "{$class} is not autoloadable — the module test namespace mapping is wrong.",
                );

                $reflection = new \ReflectionClass($class);

                $testMethods = array_filter(
                    $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                    fn (\ReflectionMethod $method): bool => str_starts_with($method->getName(), 'test'),
                );

                $this->assertNotEmpty($testMethods, "{$class} declares no test methods.");
            }
        }
    }

    public function test_the_class_name_matches_the_file_name(): void
    {
        // Found the hard way while building this: a file declared
        // `MeetingTemplateTest` under a `MeetingTemplateAgendaTest.php` name.
        // PHPUnit loads test files by PATH, so the suite passed — and the class was
        // invisible to every autoloader, which is exactly what a module test
        // namespace mapping would fail to find.
        foreach ($this->test_classes() as $module => $classes) {
            foreach ($classes as $class) {
                $file = (new \ReflectionClass($class))->getFileName();

                $this->assertNotFalse($file);

                $this->assertSame(
                    (new \ReflectionClass($class))->getShortName(),
                    basename((string) $file, '.php'),
                    "A test file is named differently from the class it declares ({$class}).",
                );

                $this->assertStringContainsString(
                    "Modules/{$module}/tests/",
                    (string) $file,
                    "{$class} is in the wrong module's test directory.",
                );
            }
        }
    }

    public function test_the_shared_harness_is_reachable_from_module_tests(): void
    {
        // Module tests reuse `Tests\TestCase` and `Tests\InteractsWithRoles`. If
        // that mapping is lost, every module test fails to load — but if the
        // mapping is narrowed so only the harness moves, the module tests still
        // would not, which this catches by resolving one explicitly.
        $this->assertTrue(trait_exists(InteractsWithRoles::class));
        $this->assertTrue(class_exists(TestCase::class));
    }

    /**
     * Test files per module per suite, `.gitkeep` excluded.
     *
     * @return array<string, array<string, list<string>>>
     */
    private function test_files(): array
    {
        $files = [];

        foreach ($this->modules() as $module) {
            $bySuite = [];

            foreach (self::SUITES as $suite) {
                // Two globs rather than one with a brace pattern: PHP's `glob()` has
                // no brace expansion, so `tests/{Feature,Unit}/*.php` silently
                // matches nothing and "no tests found" becomes indistinguishable
                // from "no tests exist".
                $paths = array_merge(
                    glob(base_path("Modules/{$module}/tests/{$suite}/*.php")) ?: [],
                    glob(base_path("Modules/{$module}/tests/*{$suite}/*.php")) ?: [],
                );

                $bySuite[$suite] = array_values(array_filter(
                    $paths,
                    fn (string $file): bool => ! str_ends_with(basename($file), '.gitkeep'),
                ));
            }

            $files[$module] = $bySuite;
        }

        return $files;
    }

    /**
     * The FQCN each test file declares, so the file-to-class relationship can be
     * asserted instead of assumed.
     *
     * @return array<string, list<class-string>>
     */
    private function test_classes(): array
    {
        $classes = [];

        foreach ($this->test_files() as $module => $bySuite) {
            $names = [];

            foreach ($bySuite as $files) {
                foreach ($files as $file) {
                    $source = (string) file_get_contents($file);

                    if (preg_match('/^namespace\s+([^;]+);/m', $source, $ns) !== 1) {
                        continue;
                    }

                    if (preg_match('/^class\s+(\w+)/m', $source, $class) !== 1) {
                        continue;
                    }

                    $names[] = trim($ns[1]).'\\'.$class[1];
                }
            }

            sort($names);

            $classes[$module] = $names;
        }

        return $classes;
    }
}
