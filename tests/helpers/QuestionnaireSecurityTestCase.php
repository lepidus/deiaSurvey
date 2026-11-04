<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

require_once __DIR__ . '/EmailAssociationTestCase.php';

use APP\core\Application;
use APP\core\PageRouter;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;

abstract class QuestionnaireSecurityTestCase extends EmailAssociationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Parent creates connection-local DEIA tables: never clear the application's tables.
        foreach (['deia_response_settings', 'deia_responses', 'deia_question_settings', 'deia_questions'] as $table) {
            DB::table($table)->delete();
        }
        $_SESSION = [];
    }

    protected function createTestContext(): int
    {
        return (int) $this->context->getId();
    }

    protected function addSchemaFile(string $name): void
    {
        // EmailAssociationTestCase registers all four plugin schemas.
    }

    protected function createDeiaQuestion(): int
    {
        return DB::table('deia_questions')->insertGetId([
            'context_id' => $this->createTestContext(), 'question_type' => DeiaQuestion::TYPE_TEXTAREA,
        ]);
    }

    protected function initializeRequestRouter(): void
    {
        Registry::delete('request');
        $app = Application::get();
        $router = new PageRouter();
        $router->setApplication($app);
        $router->setDispatcher($app->getDispatcher());
        $app->getRequest()->setRouter($router);
        $app->getRequest()->setDispatcher($app->getDispatcher());
    }
}
