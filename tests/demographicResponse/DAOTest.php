<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponse;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicResponse\DAO;
use APP\plugins\generic\deiaSurvey\classes\demographicResponse\DemographicResponse;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DAOTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseDAO;
    private $demographicQuestionId;
    private $contextId;
    private $userId;

    private const DEFAULT_LOCALE = "en_US";

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'demographic_questions',
            'demographic_question_settings',
            'demographic_responses',
            'demographic_response_settings'
        ]);

        parent::setUp();
        $this->demographicResponseDAO = app(DAO::class);
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponse');
        $this->contextId = $this->createJournalMock();
        $this->demographicQuestionId = $this->createDemographicQuestion();
        $this->userId = $this->createUserMock();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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
            'userId' => $this->userId
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
