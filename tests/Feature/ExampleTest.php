<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The application root redirects an unauthenticated visitor to the login
     * screen rather than serving a page.
     */
    public function test_the_application_root_redirects_anonymous_visitors_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
