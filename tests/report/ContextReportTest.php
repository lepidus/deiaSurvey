<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\report\classes\ContextReport;

class ContextReportTest extends PKPTestCase
{
    private $contextReport;
    private $questionBlocks;
    private $locale = 'en';

    protected function setUp(): void
    {
        parent::setUp();
        $this->questionBlocks = $this->createTestQuestionBlocks();
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

    private function createTestContextReport(): ContextReport
    {
        $contextReport = new ContextReport();

        foreach ($this->questionBlocks as $questionBlock) {
            $contextReport->addQuestionBlock($questionBlock);
        }

        return $contextReport;
    }

    public function testContextReportHeaders()
    {
        $headers = $this->contextReport->getHeaders();

        $expectedBlockHeader = ['Question block number 1', 'Question block number 2', 'Question block number 3'];
        $this->assertEquals($expectedBlockHeader, $headers[0]);
    }
}
