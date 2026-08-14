<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestionBlock;

require_once(dirname(__DIR__, 4) . '/autoload.php');
require_once(dirname(__DIR__, 3) . '/helpers/TestHelperTrait.php');
require_once(dirname(__DIR__, 4) . '/classes/DeiaDataService.php');

use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridRow;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.controllers.grid.GridHandler');
require_once(dirname(__DIR__, 4) . '/classes/controllers/grid/deiaQuestionBlock/DeiaQuestionBlockGridHandler.inc.php');

class DeiaQuestionBlockGridHandlerTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_blocks',
            'deia_question_block_settings',
            'deia_questions',
            'deia_question_settings',
            'notifications',
        ]);

        parent::setUp();

        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->deleteQuestionsAndBlocks();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testDoesNotActivateQuestionBlockWithoutQuestions(): void
    {
        $questionBlockId = $this->createInactiveQuestionBlock();
        $handler = new \DeiaQuestionBlockGridHandler();

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
            'questionText' => ['en_US' => 'Funding question'],
            'questionDescription' => ['en_US' => 'Funding description'],
            'questionType' => DeiaQuestion::TYPE_TEXT_FIELD,
            'sequence' => 1,
            'isTranslated' => true,
        ]);
        $questionId = Repo::deiaQuestion()->add($question);

        $preview = (new DeiaDataService())->retrieveQuestionBlock($this->contextId, $questionBlockId);

        self::assertSame($questionBlockId, $preview['id']);
        self::assertSame('Empty block', $preview['title']);
        self::assertCount(1, $preview['questions']);
        self::assertSame($questionId, $preview['questions'][0]['id']);
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

        $row->initialize(\Application::get()->getRequest());

        $actions = $row->getActions();
        self::assertArrayHasKey('preview', $actions);
        self::assertInstanceOf(\AjaxModal::class, $actions['preview']->getActionRequest());
        self::assertStringContainsString(
            'rowId=' . $questionBlockId,
            $actions['preview']->getActionRequest()->getUrl()
        );
        self::assertStringContainsString('preview=1', $actions['preview']->getActionRequest()->getUrl());
    }

    public function testQuestionnairePreviewActionFollowsExportAndImportActions(): void
    {
        $this->initializeRequestRouter();
        $handler = new \DeiaQuestionBlockGridHandler();

        $handler->initialize(\Application::get()->getRequest());

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
        self::assertInstanceOf(\AjaxModal::class, $actions['previewQuestionnaire']->getActionRequest());
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

    private function initializeRequestRouter(): void
    {
        $application = \Application::get();
        $request = $application->getRequest();
        import('classes.core.PageRouter');
        $router = new \PageRouter();
        $router->setApplication($application);
        import('lib.pkp.classes.core.Dispatcher');
        $dispatcher = new \Dispatcher();
        $dispatcher->setApplication($application);
        $router->setDispatcher($dispatcher);
        $request->setRouter($router);
    }

    private function createInactiveQuestionBlock(): int
    {
        $questionBlock = Repo::deiaQuestionBlock()->newDataObject([
            'contextId' => $this->contextId,
            'title' => ['en_US' => 'Empty block'],
            'description' => ['en_US' => ''],
            'active' => 0,
            'sequence' => 1,
        ]);

        return Repo::deiaQuestionBlock()->add($questionBlock);
    }

    private function createActivationRequest(int $questionBlockId): object
    {
        return new class ($this->contextId, $questionBlockId) {
            private $contextId;
            private $questionBlockId;

            public function __construct(int $contextId, int $questionBlockId)
            {
                $this->contextId = $contextId;
                $this->questionBlockId = $questionBlockId;
            }

            public function getUserVar(string $key): ?int
            {
                return $key === 'deiaQuestionBlockId' ? $this->questionBlockId : null;
            }

            public function getContext(): object
            {
                return new class ($this->contextId) {
                    private $contextId;

                    public function __construct(int $contextId)
                    {
                        $this->contextId = $contextId;
                    }

                    public function getId(): int
                    {
                        return $this->contextId;
                    }
                };
            }

            public function checkCSRF(): bool
            {
                return true;
            }

            public function getUser(): object
            {
                return new class () {
                    public function getId(): int
                    {
                        return 1;
                    }
                };
            }
        };
    }

    private function deleteQuestionsAndBlocks(): void
    {
        $dao = new \DAO();
        $dao->update('DELETE FROM deia_question_settings', [], true, false);
        $dao->update('DELETE FROM deia_questions', [], true, false);
        $dao->update('DELETE FROM deia_question_block_settings', [], true, false);
        $dao->update('DELETE FROM deia_question_blocks', [], true, false);
    }
}
