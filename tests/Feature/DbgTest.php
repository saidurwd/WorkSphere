<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;
class DbgTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;
    public function test_dbg(): void
    {
        $u = $this->superAdmin(['system.health','system.settings','system.queue','system.schedule','system.flags','system.tokens']);
        foreach ([
            '/admin/system/health','/admin/system/settings','/admin/system/queue','/admin/system/schedule',
            '/admin/system/flags','/admin/system/tokens','/livez','/readyz',
        ] as $uri) {
            try {
                $r = $this->actingAs($u)->get($uri);
                fwrite(STDERR, sprintf("%-30s %d\n", $uri, $r->status()));
            } catch (\Throwable $e) { fwrite(STDERR, sprintf("%-30s THREW %s: %s\n", $uri, get_class($e), substr($e->getMessage(),0,140))); }
        }
        $this->assertTrue(true);
    }
}
