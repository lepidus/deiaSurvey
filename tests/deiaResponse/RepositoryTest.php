<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponse;

use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DeiaResponse;
use APP\plugins\generic\deiaSurvey\classes\deiaResponse\Repository;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class RepositoryTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $deiaQuestionId;
    private $userId;

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
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->deiaQuestionId = $this->createDeiaQuestion();
        $this->userId = $this->createUserMock();
        $this->params = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'userId' => $this->userId,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text'
            ],
            'externalId' => null,
            'externalType' => null
        ];
    }

    public function testGetNewDeiaResponseObject(): void
    {
        $repository = app(Repository::class);
        $deiaResponse = $repository->newDataObject();
        self::assertInstanceOf(DeiaResponse::class, $deiaResponse);
        $deiaResponse = $repository->newDataObject($this->params);
        self::assertEquals($this->params, $deiaResponse->_data);
    }

    public function testCrud(): void
    {
        $repository = app(Repository::class);
        $deiaResponse = $repository->newDataObject($this->params);
        $insertedDeiaResponseId = $repository->add($deiaResponse);
        $this->params['id'] = $insertedDeiaResponseId;

        $fetchedDeiaResponse = $repository->get($insertedDeiaResponseId, $this->deiaQuestionId);
        self::assertEquals($this->params, $fetchedDeiaResponse->getAllData());

        $this->params['responseValue']['en'] = 'Updated text';
        $repository->edit($deiaResponse, $this->params);

        $fetchedDeiaResponse = $repository->get($deiaResponse->getId(), $this->deiaQuestionId);
        self::assertEquals($this->params, $fetchedDeiaResponse->getAllData());

        $repository->delete($deiaResponse);
        self::assertFalse($repository->exists($deiaResponse->getId()));
    }

    public function testCollectorFilterByQuestionAndUser(): void
    {
        $repository = app(Repository::class);
        $deiaResponse = $repository->newDataObject($this->params);

        $repository->add($deiaResponse);

        $deiaResponses = $repository->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->filterByUserIds([$this->userId])
            ->getMany();
        self::assertTrue(in_array($deiaResponse, $deiaResponses->all()));
    }

    public function testCollectorFilterByContext(): void
    {
        $contextId = 1;
        $repository = app(Repository::class);
        $newParams = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'userId' => null,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text 2'
            ],
            'externalId' => null,
            'externalType' => null
        ];

        $firstDeiaResponse = $repository->newDataObject($this->params);
        $secondDeiaResponse = $repository->newDataObject($newParams);

        $repository->add($firstDeiaResponse);
        $repository->add($secondDeiaResponse);

        $deiaResponses = $repository->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany()
            ->toArray();

        self::assertEquals(2, count($deiaResponses));
        self::assertTrue(in_array($firstDeiaResponse, $deiaResponses));
        self::assertTrue(in_array($secondDeiaResponse, $deiaResponses));
    }

    public function testCollectorFilterByExternalIdAndType(): void
    {
        $newParams = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'userId' => null,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text'
            ],
            'externalId' => 'external.author@lepidus.com.br',
            'externalType' => 'email'
        ];
        $repository = app(Repository::class);
        $deiaResponse = $repository->newDataObject($newParams);

        $repository->add($deiaResponse);

        $deiaResponses = $repository->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->filterByExternalIds([$newParams['externalId']])
            ->filterByExternalTypes([$newParams['externalType']])
            ->getMany();
        self::assertTrue(in_array($deiaResponse, $deiaResponses->all()));
    }
}
