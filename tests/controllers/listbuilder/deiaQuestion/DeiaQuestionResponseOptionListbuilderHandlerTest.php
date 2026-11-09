<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\listbuilder\deiaQuestion;

use APP\core\Request;
use APP\journal\Journal;
use APP\plugins\generic\deiaSurvey\classes\controllers\listbuilder\deiaQuestion\DeiaQuestionResponseOptionListbuilderHandler;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\Repository as DeiaResponseOptionRepository;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DeiaQuestionResponseOptionListbuilderHandlerTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $deiaQuestionId;

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
        $rows = $this->loadOptionsAs($this->createTestContext());

        self::assertCount(1, $rows);
        self::assertTrue($rows[1][1]['hasInputField']);
    }

    public function testDoesNotListOptionsOfQuestionFromAnotherContext(): void
    {
        self::assertSame([], $this->loadOptionsAs($this->createTestContext() + 1));
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

        return (new \ReflectionMethod($handler, 'loadData'))->invoke($handler, $request);
    }
}
