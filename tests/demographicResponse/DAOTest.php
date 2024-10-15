<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponse;

use APP\plugins\generic\demographicData\classes\demographicResponse\DemographicResponse;
use APP\plugins\generic\demographicData\classes\demographicResponse\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseDAO;
    private $demographicQuestionId;
    private $contextId;
    private $userId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings',
            'demographic_responses',
            'demographic_response_settings'
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
        $this->userId = $this->createUserMock();
    }

    public function testNewDataObjectIsInstanceOfDemographicResponse(): void
    {
        $demographicResponse = $this->demographicResponseDAO->newDataObject();
        self::assertInstanceOf(DemographicResponse::class, $demographicResponse);
    }

    public function testCreateDemographicResponse(): void
    {
        $demographicResponse = $this->createDemographicResponseObject();
        $insertedDemographicResponseId = $this->demographicResponseDAO->insert($demographicResponse);

        $fetchedDemographicResponse = $this->demographicResponseDAO->get(
            $insertedDemographicResponseId,
            $this->demographicQuestionId
        );

        self::assertEquals([
            'id' => $insertedDemographicResponseId,
            'demographicQuestionId' => $this->demographicQuestionId,
            'responseValue' => [self::DEFAULT_LOCALE => 'Test text'],
            'optionsInputValue' => [45 => 'Aditional information for response option'],
            'userId' => $this->userId,
            'externalId' => null,
            'externalType' => null
        ], $fetchedDemographicResponse->getAllData());
    }

    public function testCreateDemographicResponseForExternalAuthor(): void
    {
        $demographicResponse = $this->createDemographicResponseObject(true);
        $insertedDemographicResponseId = $this->demographicResponseDAO->insert($demographicResponse);

        $fetchedDemographicResponse = $this->demographicResponseDAO->get(
            $insertedDemographicResponseId,
            $this->demographicQuestionId
        );

        self::assertEquals([
            'id' => $insertedDemographicResponseId,
            'demographicQuestionId' => $this->demographicQuestionId,
            'responseValue' => [self::DEFAULT_LOCALE => 'Test text'],
            'optionsInputValue' => [45 => 'Aditional information for response option'],
            'userId' => null,
            'externalId' => 'external.author@lepidus.com.br',
            'externalType' => 'email'
        ], $fetchedDemographicResponse->getAllData());
    }

    public function testDeleteDemographicResponse(): void
    {
        $demographicResponse = $this->createDemographicResponseObject();
        $insertedDemographicResponseId = $this->demographicResponseDAO->insert($demographicResponse);

        $fetchedDemographicResponse = $this->demographicResponseDAO->get(
            $insertedDemographicResponseId,
            $this->demographicQuestionId
        );

        $this->demographicResponseDAO->delete($fetchedDemographicResponse);
        self::assertFalse($this->demographicResponseDAO->exists($insertedDemographicResponseId, $this->contextId));
    }

    public function testEditDemographicResponse(): void
    {
        $demographicResponse = $this->createDemographicResponseObject();
        $insertedDemographicResponseId = $this->demographicResponseDAO->insert($demographicResponse);

        $fetchedDemographicResponse = $this->demographicResponseDAO->get(
            $insertedDemographicResponseId,
            $this->demographicQuestionId
        );
        $fetchedDemographicResponse->setValue([self::DEFAULT_LOCALE => 'Updated text']);

        $this->demographicResponseDAO->update($fetchedDemographicResponse);

        $fetchedDemographicResponseEdited = $this->demographicResponseDAO->get(
            $insertedDemographicResponseId,
            $this->demographicQuestionId
        );

        self::assertEquals($fetchedDemographicResponseEdited->getValue(), [self::DEFAULT_LOCALE => 'Updated text']);
    }
}
