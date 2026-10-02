<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Every Blade template compiles, and none of them contains a blade trap.
 *
 * Both failure modes here are silent until a user opens the page, and both report
 * something that points away from the cause:
 *
 * - **A template that does not compile** returns a 500 whose message names a PHP
 *   variable — never the Blade directive that broke. The System Health screen lost
 *   its entire first half this way and the error read `unexpected variable
 *   "$statuses"`, which is a perfectly valid variable.
 * - **A Blade comment naming a directive** compiles into something else entirely:
 *   Blade extracts verbatim and script blocks BEFORE it extracts comments, so the
 *   directive is matched for real and consumes everything up to its closing tag.
 *
 * The second one is why this test compiles each file rather than grepping it, and
 * why it also lints the RESULT: a template can pass Blade's parser and still be
 * invalid PHP.
 */
class BladeCompilesTest extends TestCase
{
    /**
     * Every template compiles to valid PHP.
     *
     * ONE test over all of them rather than a data provider per file: a
     * provider is evaluated before the application boots, and a compile failure
     * needs the Blade compiler. It also reports every broken template at once,
     * which is what a person actually needs.
     */
    public function test_every_template_compiles_to_valid_php(): void
    {
        $failures = [];
        $counted = 0;

        foreach (self::paths() as $relative) {
            $source = (string) file_get_contents(base_path($relative));
            $counted++;

            // A leading PHP open tag makes Blade treat the file as plain PHP and
            // skip it entirely, so the compile "succeeds" and returns the source
            // unchanged — the first half of a template silently disappears.
            if (preg_match('/^\s*<\?php/', $source) === 1) {
                $failures[] = "{$relative} starts with a PHP open tag; Blade will not compile it.";

                continue;
            }

            $compiled = app('blade.compiler')->compileString($source);

            // A template can pass Blade's parser and still be invalid PHP, which is
            // why the RESULT is linted rather than merely produced.
            $temporary = tempnam(sys_get_temp_dir(), 'blade').'.php';
            file_put_contents($temporary, $compiled);

            $output = [];
            $exit = 0;
            exec('php -l '.escapeshellarg($temporary).' 2>&1', $output, $exit);
            @unlink($temporary);

            if ($exit !== 0) {
                $failures[] = "{$relative}: ".implode(' ', array_slice($output, 0, 2));
            }
        }

        // Assert on the count as well as the failures: a sweep that silently finds
        // no templates reports success while checking nothing.
        $this->assertGreaterThan(100, $counted, 'The template sweep found almost nothing to check.');
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /**
     * No template names a Blade directive inside a comment.
     *
     * Blade stores verbatim and script blocks before it stores comments, so a
     * directive inside `{{-- --}}` is matched for real. The symptom is a template
     * that silently loses every line up to the next closing tag — including its own
     * `@php` block.
     */
    public function test_no_comment_contains_a_directive_that_is_actually_a_directive(): void
    {
        // The closing half of each directive. A comment naming only the opening tag
        // is still dangerous: the store pairs an opening token with the next closing
        // one it finds ANYWHERE in the file.
        $dangerous = ['@php', '@endphp', '@verbatim', '@endverbatim', '@section', '@endsection', '@once', '@endonce'];

        $offenders = [];

        foreach (self::paths() as $file) {
            $source = (string) file_get_contents(base_path($file));

            if (preg_match_all('/\{\{--(.*?)--\}\}/s', $source, $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $index => $comment) {
                foreach ($dangerous as $token) {
                    if (str_contains($comment, $token)) {
                        $offenders[] = $file.' (comment '.($index + 1).') names '.$token;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These Blade comments name a directive, which Blade will match for real:\n".implode("\n", $offenders),
        );
    }

    /**
     * Every `x-` component a template uses actually exists.
     *
     * An unknown component is another render-time 500 that no compiler catches,
     * because it is resolved at runtime.
     */
    public function test_every_component_used_by_a_template_exists(): void
    {
        // Relative to the COMPONENTS directory, not to `views`. Including the
        // `components/` prefix would make every lookup miss, which would look like
        // a hundred broken templates rather than one wrong substring.
        $available = [];

        foreach ($this->componentRoots() as $root) {
            foreach ($this->phpFilesIn($root) as $file) {
                if (! str_ends_with($file, '.blade.php')) {
                    continue;
                }

                $relative = Str::after($file, $root.'/');

                // `form/input` -> `form.input`: a component in a subdirectory is
                // addressed with a dot, not a slash.
                $name = str_replace('/', '.', Str::replaceLast('.blade.php', '', $relative));

                $available[strtolower($name)] = true;
            }
        }

        $missing = [];

        foreach (self::paths() as $file) {
            $source = (string) file_get_contents(base_path($file));

            // The CLOSING `>` is part of the pattern on purpose. Without it,
            // `<x-mail::message>` — the mail package's namespace, not an
            // application component — matches as `mail` and is reported missing.
            preg_match_all('/<x-([a-z0-9-]+(?:\.[a-z0-9-]+)*)>/i', $source, $matches);

            foreach (array_unique($matches[1]) as $component) {
                // `slot` is Blade's own tag, not a component file.
                if ($component === 'slot') {
                    continue;
                }

                if (! isset($available[strtolower($component)])) {
                    $missing[] = $file.' uses <x-'.$component.'>';
                }
            }
        }

        $this->assertNotEmpty($available, 'No Blade components were found, so the check below would pass vacuously.');
        $this->assertSame([], $missing, implode("\n", $missing));
    }

    /**
     * Every directory that may hold Blade components.
     *
     * @return list<string>
     */
    private function componentRoots(): array
    {
        $roots = [];

        foreach (['resources/views', 'Modules'] as $base) {
            if (! File::isDirectory(base_path($base))) {
                continue;
            }

            // SELF_FIRST, not the default LEAVES_ONLY: the default iterator never
            // yields a DIRECTORY, so looking for one named `components` finds
            // nothing.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($base), \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
            );

            foreach ($iterator as $file) {
                if ($file->isDir() && $file->getFilename() === 'components') {
                    $roots[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        return array_values(array_unique($roots));
    }

    /**
     * Every file under a directory, at any depth, relative to the project root.
     *
     * Recursive by iterator rather than by `glob`. PHP's `glob` treats a
     * double-star segment as ONE directory level, not as "any depth" — which is
     * how a helper written to find every component silently found only the three
     * under `components/form/` and reported the other thirty as missing.
     *
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
            if ($file->isFile()) {
                $found[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        sort($found);

        return $found;
    }

    /**
     * Every template in the application, at any depth.
     *
     * Recursive because PHP's `glob` treats a double-star as a SINGLE directory
     * level — a `**\/*.blade.php` pattern silently misses `views/admin/system/`,
     * which is exactly where this bug lived.
     *
     * @return list<string>
     */
    private static function paths(): array
    {
        $found = [];

        // ONLY view directories. `Modules` also contains `app/`, whose PHP files
        // are commands and models — ordinary PHP that starts with an open tag and
        // has nothing to do with Blade.
        $roots = ['resources/views'];

        foreach (glob(base_path('Modules/*/resources/views')) ?: [] as $root) {
            if (is_dir((string) $root)) {
                $roots[] = str_replace(base_path().'/', '', (string) $root);
            }
        }

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($root), \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $found[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        sort($found);

        return $found;
    }
}
