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
            'demographic_question_settings',
            'demographic_responses',
            'demographic_response_settings'
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
            'questionType' => DemographicQuestion::TYPE_RADIO_BUTTONS,
            'questionText' => [$locale => 'Test text'],
            'questionDescription' => [$locale => 'Test description'],
            'possibleResponses' => [
                $locale => ['First possible response', 'Second possible response']
            ]
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
        $fetchedDemographicQuestion->setQuestionText('Updated text', $locale);

        $this->demographicQuestionDAO->update($fetchedDemographicQuestion);

        $fetchedDemographicQuestionEdited = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        self::assertEquals($fetchedDemographicQuestionEdited->getLocalizedQuestionText(), "Updated text");
    }

    private function createDemographicQuestionObject($locale)
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        $demographicQuestion->setContextId($this->contextId);
        $demographicQuestion->setQuestionType(DemographicQuestion::TYPE_RADIO_BUTTONS);
        $demographicQuestion->setQuestionText('Test text', $locale);
        $demographicQuestion->setQuestionDescription('Test description', $locale);
        $demographicQuestion->setPossibleResponses(['First possible response', 'Second possible response'], $locale);

        return $demographicQuestion;
    }
}
