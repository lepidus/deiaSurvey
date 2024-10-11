<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponseOption;

use APP\plugins\generic\demographicData\classes\demographicResponseOption\DemographicResponseOption;
use APP\plugins\generic\demographicData\classes\demographicResponseOption\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseOptionDAO;
    private $demographicQuestionId;
    private $contextId;
    private $userId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings',
            'demographic_response_options',
            'demographic_response_option_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->demographicResponseOptionDAO = app(DAO::class);
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponseOption');
        $this->contextId = $this->createJournalMock();
        $this->demographicQuestionId = $this->createDemographicQuestion();
        $this->userId = $this->createUserMock();
    }

    public function testNewDataObjectIsInstanceOfDemographicResponseOption(): void
    {
        $demographicResponseOption = $this->demographicResponseOptionDAO->newDataObject();
        self::assertInstanceOf(DemographicResponseOption::class, $demographicResponseOption);
    }

    public function testCreateDemographicResponseOption(): void
    {
        $demographicResponseOption = $this->createDemographicResponseOptionObject();
        $insertedObjectId = $this->demographicResponseOptionDAO->insert($demographicResponseOption);

        $fetchedDemographicResponseOption = $this->demographicResponseOptionDAO->get(
            $insertedObjectId,
            $this->demographicQuestionId
        );

        self::assertEquals([
            'id' => $insertedObjectId,
            'demographicQuestionId' => $this->demographicQuestionId,
            'optionText' => [self::DEFAULT_LOCALE => 'First response option, with input field'],
            'hasInputField' => true,
        ], $fetchedDemographicResponseOption->getAllData());
    }
}
