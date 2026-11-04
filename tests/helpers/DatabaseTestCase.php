<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\tests\PKPTestCase;
use Throwable;

abstract class DatabaseTestCase extends PKPTestCase
{
    private int $initialTransactionLevel;
    private array $originalConfig;
    private array $temporaryTables = [];

    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'request', 'plugins', 'hooks', 'templateManager'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConfig = Config::getData();
        $this->initialTransactionLevel = DB::transactionLevel();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            DB::rollBack($this->initialTransactionLevel);
        } finally {
            $cleanupError = null;
            try {
                foreach (array_reverse($this->temporaryTables) as $table) {
                    try {
                        DB::statement("DROP TEMPORARY TABLE IF EXISTS {$table}");
                    } catch (Throwable $exception) {
                        $cleanupError ??= $exception;
                    }
                }
            } finally {
                $config = &Config::getData();
                $config = $this->originalConfig;
                parent::tearDown();
            }
            if ($cleanupError !== null) {
                throw $cleanupError;
            }
        }
    }

    /** Whole-table migrations must only see empty, connection-local fixture tables. */
    protected function isolateDeiaTables(): void
    {
        foreach ([
            'deia_question_blocks',
            'deia_question_block_settings',
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings',
            'deia_responses',
            'deia_response_settings',
        ] as $table) {
            $definition = array_values((array) DB::selectOne("SHOW CREATE TABLE {$table}"))[1];
            $definition = preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition);
            // MySQL temporary tables do not support foreign keys; migration fixtures are self-contained.
            $definition = preg_replace('/^\s+CONSTRAINT .* FOREIGN KEY .*\n/m', '', $definition);
            $definition = preg_replace('/,\n\)/', "\n)", $definition);
            DB::statement($definition);
            $this->temporaryTables[] = $table;
        }
    }
}
