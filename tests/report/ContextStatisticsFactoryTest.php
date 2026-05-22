<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\report\classes\factories\ContextStatisticsFactory;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\DB;
use PKP\tests\DatabaseTestCase;

class ContextStatisticsFactoryTest extends DatabaseTestCase
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
            'deia_response_options',
            'deia_response_option_settings',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeRequestRouter();
        $this->clearDeiaTables();
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
    }

    public function testCreatesPrintingGuideFromActiveQuestionBlocks(): void
    {
        $activeBlockId = $this->createQuestionBlock('Active Block', true, 1);
        $inactiveBlockId = $this->createQuestionBlock('Inactive Block', false, 2);
        $closedQuestionId = $this->createQuestion($activeBlockId, 'Closed Question', DeiaQuestion::TYPE_RADIO_BUTTONS, 1);
        $openQuestionId = $this->createQuestion($activeBlockId, 'Open Question', DeiaQuestion::TYPE_TEXTAREA, 2);
        $this->createQuestion($inactiveBlockId, 'Inactive Question', DeiaQuestion::TYPE_TEXT_FIELD, 1);

        $firstOptionId = $this->createResponseOption($closedQuestionId, 'First Option', 1);
        $secondOptionId = $this->createResponseOption($closedQuestionId, 'Second Option', 2);

        $factory = new ContextStatisticsFactory($this->contextId);

        $this->assertEquals(
            [
                [
                    'blockTitle' => 'Active Block',
                    'questionId' => $closedQuestionId,
                    'questionText' => 'Closed Question',
                    'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
                    'responseOptions' => [
                        ['id' => $firstOptionId, 'text' => 'First Option'],
                        ['id' => $secondOptionId, 'text' => 'Second Option'],
                    ],
                ],
                [
                    'blockTitle' => 'Active Block',
                    'questionId' => $openQuestionId,
                    'questionText' => 'Open Question',
                    'questionType' => DeiaQuestion::TYPE_TEXTAREA,
                    'responseOptions' => [],
                ],
            ],
            $factory->createContextStatsPrintingGuide()
        );
    }

    private function createQuestionBlock(string $title, bool $active, int $sequence): int
    {
        $questionBlock = Repo::deiaQuestionBlock()->newDataObject([
            'contextId' => $this->contextId,
            'title' => ['en' => $title],
            'description' => ['en' => ''],
            'active' => $active,
            'sequence' => $sequence,
        ]);

        return Repo::deiaQuestionBlock()->add($questionBlock);
    }

    private function createQuestion(int $questionBlockId, string $text, int $questionType, int $sequence): int
    {
        $question = Repo::deiaQuestion()->newDataObject([
            'contextId' => $this->contextId,
            'questionBlockId' => $questionBlockId,
            'sequence' => $sequence,
            'questionType' => $questionType,
            'isDefaultQuestion' => false,
            'isTranslated' => true,
            'questionText' => ['en' => $text],
            'questionDescription' => ['en' => ''],
        ]);

        return Repo::deiaQuestion()->add($question);
    }

    private function createResponseOption(int $questionId, string $text, int $sequence): int
    {
        $responseOption = Repo::deiaResponseOption()->newDataObject([
            'deiaQuestionId' => $questionId,
            'optionText' => ['en' => $text],
            'isTranslated' => true,
            'hasInputField' => false,
            'sequence' => $sequence,
        ]);

        return Repo::deiaResponseOption()->add($responseOption);
    }

    private function clearDeiaTables(): void
    {
        DB::table('deia_response_option_settings')->delete();
        DB::table('deia_response_options')->delete();
        DB::table('deia_question_settings')->delete();
        DB::table('deia_questions')->delete();
        DB::table('deia_question_block_settings')->delete();
        DB::table('deia_question_blocks')->delete();
    }
}
