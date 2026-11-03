<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\tests\PKPTestCase;

abstract class EmailAssociationTestCase extends PKPTestCase
{
    protected $context;
    protected $user;
    protected int $questionId;
    protected int $responseId;
    protected string $ciphertext;
    private array $originalConfig;
    private $originalEvents;
    private int $initialTransactionLevel;
    private array $temporaryTables = [];

    protected function getMockedRegistryKeys(): array
    {
        return array_merge(parent::getMockedRegistryKeys(), ['hooks', 'request', 'plugins', 'templateManager', 'sessionManager', 'localeFiles', 'dispatcher']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEvents = \Illuminate\Support\Facades\Event::getFacadeRoot();
        \Illuminate\Support\Facades\Event::swap(clone $this->originalEvents);
        $this->originalConfig = \PKP\config\Config::getData();
        $config = &\PKP\config\Config::getData();
        $config['security']['api_key_secret'] = 'isolated-deia980-test-secret';
        foreach (['deiaQuestionBlock', 'deiaQuestion', 'deiaResponse', 'deiaResponseOption'] as $schemaName) {
            \PKP\plugins\Hook::add('Schema::get::' . $schemaName, function ($hook, $params) use ($schemaName) {
                $params[0] = json_decode(file_get_contents(dirname(__DIR__, 2) . '/schemas/' . $schemaName . '.json'));
                return true;
            });
        }
        $this->ciphertext = (new \APP\plugins\generic\deiaSurvey\classes\DataEncryption())
            ->encryptString(serialize(['en' => 'Control answer']));
        $this->initialTransactionLevel = DB::connection()->transactionLevel();
        DB::connection()->beginTransaction();
        // Connection-local tables never expose existing questionnaire data to these tests.
        $tables = [
            'deia_question_blocks' => 'deia_question_block_id BIGINT PRIMARY KEY AUTO_INCREMENT, context_id BIGINT, seq FLOAT, is_active SMALLINT',
            'deia_questions' => 'deia_question_id BIGINT PRIMARY KEY AUTO_INCREMENT, context_id BIGINT, deia_question_block_id BIGINT, seq FLOAT, question_type BIGINT',
            'deia_responses' => 'deia_response_id BIGINT PRIMARY KEY AUTO_INCREMENT, deia_question_id BIGINT, user_id BIGINT NULL, external_id VARCHAR(255) NULL, external_type VARCHAR(255) NULL',
            'deia_response_options' => 'deia_response_option_id BIGINT PRIMARY KEY AUTO_INCREMENT, deia_question_id BIGINT, seq FLOAT',
        ];
        foreach (['question_block', 'question', 'response', 'response_option'] as $entity) {
            $tables['deia_' . $entity . '_settings'] = 'deia_' . $entity . '_setting_id BIGINT PRIMARY KEY AUTO_INCREMENT, deia_' . $entity . '_id BIGINT, locale VARCHAR(14) DEFAULT \'\', setting_name VARCHAR(255), setting_value LONGTEXT';
        }
        foreach ($tables as $table => $definition) {
            DB::connection()->statement("CREATE TEMPORARY TABLE {$table} ({$definition})");
            $this->temporaryTables[] = $table;
        }
        $identifier = 'deia980-' . bin2hex(random_bytes(8));
        $contextId = DB::table('journals')->insertGetId(['path' => $identifier, 'primary_locale' => 'en']);
        DB::table('journal_settings')->insert([
            'journal_id' => $contextId, 'locale' => '', 'setting_name' => 'supportedLocales', 'setting_value' => json_encode(['en']),
        ]);
        $this->context = Application::getContextDAO()->getById($contextId);
        $this->context->setName('DEIA test journal', 'en');
        $this->context->setData('contactName', 'Test contact');
        $this->context->setData('contactEmail', 'contact@example.org');
        $userId = DB::table('users')->insertGetId([
            'username' => $identifier, 'email' => $identifier . '@example.org',
            'password' => 'unused', 'date_registered' => date('Y-m-d H:i:s'),
            'date_last_login' => date('Y-m-d H:i:s'), 'disabled' => 0,
        ]);
        DB::table('user_settings')->insert([
            'user_id' => $userId, 'locale' => 'en', 'setting_name' => 'givenName', 'setting_value' => 'Test User',
        ]);
        $this->user = \APP\facades\Repo::user()->get($userId, true);
        (new \APP\plugins\generic\deiaSurvey\classes\DeiaDataService())->recordRegistrationEmail($this->user);
        $blockId = DB::table('deia_question_blocks')->insertGetId(['context_id' => $contextId, 'is_active' => 1]);
        $this->questionId = DB::table('deia_questions')->insertGetId([
            'context_id' => $contextId, 'deia_question_block_id' => $blockId, 'question_type' => 1,
        ]);
        $this->responseId = DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $this->questionId,
            'external_id' => $this->user->getEmail(), 'external_type' => 'email',
        ]);
        DB::table('deia_response_settings')->insert([
            'deia_response_id' => $this->responseId, 'setting_name' => 'responseValue', 'setting_value' => $this->ciphertext,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            DB::connection()->rollBack($this->initialTransactionLevel);
        } finally {
            try {
                foreach (array_reverse($this->temporaryTables) as $table) {
                    DB::connection()->statement("DROP TEMPORARY TABLE IF EXISTS {$table}");
                }
            } finally {
                $config = &\PKP\config\Config::getData();
                $config = $this->originalConfig;
                \Illuminate\Support\Facades\Event::swap($this->originalEvents);
                parent::tearDown();
            }
        }
    }
}
