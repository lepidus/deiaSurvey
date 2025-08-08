<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseOptionDAO;
    private $demographicQuestionId;
    private $contextId;

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
            'optionText' => 'plugins.generic.deiaSurvey.demographicQuestion.exampleResponseOption.text',
            'isTranslated' => false,
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
        $fetchedDemographicResponseOption->setOptionText(
            'plugins.generic.deiaSurvey.demographicQuestion.exampleResponseOption.updatedText'
        );

        $this->demographicResponseOptionDAO->update($fetchedDemographicResponseOption);

        $editedResponseOption = $this->demographicResponseOptionDAO->get(
            $insertedObjectId,
            $this->demographicQuestionId
        );

        self::assertEquals(
            $editedResponseOption->getData('optionText'),
            'plugins.generic.deiaSurvey.demographicQuestion.exampleResponseOption.updatedText'
        );
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
