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
        $firstQuestionBlock = new DeiaQuestionBlock();
        $firstQuestionBlock->setAllData([
            'id' => 1,
            'title' => [
                $this->locale => 'First question block'
            ],
            'sequence' => 1,
            'active' => 1
        ]);

        $secondQuestionBlock = new DeiaQuestionBlock();
        $secondQuestionBlock->setAllData([
            'id' => 2,
            'title' => [
                $this->locale => 'Second question block'
            ],
            'sequence' => 2,
            'active' => 1
        ]);

        $thirdQuestionBlock = new DeiaQuestionBlock();
        $thirdQuestionBlock->setAllData([
            'id' => 3,
            'title' => [
                $this->locale => 'Third question block'
            ],
            'sequence' => 3,
            'active' => 1
        ]);

        return [$thirdQuestionBlock, $secondQuestionBlock, $firstQuestionBlock];
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

        $expectedBlockHeader = ['First question block', 'Second question block', 'Third question block'];
        $this->assertEquals($expectedBlockHeader, $headers[0]);
    }
}
