<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicQuestion;

use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

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
            'isTranslated' => false,
            'questionText' => 'plugin.generic.deiaSurvey.demographicQuestion.exampleQuestion.title',
            'questionDescription' => [$locale => 'Test description']
        ], $fetchedDemographicQuestion->_data);
    }

    public function testCreateDemographicQuestionWithTranslatedFields(): void
    {
        $locale = 'en';

        $demographicQuestion = $this->createDemographicQuestionObject($locale);
        $demographicQuestion->setIsTranslated(true);
        $demographicQuestion->setQuestionText('Translated question text', $locale);
        $insertedDemographicQuestionId = $this->demographicQuestionDAO->insert($demographicQuestion);

        $fetchedDemographicQuestion = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        self::assertEquals([
            'id' => $insertedDemographicQuestionId,
            'contextId' => $this->contextId,
            'questionType' => DemographicQuestion::TYPE_RADIO_BUTTONS,
            'isTranslated' => true,
            'questionText' => [$locale => 'Translated question text'],
            'questionDescription' => [$locale => 'Test description']
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

        $fetchedDemographicQuestion->setQuestionText('plugin.generic.deiaSurvey.demographicQuestion.exampleQuestion.updatedTitle');
        $this->demographicQuestionDAO->update($fetchedDemographicQuestion);

        $fetchedDemographicQuestionEdited = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        self::assertEquals(
            $fetchedDemographicQuestionEdited->getLocalizedQuestionText(),
            __('plugin.generic.deiaSurvey.demographicQuestion.exampleQuestion.updatedTitle')
        );
    }

    private function createDemographicQuestionObject($locale)
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        $demographicQuestion->setContextId($this->contextId);
        $demographicQuestion->setQuestionType(DemographicQuestion::TYPE_RADIO_BUTTONS);
        $demographicQuestion->setIsTranslated(false);
        $demographicQuestion->setQuestionText('plugin.generic.deiaSurvey.demographicQuestion.exampleQuestion.title');
        $demographicQuestion->setQuestionDescription('Test description', $locale);

        return $demographicQuestion;
    }
}
