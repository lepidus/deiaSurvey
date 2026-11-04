<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponseOption;

use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DAO;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $deiaResponseOptionDAO;
    private $deiaQuestionId;
    private $contextId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deiaResponseOptionDAO = app(DAO::class);
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->contextId = $this->createTestContext();
        $this->deiaQuestionId = $this->createDeiaQuestion();
    }

    public function testNewDataObjectIsInstanceOfDeiaResponseOption(): void
    {
        $deiaResponseOption = $this->deiaResponseOptionDAO->newDataObject();
        self::assertInstanceOf(DeiaResponseOption::class, $deiaResponseOption);
    }

    public function testCreateDeiaResponseOption(): void
    {
        $deiaResponseOption = $this->createDeiaResponseOptionObject();
        $insertedObjectId = $this->deiaResponseOptionDAO->insert($deiaResponseOption);

        $fetchedDeiaResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );

        self::assertEquals([
            'id' => $insertedObjectId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'optionText' => self::TEST_OPTION_TEXT,
            'isTranslated' => false,
            'hasInputField' => true,
            'sequence' => null,
        ], $fetchedDeiaResponseOption->getAllData());
    }

    public function testCreateResponseOptionWithTranslatedText(): void
    {
        $locale = 'en';
        $deiaResponseOption = $this->createDeiaResponseOptionObject();
        $deiaResponseOption->setIsTranslated(true);
        $deiaResponseOption->setOptionText('Translated option text', $locale);
        $insertedObjectId = $this->deiaResponseOptionDAO->insert($deiaResponseOption);

        $fetchedDeiaResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );

        self::assertEquals([
            'id' => $insertedObjectId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'optionText' => [$locale => 'Translated option text'],
            'isTranslated' => true,
            'hasInputField' => true,
            'sequence' => null,
        ], $fetchedDeiaResponseOption->getAllData());
    }

    public function testEditDeiaResponseOption(): void
    {
        $deiaResponseOption = $this->createDeiaResponseOptionObject();
        $insertedObjectId = $this->deiaResponseOptionDAO->insert($deiaResponseOption);

        $fetchedDeiaResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );
        $fetchedDeiaResponseOption->setOptionText(
            self::TEST_UPDATED_OPTION_TEXT
        );

        $this->deiaResponseOptionDAO->update($fetchedDeiaResponseOption);

        $editedResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );

        self::assertEquals(
            $editedResponseOption->getData('optionText'),
            self::TEST_UPDATED_OPTION_TEXT
        );
    }

    public function testDeleteDeiaResponseOption(): void
    {
        $deiaResponseOption = $this->createDeiaResponseOptionObject();
        $insertedObjectId = $this->deiaResponseOptionDAO->insert($deiaResponseOption);

        $fetchedDeiaResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );

        $this->deiaResponseOptionDAO->delete($fetchedDeiaResponseOption);
        self::assertFalse($this->deiaResponseOptionDAO->exists($insertedObjectId, $this->contextId));
    }
}
