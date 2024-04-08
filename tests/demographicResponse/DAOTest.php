<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponse;

use APP\plugins\generic\demographicData\classes\demographicResponse\DemographicResponse;
use APP\plugins\generic\demographicData\classes\demographicResponse\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;
use APP\plugins\generic\demographicData\classes\demographicQuestion\Repository as DemographicQuestionRepository;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseDAO;
    private $demographicQuestionId;
    private const DEFAULT_LOCALE = "en";
    private $contextId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings',
            'demographic_question_responses'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->demographicResponseDAO = app(DAO::class);
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponse');
        $this->contextId = $this->createJournalMock();
        $this->demographicQuestionId = $this->createDemographicQuestion();
    }

    private function createDemographicQuestion()
    {
        $params = [
            'contextId' => $this->contextId,
            'questionText' => [
                self::DEFAULT_LOCALE => 'Test text'
            ],
            'questionDescription' => [
                self::DEFAULT_LOCALE => 'Test description'
            ]
        ];

        $repository = app(DemographicQuestionRepository::class);
        $demographicQuestion = $repository->newDataObject($params);
        return $repository->add($demographicQuestion);
    }

    public function testNewDataObjectIsInstanceOfDemographicResponse(): void
    {
        $demographicResponse = $this->demographicResponseDAO->newDataObject();
        self::assertInstanceOf(DemographicResponse::class, $demographicResponse);
    }

    // public function testCreateDemographicResponse(): void
    // {
    //     $demographicResponse = $this->createDemographicResponseObject();
    //     dump($demographicResponse);
    //     $insertedDemographicResponseId = $this->demographicResponseDAO->insert($demographicResponse);

    //     $fetchedDemographicResponse = $this->demographicResponseDAO->get(
    //         $insertedDemographicResponseId,
    //         $this->demographicQuestionId
    //     );

    //     self::assertEquals([
    //         'id' => $insertedDemographicResponseId,
    //         'demographicQuestionId' => $this->demographicQuestionId,
    //         'responseText' => [self::DEFAULT_LOCALE => 'Test text']
    //     ], $fetchedDemographicResponse->_data);
    // }

    // private function createDemographicResponseObject()
    // {
    //     $demographicResponse = $this->demographicResponseDAO->newDataObject();
    //     $demographicResponse->setDemographicQuestionId($this->demographicQuestionId);
    //     $demographicResponse->setText('Test text', self::DEFAULT_LOCALE);

    //     return $demographicResponse;
    // }
}
