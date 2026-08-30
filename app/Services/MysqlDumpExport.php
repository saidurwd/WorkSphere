<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

class MysqlDumpExport
{
    public function __construct(
        private readonly string $connection = 'mysql',
    ) {}

    public function dump(): string
    {
        $pdo = DB::connection($this->connection)->getPdo();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $database = $this->getDatabaseName();
        $tables = $this->getTableNames();

        $output = $this->buildHeader($database);

        $output .= "-- Lock all tables for the duration of the export\n";
        $output .= "FLUSH TABLES WITH READ LOCK;\n\n";

        foreach ($tables as $table) {
            $output .= $this->dumpTable($pdo, $table);
        }

        $output .= "UNLOCK TABLES;\n";

        return $output;
    }

    private function buildHeader(string $database): string
    {
        $now = now()->format('Y-m-d H:i:s');

        return <<<SQL

-- ------------------------------------------------------------
-- MySQL database dump
-- Database: `{$database}`
-- Generated: {$now}
-- ------------------------------------------------------------

/*!40101 SET NAMES utf8mb4 */;
/*!40101 SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40014 SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

SQL;
    }

    private function dumpTable(PDO $pdo, string $table): string
    {
        $output = '';

        $output .= "--\n-- Table structure for table `{$table}`\n--\n";

        $createStatement = $this->getCreateTable($pdo, $table);
        $output .= 'DROP TABLE IF EXISTS `'.$table."`;\n";
        $output .= $createStatement.";\n\n";

        $output .= "--\n-- Dumping data for table `{$table}`\n--\n";
        $output .= 'LOCK TABLES `'.$table.'` WRITE;'."\n";
        $output .= '/*!40000 ALTER TABLE `'.$table.'` DISABLE KEYS */;'."\n";

        $output .= $this->dumpRows($pdo, $table);

        $output .= '/*!40000 ALTER TABLE `'.$table.'` ENABLE KEYS */;'."\n";
        $output .= 'UNLOCK TABLES;'."\n\n";

        return $output;
    }

    private function dumpRows(PDO $pdo, string $table): string
    {
        $rows = $pdo->query('SELECT * FROM `'.$table.'`', PDO::FETCH_ASSOC);

        if ($rows === false) {
            return '';
        }

        $inserts = '';
        $buffer = '';
        $rowCount = 0;
        $lineThreshold = 50;

        foreach ($rows as $row) {
            $values = array_map(
                fn ($value) => $this->quoteValue($pdo, $value),
                array_values($row),
            );

            $rowSql = '('.implode(', ', $values).')';

            if ($rowCount === 0) {
                $columns = array_keys($row);
                $columnList = '`'.implode('`, `', $columns).'`';
                $insertPrefix = 'INSERT INTO `'.$table.'` ('.$columnList.') VALUES ';
                $buffer = $insertPrefix;
            }

            $buffer .= ($rowCount === 0 ? '' : ', ').$rowSql;
            $rowCount++;

            if ($rowCount % $lineThreshold === 0) {
                $inserts .= $buffer.";\n";
                $buffer = 'INSERT INTO `'.$table.'` (`'.implode('`, `', array_keys($row)).'`) VALUES ';
                $rowCount = 0;
            }
        }

        if ($buffer !== '' && ! str_ends_with($buffer, 'VALUES ')) {
            $inserts .= $buffer.";\n";
        }

        return $inserts;
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private function getCreateTable(PDO $pdo, string $table): string
    {
        $statement = $pdo->query(
            'SHOW CREATE TABLE `'.$table.'`',
            PDO::FETCH_ASSOC,
        );

        if ($statement === false) {
            throw new \RuntimeException('Unable to read schema for table `'.$table.'`.');
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $create = $row['Create Table'] ?? $row['Create View'] ?? '';

        if ($create === '') {
            throw new \RuntimeException('Unable to read schema for table `'.$table.'`.');
        }

        return 'CREATE TABLE `'.$table.'` '.trim(
            preg_replace('/^CREATE TABLE `'.preg_quote($table, '/').'`/i', '', $create),
        );
    }

    private function getTableNames(): array
    {
        $pdo = DB::connection($this->connection)->getPdo();

        $statement = $pdo->query('SHOW TABLES', PDO::FETCH_NUM);

        if ($statement === false) {
            return [];
        }

        return array_map(fn (array $row): string => $row[0], $statement->fetchAll());
    }

    private function getDatabaseName(): string
    {
        return DB::connection($this->connection)->getDatabaseName();
    }
}
