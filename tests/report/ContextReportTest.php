<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\report\classes\ContextReport;

class ContextReportTest extends PKPTestCase
{
    private $contextReport;
    private $questionBlocks;
    private $questions;
    private $locale = 'en';

    protected function setUp(): void
    {
        parent::setUp();
        $this->questionBlocks = $this->createTestQuestionBlocks();
        $this->questions = $this->createTestQuestions();
        $this->contextReport = $this->createTestContextReport();
    }

    private function createTestQuestionBlocks(): array
    {
        $questionBlocks = [];
        for ($sequence = 3; $sequence > 0; $sequence--) {
            $questionBlock = new DeiaQuestionBlock();
            $questionBlock->setAllData([
                'id' => $sequence,
                'title' => [
                    $this->locale => "Question block number $sequence"
                ],
                'sequence' => $sequence,
                'active' => 1
            ]);
            $questionBlocks[] = $questionBlock;
        }

        return $questionBlocks;
    }

    private function createTestQuestions(): array
    {
        $questions = [];
        $questionsPerBlock = 3;
        foreach ($this->questionBlocks as $questionBlock) {
            for ($sequence = $questionsPerBlock; $sequence > 0; $sequence--) {
                $questionId = (($questionBlock->getSequence() - 1) * $questionsPerBlock) + $sequence;
                $question = new DeiaQuestion();
                $question->setAllData([
                    'id' => $questionId,
                    'questionBlockId' => $questionBlock->getId(),
                    'sequence' => $sequence,
                    'questionType' => DeiaQuestion::TYPE_CHECKBOXES,
                    'questionText' => [
                        $this->locale => "Question number $questionId"
                    ]
                ]);

                $questions[] = $question;
            }
        }

        return $questions;
    }

    private function createTestContextReport(): ContextReport
    {
        $contextReport = new ContextReport();

        foreach ($this->questionBlocks as $questionBlock) {
            $contextReport->addQuestionBlock($questionBlock);
        }

        foreach ($this->questions as $question) {
            $contextReport->addQuestion($question);
        }

        return $contextReport;
    }

    public function testReportGeneratesQuestionsPrintingGuide()
    {
        $printingGuide = $this->contextReport->getQuestionsPrintingGuide();
        $expectedPrintingGuide = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $this->assertEquals($expectedPrintingGuide, $printingGuide);
    }

    public function testContextReportHeaders()
    {
        $headers = $this->contextReport->getHeaders();

        $expectedBlocksHeader = ['Question block number 1', 'Question block number 2', 'Question block number 3'];
        $this->assertEquals($expectedBlocksHeader, $headers[0]);

        $expectedQuestionsHeader = [
            'Question number 1',
            'Question number 2',
            'Question number 3',
            'Question number 4',
            'Question number 5',
            'Question number 6',
            'Question number 7',
            'Question number 8',
            'Question number 9'
        ];
        $this->assertEquals($expectedQuestionsHeader, $headers[1]);
    }
}
