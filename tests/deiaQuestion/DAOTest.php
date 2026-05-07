<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DAO;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DAOTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $deiaQuestionDAO;

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
            'deia_question_settings',
            'deia_questions',
            'deia_response_settings',
            'deia_responses',
        ]);

        parent::setUp();
        $this->deiaQuestionDAO = app(DAO::class);
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestion');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    private function createDeiaQuestionObject()
    {
        $deiaQuestion = $this->deiaQuestionDAO->newDataObject();
        $deiaQuestion->setContextId($this->contextId);
        $deiaQuestion->setQuestionBlockId($this->createDeiaQuestionBlock($this->contextId));
        $deiaQuestion->setSequence(1);
        $deiaQuestion->setQuestionType(DeiaQuestion::TYPE_RADIO_BUTTONS);
        $deiaQuestion->setIsTranslated(false);
        $deiaQuestion->setIsDefaultQuestion(true);
        $deiaQuestion->setQuestionText('plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title');
        $deiaQuestion->setQuestionDescription('plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.description');

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
            'questionBlockId' => $deiaQuestion->getQuestionBlockId(),
            'sequence' => 1,
            'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
            'isTranslated' => false,
            'isDefaultQuestion' => true,
            'questionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title',
            'questionDescription' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.description'
        ], $fetchedDeiaQuestion->_data);
    }

    public function testCreateDeiaQuestionWithTranslatedFields(): void
    {
        $locale = 'en_US';
        $deiaQuestion = $this->createDeiaQuestionObject();
        $deiaQuestion->unsetData('questionText');
        $deiaQuestion->unsetData('questionDescription');
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
            'questionBlockId' => $deiaQuestion->getQuestionBlockId(),
            'sequence' => 1,
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
        $fetchedDeiaQuestion->setQuestionText('plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.updatedTitle');

        $this->deiaQuestionDAO->update($fetchedDeiaQuestion);

        $fetchedDeiaQuestionEdited = $this->deiaQuestionDAO->get(
            $insertedDeiaQuestionId,
            $this->contextId
        );

        self::assertEquals(
            $fetchedDeiaQuestionEdited->getLocalizedQuestionText(),
            __('plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.updatedTitle')
        );
    }
}
