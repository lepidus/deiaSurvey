<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DAO;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DAOTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseOptionDAO;
    private $demographicQuestionId;
    private $contextId;

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
            'demographic_response_options',
            'demographic_response_option_settings'
        ]);

        parent::setUp();
        $this->demographicResponseOptionDAO = app(DAO::class);
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponseOption');
        $this->contextId = $this->createJournalMock();
        $this->demographicQuestionId = $this->createDemographicQuestion();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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

    public function testEditDemographicResponseOption(): void
    {
        $demographicResponseOption = $this->createDemographicResponseOptionObject();
        $insertedObjectId = $this->demographicResponseOptionDAO->insert($demographicResponseOption);

        $fetchedDemographicResponseOption = $this->demographicResponseOptionDAO->get(
            $insertedObjectId,
            $this->demographicQuestionId
        );
        $fetchedDemographicResponseOption->setOptionText('Updated text', self::DEFAULT_LOCALE);

        $this->demographicResponseOptionDAO->update($fetchedDemographicResponseOption);

        $objectEdited = $this->demographicResponseOptionDAO->get(
            $insertedObjectId,
            $this->demographicQuestionId
        );

        self::assertEquals($objectEdited->getData('optionText'), [self::DEFAULT_LOCALE => 'Updated text']);
    }

    public function testDeleteDemographicResponseOption(): void
    {
        $demographicResponseOption = $this->createDemographicResponseOptionObject();
        $insertedObjectId = $this->demographicResponseOptionDAO->insert($demographicResponseOption);

        $fetchedDemographicResponseOption = $this->demographicResponseOptionDAO->get(
            $insertedObjectId,
            $this->demographicQuestionId
        );

        $this->demographicResponseOptionDAO->delete($fetchedDemographicResponseOption);
        self::assertFalse($this->demographicResponseOptionDAO->exists($insertedObjectId, $this->contextId));
    }
}
