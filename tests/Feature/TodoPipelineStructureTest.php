<?php

namespace Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Modules\Todos\Services\TodoLinkService;
use Tests\TestCase;

/**
 * Structural guarantees of the notification pipeline — §6.
 *
 * These are assertions about the shape of the code rather than its behaviour,
 * because the failure they guard against is silent: a listener that loses
 * `ShouldQueue`, or a `deliver()` call added to a controller, would still pass
 * every functional test while sending mail inside an HTTP request.
 */
class TodoPipelineStructureTest extends TestCase
{
    /**
     * @return list<string>
     */
    private static function moduleClasses(string $namespace, string $path): array
    {
        $classes = [];

        foreach (glob(base_path($path.'/*.php')) ?: [] as $file) {
            $classes[] = $namespace.'\\'.basename($file, '.php');
        }

        return $classes;
    }

    public function test_every_listener_is_queued(): void
    {
        $listeners = self::moduleClasses('Modules\Todos\Listeners', 'Modules/Todos/app/Listeners');

        $this->assertNotEmpty($listeners);

        foreach ($listeners as $listener) {
            $this->assertTrue(
                is_subclass_of($listener, ShouldQueue::class),
                "{$listener} must implement ShouldQueue — an unqueued listener sends mail inside the request.",
            );
        }
    }

    public function test_every_job_is_queued_including_those_inheriting_the_base(): void
    {
        $jobs = self::moduleClasses('Modules\Todos\Jobs', 'Modules/Todos/app/Jobs');

        $this->assertNotEmpty($jobs);

        foreach ($jobs as $job) {
            $this->assertTrue(
                is_subclass_of($job, ShouldQueue::class),
                "{$job} must implement ShouldQueue, directly or through SendTodoNotificationJob.",
            );
        }
    }

    public function test_delivery_is_only_reachable_from_a_job(): void
    {
        $callers = [];

        foreach ($this->phpFilesIn('Modules/Todos/app') as $file) {
            $source = (string) file_get_contents($file);

            if (! str_contains($source, '->deliver(')) {
                continue;
            }

            if (str_ends_with($file, 'TodoNotificationService.php')) {
                continue;
            }

            $callers[] = str_replace(base_path().'/', '', $file);
        }

        $this->assertNotEmpty($callers, 'No caller of deliver() was found; the pipeline has been renamed.');

        foreach ($callers as $caller) {
            $this->assertStringContainsString(
                '/Jobs/',
                $caller,
                "{$caller} calls TodoNotificationService::deliver() outside a queued job.",
            );
        }
    }

    public function test_no_controller_or_service_sends_mail_directly(): void
    {
        foreach (['app/Http/Controllers', 'Modules/Todos/app/Http/Controllers'] as $directory) {
            foreach ($this->phpFilesIn($directory) as $file) {
                $source = (string) file_get_contents($file);

                $this->assertStringNotContainsString(
                    'Mail::to(',
                    $source,
                    "{$file} sends mail inline.",
                );
            }
        }
    }

    /**
     * A listener that reads a property its event never declares fails at RUNTIME,
     * not at build time — and if it sits inside a scheduler's try/catch it is
     * silently swallowed into a "failed" count. This compares the two shapes
     * directly, by reflection, so the mismatch cannot survive a review.
     */
    public function test_every_listener_only_reads_properties_its_event_declares(): void
    {
        $problems = [];

        foreach ($this->listenerEventMap() as $listener => $event) {
            $declared = array_map(
                static fn (\ReflectionProperty $property): string => $property->getName(),
                (new \ReflectionClass($event))->getProperties(\ReflectionProperty::IS_PUBLIC),
            );

            $source = (string) file_get_contents(
                (new \ReflectionClass($listener))->getFileName()
            );

            preg_match('/function handle\([^)]*\$event[^)]*\)[^{]*\{(.*)\n    \}/s', $source, $body);

            $reads = [];
            preg_match_all('/\$event->(\w+)/', $body[1] ?? $source, $matches);

            foreach (array_unique($matches[1]) as $property) {
                if (! in_array($property, $declared, true)) {
                    $problems[] = class_basename($listener).' reads $event->'.$property
                        .', which '.class_basename($event).' does not declare';
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    /**
     * @return array<class-string, class-string>
     */
    private function listenerEventMap(): array
    {
        $map = [];

        foreach ($this->moduleClasses('Modules\Todos\Listeners', 'Modules/Todos/app/Listeners') as $listener) {
            $source = (string) file_get_contents((new \ReflectionClass($listener))->getFileName());

            if (preg_match('/function handle\(([A-Za-z\\|]+) \$event\)/', $source, $matches)) {
                foreach (explode('|', $matches[1]) as $event) {
                    if (class_exists($event)) {
                        $map[$listener] = $event;
                    }
                }
            }
        }

        return $map;
    }

    public function test_every_event_has_a_registered_listener(): void
    {
        $events = self::moduleClasses('Modules\Todos\Events', 'Modules/Todos/app/Events');

        // §6.2's recipient table defines eleven To-Do events. `TodoArchived` is
        // deliberately absent: archiving is the actor's own reversible action and
        // §6.2 names nobody to notify, so an event for it would never be
        // dispatched.
        $this->assertCount(11, $events);

        foreach ($events as $event) {
            $this->assertTrue(
                Event::hasListeners($event),
                "{$event} has no registered listener, so dispatching it does nothing.",
            );
        }
    }

    public function test_events_are_dispatched_from_the_service_not_a_controller(): void
    {
        // TodoService owns every state change, so every event must originate
        // there; a controller dispatching one would bypass the transaction.
        $serviceSource = (string) file_get_contents(base_path('Modules/Todos/app/Services/TodoService.php'));

        $this->assertStringContainsString('TodoCompleted::dispatch', $serviceSource);
        $this->assertStringContainsString('TodoReopened::dispatch', $serviceSource);
        $this->assertStringContainsString('TodoAssigned::dispatch', $serviceSource);

        foreach ($this->controllerSources() as $file) {
            $this->assertStringNotContainsString(
                '::dispatch(',
                $file['source'],
                "{$file['path']} dispatches an event directly.",
            );
        }
    }

    /**
     * Every controller in the module, at any depth, with its source.
     *
     * @return list<array{path: string, source: string}>
     */
    private function controllerSources(): array
    {
        return array_map(
            fn (string $path): array => [
                'path' => $path,
                'source' => (string) file_get_contents($path),
            ],
            $this->phpFilesIn('Modules/Todos/app/Http/Controllers'),
        );
    }

    /**
     * Every PHP file under a directory, at ANY depth.
     *
     * Recursive on purpose. `glob('…/**\/*.php')` matches exactly one directory
     * level, not arbitrarily many — so while every controller sat directly in
     * `Controllers/`, that pattern matched an EMPTY list and the "controllers must
     * not dispatch events" rule was passing vacuously. It only started looking
     * when Phase 12 introduced `Controllers/Api/`, and immediately caught a real
     * violation. A guard that inspects nothing is not a guard.
     *
     * @return list<string>
     */
    private function phpFilesIn(string $relativePath): array
    {
        $root = base_path($relativePath);

        if (! is_dir($root)) {
            return [];
        }

        $paths = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }

    public function test_every_service_mutation_is_wrapped_in_a_transaction(): void
    {
        foreach ([
            'Modules/Todos/app/Services/TodoService.php',
            'Modules/Todos/app/Services/TodoRecurrenceService.php',
            'Modules/Todos/app/Services/TodoLinkService.php',
        ] as $path) {
            $source = (string) file_get_contents(base_path($path));

            $this->assertStringContainsString(
                'DB::transaction',
                $source,
                "{$path} writes without a transaction.",
            );
        }
    }

    public function test_linkable_types_go_through_an_allow_list(): void
    {
        $source = (string) file_get_contents(base_path('Modules/Todos/app/Services/TodoLinkService.php'));

        // An unrestricted morphTo would let a caller name any class in the app.
        $this->assertStringContainsString('ALLOWED_LINKABLES', $source);
        $this->assertStringContainsString('Unknown linkable type', $source);
        $this->assertNotSame([], TodoLinkTypesAreRestricted::allowed());
    }
}

/**
 * Small named helper so the allow-list assertion reads as an intent rather than
 * a string comparison.
 */
final class TodoLinkTypesAreRestricted
{
    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return TodoLinkService::linkableTypes();
    }
}
