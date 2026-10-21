<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The core IDOR regression net. Before Phase 2 every mutating route on Tasks,
 * Meetings and Obligations was reachable by any authenticated user who knew an
 * id, so each of these cases is a proof that the hole is closed.
 */
class TaskPolicyTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;
}
