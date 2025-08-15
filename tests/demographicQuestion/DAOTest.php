<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DAO;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DAOTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $demographicQuestionDAO;

    private const DEFAULT_LOCALE = "en_US";

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'demographic_question_settings',
            'demographic_questions',
            'demographic_response_settings',
            'demographic_responses',
        ]);

        parent::setUp();
        $this->demographicQuestionDAO = app(DAO::class);
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('demographicQuestion');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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
        $locale = 'en_US';
        $demographicQuestion = $this->createDemographicQuestionObject();
        $demographicQuestion->unsetData('questionText');
        $demographicQuestion->unsetData('questionDescription');
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
