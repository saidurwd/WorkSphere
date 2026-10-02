<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\InteractsWithRoles;
use Tests\Support\PolicyCase;
use Tests\Support\PolicyMatrix;
use Tests\TestCase;

/**
 * The authorization matrix, executed.
 *
 * Two claims are being made here, and the second is the one that matters:
 *
 * 1. Every ability grants what it should and refuses what it should — asserted per
 *    row, against the Gate rather than against the policy object, so the registration
 *    in `AppServiceProvider` is exercised too.
 * 2. Every ability HAS a row. `test_every_policy_ability_has_a_case` reflects over
 *    the policy classes and fails when one does not, which is what stops "we tested
 *    the policies" from quietly becoming true of 90% of them.
 *
 * The denial assertion is not optional and not derived: each row carries its own
 * refused actor, and a row whose refusal happens to be a `viewAny` (which is true
 * for everyone by design) is marked as such in the registry.
 */
class PolicyMatrixTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * Every policy ability grants its allowed case.
     */
    #[DataProvider('allowedCases')]
    public function test_an_ability_grants_its_allowed_case(PolicyCase $case): void
    {
        [$actor, $arguments] = $case->allowedArguments();

        $this->assertTrue(
            Gate::forUser($actor)->allows($case->ability, ...$arguments),
            $case->label().' refused an actor it should allow.'.($case->note === '' ? '' : ' '.$case->note),
        );
    }

    /**
     * Every policy ability refuses its denied case.
     *
     * This is the security control: a row with only an allow case would prove a
     * permission string is spelled correctly and nothing about whether a user who
     * lacks it is stopped.
     */
    #[DataProvider('deniedCases')]
    public function test_an_ability_refuses_its_denied_case(PolicyCase $case): void
    {
        [$actor, $arguments] = $case->deniedArguments();

        // A NULL actor is a guest. `viewAny` is true for every authenticated user,
        // so the only meaningful refusal is an unauthenticated one — which is a
        // real control, and the reason the registry declares it rather than
        // leaving the row with no denial at all.
        $allowed = $actor === null
            ? Gate::allows($case->ability, ...$arguments)
            : Gate::forUser($actor)->allows($case->ability, ...$arguments);

        $this->assertFalse(
            $allowed,
            $case->label().' allowed an actor it should refuse.'.($case->note === '' ? '' : ' '.$case->note),
        );
    }

    /**
     * A super-admin passes every ability — the `Gate::before` bypass.
     *
     * Asserted as a sweep rather than per row because the bypass is global: if it
     * ever stops applying, EVERY policy silently starts denying administrators,
     * which is a far more expensive failure than one policy losing an ability.
     */
    public function test_a_super_admin_passes_every_ability(): void
    {
        $admin = $this->superAdmin();

        foreach (PolicyMatrix::cases() as $case) {
            $subject = $case->allowedArguments()[1];

            $this->assertTrue(
                Gate::forUser($admin)->allows($case->ability, ...$subject),
                "A super-admin was refused {$case->label()}.",
            );
        }
    }

    /**
     * Every public policy method has a case. This is the anti-drift assertion.
     */
    public function test_every_policy_ability_has_a_case(): void
    {
        $covered = [];

        foreach (PolicyMatrix::cases() as $case) {
            $covered[$case->policy.'::'.$case->ability] = true;
        }

        $missing = [];

        foreach (PolicyMatrix::policies() as $policy) {
            foreach ($this->abilities($policy) as $ability) {
                if (! isset($covered[$policy.'::'.$ability])) {
                    $missing[] = class_basename($policy).'::'.$ability;
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These policy abilities have no case in the matrix. Add one to PolicyMatrix, '
            .'including a DENIED actor — an allow-only case proves nothing about authorization.',
        );
    }

    /**
     * No matrix row names an ability that does not exist.
     *
     * The reverse direction: without this, a typo in a row name is invisible because
     * Laravel resolves an unknown ability through `Gate::before` or the fallback.
     */
    public function test_every_matrix_row_names_a_real_ability(): void
    {
        $unknown = [];

        foreach (PolicyMatrix::cases() as $case) {
            if (! in_array($case->ability, $this->abilities($case->policy), true)) {
                $unknown[] = $case->label();
            }
        }

        $this->assertSame([], $unknown, 'These matrix rows name an ability the policy does not declare.');
    }

    /**
     * Every registered policy is in the matrix's policy list, and every policy in
     * the list is registered.
     */
    public function test_every_policy_on_disk_is_in_the_matrix(): void
    {
        // A new policy class that nobody added to the matrix would be invisible to
        // the completeness check above, which only walks the listed policies.
        $this->assertSame(
            [],
            array_values(array_diff($this->policiesOnDisk(), PolicyMatrix::policies())),
            'These policy classes exist but are not in the matrix.',
        );
    }

    public function test_every_matrix_policy_exists_on_disk(): void
    {
        foreach (PolicyMatrix::policies() as $policy) {
            $this->assertTrue(
                class_exists($policy),
                "{$policy} is listed in the matrix but does not exist.",
            );
        }
    }

    public function test_an_always_allowed_row_explains_itself(): void
    {
        // `viewAny` is true for every authenticated user, so its refusal is a
        // guest. A row that is allowed for EVERYONE and denies nobody has slipped
        // through; requiring a note makes that visible at review time rather than
        // discovered when an endpoint leaks.
        foreach (PolicyMatrix::cases() as $case) {
            [$deniedActor] = $case->deniedArguments();

            if ($deniedActor === null) {
                $this->assertNotSame('', $case->note, "{$case->label()} needs a note explaining why it is always allowed.");
            }
        }
    }

    // ---- Data providers -----------------------------------------------------

    /**
     * @return array<string, array{0: PolicyCase}>
     */
    public static function allowedCases(): array
    {
        $cases = [];

        foreach (PolicyMatrix::cases() as $index => $case) {
            $cases[$case->label().'#'.$index] = [$case];
        }

        return $cases;
    }

    /**
     * @return array<string, array{0: PolicyCase}>
     */
    public static function deniedCases(): array
    {
        $cases = [];

        foreach (PolicyMatrix::cases() as $index => $case) {
            $cases[$case->label().'#'.$index] = [$case];
        }

        return $cases;
    }

    // ---- Reflection helpers -------------------------------------------------

    /**
     * The public methods of a policy that are Gate abilities.
     *
     * Protected helpers are excluded: `owns()`, `organizes()` and friends are
     * implementation, and a matrix row for `owns()` would be testing a private
     * detail through a name that no route can call.
     *
     * @param  class-string  $policy
     * @return list<string>
     */
    private function abilities(string $policy): array
    {
        $abilities = [];

        foreach ((new ReflectionClass($policy))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || str_starts_with($method->getName(), '__')) {
                continue;
            }

            $abilities[] = $method->getName();
        }

        sort($abilities);

        return $abilities;
    }

    /**
     * Every policy class on disk.
     *
     * @return list<class-string>
     */
    private function policiesOnDisk(): array
    {
        $policies = [];

        foreach (array_merge(['app/Policies'], glob(base_path('Modules/*/app/Policies')) ?: []) as $path) {
            foreach (glob(base_path($path).'/*.php') ?: [] as $file) {
                $relative = str_replace(base_path().'/', '', $file);

                if (str_starts_with($relative, 'app/Policies/')) {
                    $policies[] = 'App\\Policies\\'.basename($file, '.php');
                } elseif (preg_match('#^Modules/([^/]+)/app/Policies/(.*)\.php$#', $relative, $m) === 1) {
                    $policies[] = "Modules\\{$m[1]}\\Policies\\".basename($file, '.php');
                }
            }
        }

        sort($policies);

        return $policies;
    }
}
