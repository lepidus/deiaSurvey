<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(__DIR__ . '/../autoload.php');
require_once(__DIR__ . '/helpers/TestHelperTrait.php');
require_once(__DIR__ . '/../classes/DeiaDataService.php');

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DeiaDataServiceTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_block_settings',
            'deia_question_blocks',
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings',
        ]);
        parent::setUp();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);
        parent::tearDown();
    }

    public function testRejectsClientSuppliedQuestionType(): void
    {
        $questionId = $this->createDeiaQuestion();
        $service = new DeiaDataService();

        $this->expectException(\InvalidArgumentException::class);
        $service->normalizeResponses(
            1,
            ['question-' . $questionId . '-checkbox' => ['1']],
            [],
            true
        );
    }
}
