<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestion;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $deiaQuestionDAO;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_questions',
            'deia_question_settings',
            'deia_responses',
            'deia_response_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->deiaQuestionDAO = app(DAO::class);
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestion');
    }

    private function createDeiaQuestionObject()
    {
        $deiaQuestion = $this->deiaQuestionDAO->newDataObject();
        $deiaQuestion->setContextId($this->contextId);
        $deiaQuestion->setQuestionType(DeiaQuestion::TYPE_RADIO_BUTTONS);
        $deiaQuestion->setIsTranslated(false);
        $deiaQuestion->setIsDefaultQuestion(true);
        $deiaQuestion->setQuestionText(self::TEST_QUESTION_TEXT);
        $deiaQuestion->setQuestionDescription(self::TEST_QUESTION_DESCRIPTION);

        return $deiaQuestion;
    }

    public function testNewDataObjectIsInstanceOfDeiaQuestion(): void
    {
        $deiaQuestion = $this->deiaQuestionDAO->newDataObject();
        self::assertInstanceOf(DeiaQuestion::class, $deiaQuestion);
    }

    public function testCreateDeiaQuestion(): void
    {
        $deiaQuestion = $this->createDeiaQuestionObject();
        $insertedDeiaQuestionId = $this->deiaQuestionDAO->insert($deiaQuestion);

        $fetchedDeiaQuestion = $this->deiaQuestionDAO->get(
            $insertedDeiaQuestionId,
            $this->contextId
        );

        self::assertEquals([
            'id' => $insertedDeiaQuestionId,
            'contextId' => $this->contextId,
            'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
            'isTranslated' => false,
            'isDefaultQuestion' => true,
            'questionText' => self::TEST_QUESTION_TEXT,
            'questionDescription' => self::TEST_QUESTION_DESCRIPTION
        ], $fetchedDeiaQuestion->_data);
    }

    public function testCreateDeiaQuestionWithTranslatedFields(): void
    {
        $locale = 'en';

        $deiaQuestion = $this->createDeiaQuestionObject();
        $deiaQuestion->setIsTranslated(true);
        $deiaQuestion->setQuestionText('Translated question text', $locale);
        $deiaQuestion->setQuestionDescription('Test description', $locale);
        $insertedDeiaQuestionId = $this->deiaQuestionDAO->insert($deiaQuestion);

        $fetchedDeiaQuestion = $this->deiaQuestionDAO->get(
            $insertedDeiaQuestionId,
            $this->contextId
        );

        self::assertEquals([
            'id' => $insertedDeiaQuestionId,
            'contextId' => $this->contextId,
            'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
            'isTranslated' => true,
            'isDefaultQuestion' => true,
            'questionText' => [$locale => 'Translated question text'],
            'questionDescription' => [$locale => 'Test description']
        ], $fetchedDeiaQuestion->_data);
    }

    public function testDeleteDeiaQuestion(): void
    {
        $deiaQuestion = $this->createDeiaQuestionObject();
        $insertedDeiaQuestionId = $this->deiaQuestionDAO->insert($deiaQuestion);

        $fetchedDeiaQuestion = $this->deiaQuestionDAO->get(
            $insertedDeiaQuestionId,
            $this->contextId
        );

        $this->deiaQuestionDAO->delete($fetchedDeiaQuestion);
        self::assertFalse($this->deiaQuestionDAO->exists($insertedDeiaQuestionId, $this->contextId));
    }

    public function testEditDeiaQuestion(): void
    {
        $deiaQuestion = $this->createDeiaQuestionObject();
        $insertedDeiaQuestionId = $this->deiaQuestionDAO->insert($deiaQuestion);

        $fetchedDeiaQuestion = $this->deiaQuestionDAO->get(
            $insertedDeiaQuestionId,
            $this->contextId
        );

        $this->initializeRequestRouter();
        $this->initializePluginLocaleData();
        $fetchedDeiaQuestion->setQuestionText(self::TEST_UPDATED_QUESTION_TEXT);
        $fetchedDeiaQuestion->setQuestionDescription(self::TEST_UPDATED_QUESTION_DESCRIPTION);
        $this->deiaQuestionDAO->update($fetchedDeiaQuestion);

        $fetchedDeiaQuestionEdited = $this->deiaQuestionDAO->get(
            $insertedDeiaQuestionId,
            $this->contextId
        );

        self::assertEquals(
            $fetchedDeiaQuestionEdited->getLocalizedQuestionText(),
            __(self::TEST_UPDATED_QUESTION_TEXT)
        );
    }
}
