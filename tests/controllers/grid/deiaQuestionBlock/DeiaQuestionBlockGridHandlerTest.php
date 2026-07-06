<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestionBlock;

use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridHandler;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\DB;
use PKP\tests\DatabaseTestCase;

class DeiaQuestionBlockGridHandlerTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private int $contextId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_question_blocks',
            'deia_question_block_settings',
            'deia_questions',
            'deia_question_settings',
            'notifications',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        DB::table('deia_question_settings')->delete();
        DB::table('deia_questions')->delete();
        DB::table('deia_question_block_settings')->delete();
        DB::table('deia_question_blocks')->delete();
    }

    public function testDoesNotActivateQuestionBlockWithoutQuestions(): void
    {
        $questionBlockId = $this->createInactiveQuestionBlock();
        $handler = new DeiaQuestionBlockGridHandler();

        $handler->activateDeiaQuestionBlock([], $this->createActivationRequest($questionBlockId));

        $questionBlock = Repo::deiaQuestionBlock()->get($questionBlockId, $this->contextId);
        self::assertSame(0, $questionBlock->getActive());
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
        return new class ($this->contextId, $questionBlockId) {
            public function __construct(private int $contextId, private int $questionBlockId)
            {
            }

            public function getUserVar(string $key): ?int
            {
                return $key === 'deiaQuestionBlockId' ? $this->questionBlockId : null;
            }

            public function getContext(): object
            {
                return new class ($this->contextId) {
                    public function __construct(private int $contextId)
                    {
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
}
