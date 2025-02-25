<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\demographicData\classes\demographicQuestion\DAO;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

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

        return $demographicQuestion;
    }
}
