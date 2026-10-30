<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestionBlock;

use APP\core\Application;
use APP\core\Request;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridHandler;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridRow;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use PKP\linkAction\request\AjaxModal;

class DeiaQuestionBlockGridHandlerTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private int $contextId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeRequestRouter();
        $this->contextId = $this->createTestContext();
        $this->initializePluginLocaleData();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
    }

    public function testDoesNotActivateQuestionBlockWithoutQuestions(): void
    {
        $questionBlockId = $this->createInactiveQuestionBlock();
        $handler = new DeiaQuestionBlockGridHandler();

        $response = $handler->activateDeiaQuestionBlock([], $this->createActivationRequest($questionBlockId));

        $questionBlock = Repo::deiaQuestionBlock()->get($questionBlockId, $this->contextId);
        self::assertSame(0, $questionBlock->getActive());
        self::assertTrue($response->getStatus());
    }

    public function testRetrievesQuestionsFromInactiveBlockForPreview(): void
    {
        $this->initializeRequestRouter();
        $questionBlockId = $this->createInactiveQuestionBlock();
        $question = Repo::deiaQuestion()->newDataObject([
            'contextId' => $this->contextId,
            'questionBlockId' => $questionBlockId,
            'questionText' => ['en' => 'Funding question'],
            'questionDescription' => ['en' => 'Funding description'],
            'questionType' => DeiaQuestion::TYPE_TEXT_FIELD,
            'sequence' => 1,
            'isTranslated' => true,
        ]);
        $questionId = Repo::deiaQuestion()->add($question);

        $preview = (new DeiaDataService())->retrieveQuestionBlock($this->contextId, $questionBlockId);

        self::assertSame($questionBlockId, $preview['id']);
        self::assertSame('Empty block', $preview['title']);
        self::assertCount(1, $preview['questions']);
        self::assertSame($questionId, $preview['questions'][0]['questionId']);
        self::assertSame(DeiaQuestion::TYPE_TEXT_FIELD, $preview['questions'][0]['type']);
    }

    public function testQuestionBlockRowOpensPreviewTab(): void
    {
        $this->initializeRequestRouter();
        $questionBlockId = $this->createInactiveQuestionBlock();
        $questionBlock = Repo::deiaQuestionBlock()->get($questionBlockId, $this->contextId);
        $questionBlock->setData('active', 1);
        $row = new DeiaQuestionBlockGridRow();
        $row->setId($questionBlockId);
        $row->setData($questionBlock);

        $row->initialize(Application::get()->getRequest());

        $actions = $row->getActions();
        self::assertArrayHasKey('preview', $actions);
        self::assertInstanceOf(AjaxModal::class, $actions['preview']->getActionRequest());
        self::assertStringContainsString(
            'rowId=' . $questionBlockId,
            $actions['preview']->getActionRequest()->getUrl()
        );
        self::assertStringContainsString('preview=1', $actions['preview']->getActionRequest()->getUrl());
    }

    public function testQuestionnairePreviewActionFollowsExportAndImportActions(): void
    {
        $this->initializeRequestRouter();
        $handler = new DeiaQuestionBlockGridHandler();

        $handler->initialize(Application::get()->getRequest());

        $actions = $handler->getActions();
        self::assertSame(
            [
                'orderItems',
                'exportQuestionBlocks',
                'importQuestionBlocks',
                'previewQuestionnaire',
                'createDeiaQuestionBlock',
            ],
            array_keys($actions)
        );
        self::assertInstanceOf(AjaxModal::class, $actions['previewQuestionnaire']->getActionRequest());
        self::assertStringContainsString(
            'previewQuestionnaire',
            $actions['previewQuestionnaire']->getActionRequest()->getUrl()
        );
    }

    public function testQuestionnairePreviewRetrievesOnlyActiveBlocks(): void
    {
        $inactiveBlockId = $this->createInactiveQuestionBlock();
        $activeBlockId = $this->createInactiveQuestionBlock();
        $activeBlock = Repo::deiaQuestionBlock()->get($activeBlockId, $this->contextId);
        Repo::deiaQuestionBlock()->edit($activeBlock, ['active' => 1]);

        $questionBlocks = (new DeiaDataService())->retrieveQuestionBlocks($this->contextId);

        self::assertSame([$activeBlockId], array_column($questionBlocks, 'id'));
        self::assertNotContains($inactiveBlockId, array_column($questionBlocks, 'id'));
    }

    private function createInactiveQuestionBlock(): int
    {
        $questionBlock = Repo::deiaQuestionBlock()->newDataObject([
            'contextId' => $this->contextId,
            'title' => ['en' => 'Empty block'],
            'description' => ['en' => ''],
            'active' => 0,
            'sequence' => 1,
        ]);

        return Repo::deiaQuestionBlock()->add($questionBlock);
    }

    private function createActivationRequest(int $questionBlockId): object
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getUserVar', 'getContext', 'checkCSRF', 'getUser'])->getMock();
        $request->method('getUserVar')->with('deiaQuestionBlockId')->willReturn($questionBlockId);
        $request->method('getContext')->willReturn(Application::getContextDAO()->getById($this->contextId));
        $request->method('checkCSRF')->willReturn(true);
        $request->method('getUser')->willReturn(\APP\facades\Repo::user()->get($this->createTestUser()));
        return $request;
    }
}
