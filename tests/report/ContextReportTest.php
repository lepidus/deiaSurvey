<?php

namespace APP\plugins\generic\deiaSurvey\tests\report;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DeiaResponse;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\report\classes\ContextReport;

import('lib.pkp.tests.PKPTestCase');

class ContextReportTest extends \PKPTestCase
{
    private const CSV_FILE_PATH = '/tmp/deia_survey_context_report_test.csv';

    private $contextReport;
    private $questionBlocks;
    private $questions;
    private $locale = 'en_US';

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
        for ($sequence = 1; $sequence <= 3; $sequence++) {
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
            for ($sequence = 1; $sequence <= $questionsPerBlock; $sequence++) {
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

    private function addUserTestResponsesToReport(array $userData)
    {
        $responseId = 10;
        $responseOptionId = 123;

        foreach ($this->questions as $question) {
            $response = new DeiaResponse();
            $response->setAllData([
                'id' => $responseId,
                'userId' => $userData['userId'],
                'externalId' => $userData['externalId'],
                'deiaQuestionId' => $question->getId(),
                'responseValue' => [$responseOptionId]
            ]);
            $this->contextReport->addResponse($response);

            $responseOption = new DeiaResponseOption();
            $responseOption->setAllData([
                'id' => $responseOptionId,
                'deiaQuestionId' => $question->getId(),
                'isTranslated' => true,
                'optionText' => [
                    $this->locale => "Response option $responseOptionId"
                ]
            ]);
            $this->contextReport->addResponseOption($responseOption);

            $responseId++;
            $responseOptionId++;
        }
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

    public function testWritesContextReport()
    {
        $testUsersData = [
            ['userId' => 234, 'externalId' => null],
            ['userId' => 235, 'externalId' => null]
        ];
        foreach ($testUsersData as $userData) {
            $this->addUserTestResponsesToReport($userData);
        }

        $this->contextReport->writeReport(self::CSV_FILE_PATH);

        $this->assertFileExists(self::CSV_FILE_PATH);
        $csvFile = fopen(self::CSV_FILE_PATH, 'r');
        $UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fread($csvFile, strlen($UTF8_BOM));

        $expectedBlocksHeader = ['Question block number 1', 'Question block number 2', 'Question block number 3'];
        $blocksHeader = fgetcsv($csvFile);
        $this->assertEquals($expectedBlocksHeader, $blocksHeader);

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
        $questionsHeader = fgetcsv($csvFile);
        $this->assertEquals($expectedQuestionsHeader, $questionsHeader);

        $expectedResponses = [
            'Response option 123',
            'Response option 124',
            'Response option 125',
            'Response option 126',
            'Response option 127',
            'Response option 128',
            'Response option 129',
            'Response option 130',
            'Response option 131'
        ];
        $firstUserResponses = fgetcsv($csvFile);
        $this->assertEquals($expectedResponses, $firstUserResponses);

        $secondUserResponses = fgetcsv($csvFile);
        $this->assertEquals($expectedResponses, $secondUserResponses);

        fclose($csvFile);
        unlink(self::CSV_FILE_PATH);
    }

    public function testWritesContextReportWithExternalAuthorsData()
    {
        $testUsersData = [
            ['userId' => 234, 'externalId' => null],
            ['userId' => 235, 'externalId' => null],
            ['userId' => null, 'externalId' => 'john.doe@email.com'],
            ['userId' => null, 'externalId' => 'jane.doe@email.com']
        ];
        foreach ($testUsersData as $userData) {
            $this->addUserTestResponsesToReport($userData);
        }

        $this->contextReport->writeReport(self::CSV_FILE_PATH);
        $this->assertFileExists(self::CSV_FILE_PATH);

        $csvFile = fopen(self::CSV_FILE_PATH, 'r');
        $UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fread($csvFile, strlen($UTF8_BOM));

        $blocksHeader = fgetcsv($csvFile);
        $questionsHeader = fgetcsv($csvFile);

        $expectedResponses = [
            'Response option 123',
            'Response option 124',
            'Response option 125',
            'Response option 126',
            'Response option 127',
            'Response option 128',
            'Response option 129',
            'Response option 130',
            'Response option 131'
        ];
        $firstUserResponses = fgetcsv($csvFile);
        $this->assertEquals($expectedResponses, $firstUserResponses);

        $secondUserResponses = fgetcsv($csvFile);
        $this->assertEquals($expectedResponses, $secondUserResponses);

        $firstExternalAuthorResponses = fgetcsv($csvFile);
        $this->assertEquals($expectedResponses, $firstExternalAuthorResponses);

        $secondExternalAuthorResponses = fgetcsv($csvFile);
        $this->assertEquals($expectedResponses, $secondExternalAuthorResponses);

        fclose($csvFile);
        unlink(self::CSV_FILE_PATH);
    }
}
