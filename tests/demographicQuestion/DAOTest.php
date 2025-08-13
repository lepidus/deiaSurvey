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

    private function createDemographicQuestionObject()
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        $demographicQuestion->setContextId($this->contextId);
        $demographicQuestion->setQuestionType(DemographicQuestion::TYPE_RADIO_BUTTONS);
        $demographicQuestion->setIsTranslated(false);
        $demographicQuestion->setIsDefaultQuestion(true);
        $demographicQuestion->setQuestionText('plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.title');
        $demographicQuestion->setQuestionDescription('plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.description');

        return $demographicQuestion;
    }

    public function testNewDataObjectIsInstanceOfDemographicQuestion(): void
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);
    }

    public function testCreateDemographicQuestion(): void
    {
        $demographicQuestion = $this->createDemographicQuestionObject();
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
            'isDefaultQuestion' => true,
            'questionText' => 'plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.title',
            'questionDescription' => 'plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.description'
        ], $fetchedDemographicQuestion->_data);
    }

    public function testCreateDemographicQuestionWithTranslatedFields(): void
    {
        $locale = 'en';

        $demographicQuestion = $this->createDemographicQuestionObject();
        $demographicQuestion->setIsTranslated(true);
        $demographicQuestion->setQuestionText('Translated question text', $locale);
        $demographicQuestion->setQuestionDescription('Test description', $locale);
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
            'isDefaultQuestion' => true,
            'questionText' => [$locale => 'Translated question text'],
            'questionDescription' => [$locale => 'Test description']
        ], $fetchedDemographicQuestion->_data);
    }

    public function testDeleteDemographicQuestion(): void
    {
        $demographicQuestion = $this->createDemographicQuestionObject();
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
        $demographicQuestion = $this->createDemographicQuestionObject();
        $insertedDemographicQuestionId = $this->demographicQuestionDAO->insert($demographicQuestion);

        $fetchedDemographicQuestion = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        $fetchedDemographicQuestion->setQuestionText('plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.updatedTitle');
        $fetchedDemographicQuestion->setQuestionDescription('plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.updatedDescription');
        $this->demographicQuestionDAO->update($fetchedDemographicQuestion);

        $fetchedDemographicQuestionEdited = $this->demographicQuestionDAO->get(
            $insertedDemographicQuestionId,
            $this->contextId
        );

        self::assertEquals(
            $fetchedDemographicQuestionEdited->getLocalizedQuestionText(),
            __('plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.updatedTitle')
        );
    }
}
