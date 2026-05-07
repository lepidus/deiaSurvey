<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponse;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DAO;
use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DeiaResponse;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class DAOTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $deiaResponseDAO;
    private $deiaQuestionId;
    private $contextId;
    private $userId;

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
            'deia_responses',
            'deia_response_settings'
        ]);

        parent::setUp();
        $this->deiaResponseDAO = app(DAO::class);
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->contextId = $this->createJournalMock();
        $this->deiaQuestionId = $this->createDeiaQuestion();
        $this->userId = $this->createUserMock();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testNewDataObjectIsInstanceOfDeiaResponse(): void
    {
        $deiaResponse = $this->deiaResponseDAO->newDataObject();
        self::assertInstanceOf(DeiaResponse::class, $deiaResponse);
    }

    public function testCreateDeiaResponse(): void
    {
        $deiaResponse = $this->createDeiaResponseObject();
        $insertedDeiaResponseId = $this->deiaResponseDAO->insert($deiaResponse);

        $fetchedDeiaResponse = $this->deiaResponseDAO->get(
            $insertedDeiaResponseId,
            $this->deiaQuestionId
        );

        self::assertEquals([
            'id' => $insertedDeiaResponseId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'responseValue' => [self::DEFAULT_LOCALE => 'Test text'],
            'optionsInputValue' => [45 => 'Aditional information for response option'],
            'userId' => $this->userId
        ], $fetchedDeiaResponse->getAllData());
    }

    public function testCreateDeiaResponseForExternalAuthor(): void
    {
        $deiaResponse = $this->createDeiaResponseObject(true);
        $insertedDeiaResponseId = $this->deiaResponseDAO->insert($deiaResponse);

        $fetchedDeiaResponse = $this->deiaResponseDAO->get(
            $insertedDeiaResponseId,
            $this->deiaQuestionId
        );

        self::assertEquals([
            'id' => $insertedDeiaResponseId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'responseValue' => [self::DEFAULT_LOCALE => 'Test text'],
            'optionsInputValue' => [45 => 'Aditional information for response option'],
            'userId' => null,
            'externalId' => 'external.author@lepidus.com.br',
            'externalType' => 'email'
        ], $fetchedDeiaResponse->getAllData());
    }

    public function testDeleteDeiaResponse(): void
    {
        $deiaResponse = $this->createDeiaResponseObject();
        $insertedDeiaResponseId = $this->deiaResponseDAO->insert($deiaResponse);

        $fetchedDeiaResponse = $this->deiaResponseDAO->get(
            $insertedDeiaResponseId,
            $this->deiaQuestionId
        );

        $this->deiaResponseDAO->delete($fetchedDeiaResponse);
        self::assertFalse($this->deiaResponseDAO->exists($insertedDeiaResponseId, $this->contextId));
    }

    public function testEditDeiaResponse(): void
    {
        $deiaResponse = $this->createDeiaResponseObject();
        $insertedDeiaResponseId = $this->deiaResponseDAO->insert($deiaResponse);

        $fetchedDeiaResponse = $this->deiaResponseDAO->get(
            $insertedDeiaResponseId,
            $this->deiaQuestionId
        );
        $fetchedDeiaResponse->setValue([self::DEFAULT_LOCALE => 'Updated text']);

        $this->deiaResponseDAO->update($fetchedDeiaResponse);

        $fetchedDeiaResponseEdited = $this->deiaResponseDAO->get(
            $insertedDeiaResponseId,
            $this->deiaQuestionId
        );

        self::assertEquals($fetchedDeiaResponseEdited->getValue(), [self::DEFAULT_LOCALE => 'Updated text']);
    }
}
