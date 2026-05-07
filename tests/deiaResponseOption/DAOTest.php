<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DAO;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DAOTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $deiaResponseOptionDAO;
    private $deiaQuestionId;
    private $contextId;

    private const DEFAULT_LOCALE = "en_US";

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_block_settings',
            'deia_question_blocks',
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings'
        ]);

        parent::setUp();
        $this->deiaResponseOptionDAO = app(DAO::class);
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->contextId = $this->createJournalMock();
        $this->deiaQuestionId = $this->createDeiaQuestion();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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
            'sequence' => 1,
            'optionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.text',
            'isTranslated' => false,
            'hasInputField' => true,
        ], $fetchedDeiaResponseOption->getAllData());
    }

    public function testCreateResponseOptionWithTranslatedText(): void
    {
        $deiaResponseOption = $this->createDeiaResponseOptionObject();
        $deiaResponseOption->unsetData('optionText');
        $deiaResponseOption->setIsTranslated(true);
        $deiaResponseOption->setOptionText('Translated option text', self::DEFAULT_LOCALE);
        $insertedObjectId = $this->deiaResponseOptionDAO->insert($deiaResponseOption);

        $fetchedDeiaResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );

        self::assertEquals([
            'id' => $insertedObjectId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'sequence' => 1,
            'optionText' => [self::DEFAULT_LOCALE => 'Translated option text'],
            'isTranslated' => true,
            'hasInputField' => true,
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
        $fetchedDeiaResponseOption->setOptionText('plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.updatedText');

        $this->deiaResponseOptionDAO->update($fetchedDeiaResponseOption);

        $editedResponseOption = $this->deiaResponseOptionDAO->get(
            $insertedObjectId,
            $this->deiaQuestionId
        );

        self::assertEquals(
            $editedResponseOption->getData('optionText'),
            'plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.updatedText'
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
