<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $demographicQuestionDAO;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->demographicQuestionDAO = app(DAO::class);
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('demographicQuestion');
    }

    public function testNewDataObjectIsInstanceOfDemographicQuestion(): void
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);
    }

    public function testCreateDemographicQuestion(): void
    {
        $locale = 'en';

        $demographicQuestion = $this->createDemographicQuestionObject($locale);
        $insertedDemographicQuestionId = $this->demographicQuestionDAO->insert($demographicQuestion);

        $fetchedDemographicQuestion = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        self::assertEquals([
            'id' => $insertedDemographicQuestionId,
            'contextId' => $this->contextId,
            'questionText' => [$locale => 'Test title'],
            'questionDescription' => [$locale => 'Test description'],
        ], $fetchedDemographicQuestion->_data);
    }

    public function testDeleteDemographicQuestion(): void
    {
        $locale = 'en';

        $demographicQuestion = $this->createDemographicQuestionObject($locale);
        $insertedDemographicQuestionId = $this->demographicQuestionDAO->insert($demographicQuestion);

        $fetchedDemographicQuestion = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        $this->demographicQuestionDAO->delete($fetchedDemographicQuestion);
        self::assertFalse($this->demographicQuestionDAO->exists($insertedDemographicQuestionId, $this->contextId));
    }

    public function testEditDemographicQuestion(): void
    {
        $locale = 'en';

        $demographicQuestion = $this->createDemographicQuestionObject($locale);
        $insertedDemographicQuestionId = $this->demographicQuestionDAO->insert($demographicQuestion);

        $fetchedDemographicQuestion = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );
        $fetchedDemographicQuestion->setQuestionText('Updated title', $locale);

        $this->demographicQuestionDAO->update($fetchedDemographicQuestion);

        $fetchedDemographicQuestionEdited = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        self::assertEquals($fetchedDemographicQuestionEdited->getLocalizedQuestionText()[$locale], "Updated title");
    }

    private function createDemographicQuestionObject($locale)
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        $demographicQuestion->setContextId($this->contextId);
        $demographicQuestion->setQuestionText('Test title', $locale);
        $demographicQuestion->setQuestionDescription('Test description', $locale);

        return $demographicQuestion;
    }
}
