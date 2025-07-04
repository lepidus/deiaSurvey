<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponse;

use APP\plugins\generic\deiaSurvey\classes\demographicResponse\DemographicResponse;
use APP\plugins\generic\deiaSurvey\classes\demographicResponse\Repository;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class RepositoryTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $demographicQuestionId;
    private $userId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings',
            'demographic_responses',
            'demographic_response_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponse');
        $this->demographicQuestionId = $this->createDemographicQuestion();
        $this->userId = $this->createUserMock();
        $this->params = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'userId' => $this->userId,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text'
            ],
            'externalId' => null,
            'externalType' => null
        ];
    }

    public function testGetNewDemographicResponseObject(): void
    {
        $repository = app(Repository::class);
        $demographicResponse = $repository->newDataObject();
        self::assertInstanceOf(DemographicResponse::class, $demographicResponse);
        $demographicResponse = $repository->newDataObject($this->params);
        self::assertEquals($this->params, $demographicResponse->_data);
    }

    public function testCrud(): void
    {
        $repository = app(Repository::class);
        $demographicResponse = $repository->newDataObject($this->params);
        $insertedDemographicResponseId = $repository->add($demographicResponse);
        $this->params['id'] = $insertedDemographicResponseId;

        $fetchedDemographicResponse = $repository->get($insertedDemographicResponseId, $this->demographicQuestionId);
        self::assertEquals($this->params, $fetchedDemographicResponse->getAllData());

        $this->params['responseValue']['en'] = 'Updated text';
        $repository->edit($demographicResponse, $this->params);

        $fetchedDemographicResponse = $repository->get($demographicResponse->getId(), $this->demographicQuestionId);
        self::assertEquals($this->params, $fetchedDemographicResponse->getAllData());

        $repository->delete($demographicResponse);
        self::assertFalse($repository->exists($demographicResponse->getId()));
    }

    public function testCollectorFilterByQuestionAndUser(): void
    {
        $repository = app(Repository::class);
        $demographicResponse = $repository->newDataObject($this->params);

        $repository->add($demographicResponse);

        $demographicResponses = $repository->getCollector()
            ->filterByQuestionIds([$this->demographicQuestionId])
            ->filterByUserIds([$this->userId])
            ->getMany();
        self::assertTrue(in_array($demographicResponse, $demographicResponses->all()));
    }

    public function testCollectorFilterByContext(): void
    {
        $contextId = 1;
        $repository = app(Repository::class);
        $newParams = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'userId' => null,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text 2'
            ],
            'externalId' => null,
            'externalType' => null
        ];

        $firstDemographicResponse = $repository->newDataObject($this->params);
        $secondDemographicResponse = $repository->newDataObject($newParams);

        $repository->add($firstDemographicResponse);
        $repository->add($secondDemographicResponse);

        $demographicResponses = $repository->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany()
            ->toArray();

        self::assertEquals(2, count($demographicResponses));
        self::assertTrue(in_array($firstDemographicResponse, $demographicResponses));
        self::assertTrue(in_array($secondDemographicResponse, $demographicResponses));
    }

    public function testCollectorFilterByExternalIdAndType(): void
    {
        $newParams = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'userId' => null,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text'
            ],
            'externalId' => 'external.author@lepidus.com.br',
            'externalType' => 'email'
        ];
        $repository = app(Repository::class);
        $demographicResponse = $repository->newDataObject($newParams);

        $repository->add($demographicResponse);

        $demographicResponses = $repository->getCollector()
            ->filterByQuestionIds([$this->demographicQuestionId])
            ->filterByExternalIds([$newParams['externalId']])
            ->filterByExternalTypes([$newParams['externalType']])
            ->getMany();
        self::assertTrue(in_array($demographicResponse, $demographicResponses->all()));
    }
}
