<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\listbuilder\deiaQuestion;

require_once(dirname(__DIR__, 4) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\Repository as DeiaResponseOptionRepository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');
import('classes.core.Request');
import('lib.pkp.classes.security.Role');
import(
    'plugins.generic.deiaSurvey.classes.controllers.listbuilder.deiaQuestion.'
        . 'DeiaQuestionResponseOptionListbuilderHandler'
);

class DeiaQuestionResponseOptionListbuilderHandlerTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private const QUESTION_CONTEXT_ID = 1;
    private const OTHER_CONTEXT_ID = 2;

    private $deiaQuestionId;

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
            'deia_response_option_settings'
        ]);

        parent::setUp();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->deiaQuestionId = $this->createDeiaQuestion();

        $repository = app(DeiaResponseOptionRepository::class);
        $repository->add($repository->newDataObject([
            'deiaQuestionId' => $this->deiaQuestionId,
            'sequence' => 1,
            'optionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.text',
            'isTranslated' => false,
            'hasInputField' => true,
        ]));
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testListsOptionsOfQuestionFromCurrentContext(): void
    {
        $rows = $this->loadOptionsAs(self::QUESTION_CONTEXT_ID);

        self::assertCount(1, $rows);
        self::assertTrue($rows[1][1]['hasInputField']);
    }

    public function testDoesNotListOptionsOfQuestionFromAnotherContext(): void
    {
        self::assertSame([], $this->loadOptionsAs(self::OTHER_CONTEXT_ID));
    }

    private function loadOptionsAs(int $contextId): array
    {
        $context = $this->getMockBuilder(\Journal::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $context->method('getId')->willReturn($contextId);

        $request = $this->getMockBuilder(\Request::class)
            ->onlyMethods(['getContext'])
            ->getMock();
        $request->method('getContext')->willReturn($context);

        $handler = new \DeiaQuestionResponseOptionListbuilderHandler();
        $handler->_deiaQuestionId = $this->deiaQuestionId;

        $loadData = new \ReflectionMethod($handler, 'loadData');
        $loadData->setAccessible(true);
        return $loadData->invoke($handler, $request);
    }
}
