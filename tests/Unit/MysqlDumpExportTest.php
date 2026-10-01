<?php

namespace Tests\Unit;

use App\Services\MysqlDumpExport;
use Tests\TestCase;

/**
 * The dump is generated as plain SQL by pure string assembly, so the exact
 * directives it emits can be asserted without a live MySQL server.
 */
class MysqlDumpExportTest extends TestCase
{
    private MysqlDumpExport $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new MysqlDumpExport('mysql');
    }

    public function test_header_names_the_database_and_opens_the_session(): void
    {
        $header = $this->exporter->renderHeader('worksphere');

        $this->assertStringContainsString('Database: `worksphere`', $header);
        $this->assertStringContainsString('SET NAMES utf8mb4', $header);
        $this->assertStringContainsString('UNIQUE_CHECKS = 0', $header);
        $this->assertStringContainsString('FOREIGN_KEY_CHECKS = 0', $header);
    }

    public function test_dump_opens_and_closes_a_read_lock(): void
    {
        $this->assertStringContainsString(
            'FLUSH TABLES WITH READ LOCK;',
            $this->exporter->renderLockTables(),
        );

        $this->assertSame("UNLOCK TABLES;\n", $this->exporter->renderUnlockTables());
    }

    public function test_table_render_emits_the_restorable_directives(): void
    {
        $sql = $this->exporter->renderTable(
            'users',
            '`id` bigint unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)',
            "INSERT INTO `users` (`id`) VALUES (1);\n",
        );

        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`;', $sql);
        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('LOCK TABLES `users` WRITE;', $sql);
        $this->assertStringContainsString('ALTER TABLE `users` DISABLE KEYS', $sql);
        $this->assertStringContainsString('INSERT INTO `users` (`id`) VALUES (1);', $sql);
        $this->assertStringContainsString('ALTER TABLE `users` ENABLE KEYS', $sql);
        $this->assertStringContainsString('UNLOCK TABLES;', $sql);
    }

    public function test_table_identifier_cannot_break_out_of_the_quoting(): void
    {
        $sql = $this->exporter->renderTable('a`b', '(`x` int)', '');

        // A name containing a backtick must not be able to close the identifier
        // and inject arbitrary SQL, so the backtick is doubled.
        $this->assertStringContainsString('DROP TABLE IF EXISTS `a``b`;', $sql);
        $this->assertStringContainsString('LOCK TABLES `a``b` WRITE;', $sql);
    }
}
