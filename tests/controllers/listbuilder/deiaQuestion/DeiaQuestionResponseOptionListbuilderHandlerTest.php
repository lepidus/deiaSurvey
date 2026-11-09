<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\listbuilder\deiaQuestion;

use APP\core\Request;
use APP\journal\Journal;
use APP\plugins\generic\deiaSurvey\classes\controllers\listbuilder\deiaQuestion\DeiaQuestionResponseOptionListbuilderHandler;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\Repository as DeiaResponseOptionRepository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use PKP\tests\DatabaseTestCase;

class DeiaQuestionResponseOptionListbuilderHandlerTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private const QUESTION_CONTEXT_ID = 1;
    private const OTHER_CONTEXT_ID = 2;

    private $deiaQuestionId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->deiaQuestionId = $this->createDeiaQuestion();

        $repository = app(DeiaResponseOptionRepository::class);
        $repository->add($repository->newDataObject([
            'deiaQuestionId' => $this->deiaQuestionId,
            'optionText' => self::TEST_OPTION_TEXT,
            'isTranslated' => false,
            'hasInputField' => true,
            'sequence' => 1,
        ]));
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
        $context = $this->getMockBuilder(Journal::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $context->method('getId')->willReturn($contextId);

        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getContext'])
            ->getMock();
        $request->method('getContext')->willReturn($context);

        $handler = new DeiaQuestionResponseOptionListbuilderHandler();
        $handler->deiaQuestionId = $this->deiaQuestionId;

        $loadData = new \ReflectionMethod($handler, 'loadData');
        $loadData->setAccessible(true);
        return $loadData->invoke($handler, $request);
    }
}
