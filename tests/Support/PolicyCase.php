<?php

namespace Tests\Support;

use App\Models\User;

/**
 * One row of the authorization matrix: an ability, the actor it is GRANTED to,
 * the actor it is REFUSED to, and the subject it is evaluated against.
 *
 * The matrix exists because "we tested the policies" is not a claim anybody can
 * check. A policy gains an ability and nothing complains; the new ability is
 * simply unreachable-by-test until somebody reads the class and remembers. So
 * coverage here is a REFLECTION over the policy classes and a comparison against
 * this registry: add a method to a policy and the matrix test fails until the
 * ability has a case.
 *
 * Both an allowed and a denied actor are mandatory on every row. A row with only
 * an allow case proves the permission string is spelled right; it proves nothing
 * about a user who lacks it, which is the case that matters for authorization.
 */
/**
 * One row of the authorization matrix: an ability, the actor it is GRANTED to,
 * the actor it is REFUSED to, and the subject it is evaluated against.
 *
 * The matrix exists because "we tested the policies" is not a claim anybody can
 * check. A policy gains an ability and nothing complains; the new ability is
 * simply unreachable-by-test until somebody reads the class and remembers. So
 * coverage here is a REFLECTION over the policy classes and a comparison against
 * this registry: add a method to a policy and the matrix test fails until the
 * ability has a case.
 *
 * Both an allowed and a denied actor are mandatory on every row. A row with only
 * an allow case proves the permission string is spelled right; it proves nothing
 * about a user who lacks it, which is the case that matters for authorization.
 */
final class PolicyCase
{
    /**
     * @param  class-string  $policy  The policy class declaring the ability.
     * @param  string  $ability  The policy method under test.
     * @param  string  $subject  `class` for a class-scoped ability, `record` otherwise.
     * @param  callable(): array{0: User, 1: list<mixed>}  $allowed  Returns the granted actor and the gate arguments.
     * @param  callable(): array{0: User, 1: list<mixed>}  $denied  Returns the refused actor and the gate arguments.
     * @param  string  $note  Why the pair is the way it is, when the pair is surprising.
     */
    public function __construct(
        public readonly string $policy,
        public readonly string $ability,
        public readonly string $subject,
        public readonly mixed $allowed,
        public readonly mixed $denied,
        public readonly string $note = '',
    ) {}

    /**
     * @return array{User, list<mixed>}
     */
    public function allowedArguments(): array
    {
        return $this->gateArguments($this->allowedArgumentsRaw());
    }

    /**
     * @return array{User, list<mixed>}
     */
    public function deniedArguments(): array
    {
        return $this->gateArguments($this->deniedArgumentsRaw());
    }

    /**
     * @return array{User, list<mixed>}
     */
    private function allowedArgumentsRaw(): array
    {
        return ($this->allowed)();
    }

    /**
     * @return array{User, list<mixed>}
     */
    private function deniedArgumentsRaw(): array
    {
        return ($this->denied)();
    }

    /**
     * A class-scoped ability needs the CLASS, not a record: `Gate::allows('create')`
     * with no argument cannot resolve a policy at all and silently denies.
     *
     * @param  array{User, list<mixed>}  $arguments
     * @return array{User, list<mixed>}
     */
    private function gateArguments(array $arguments): array
    {
        [$actor, $gate] = $arguments;

        return [$actor, $gate === [] ? [PolicyMatrix::subjectClassFor($this->policy)] : $gate];
    }

    public function label(): string
    {
        return class_basename($this->policy).'::'.$this->ability;
    }
}
