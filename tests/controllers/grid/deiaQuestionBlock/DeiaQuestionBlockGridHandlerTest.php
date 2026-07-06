<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestionBlock;

require_once(dirname(__DIR__, 4) . '/autoload.php');
require_once(dirname(__DIR__, 3) . '/helpers/TestHelperTrait.php');

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

        $handler->activateDeiaQuestionBlock([], $this->createActivationRequest($questionBlockId));

        $questionBlock = Repo::deiaQuestionBlock()->get($questionBlockId, $this->contextId);
        self::assertSame(0, $questionBlock->getActive());
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
                return new class {
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
