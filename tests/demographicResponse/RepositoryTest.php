<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponse;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicResponse\DemographicResponse;
use APP\plugins\generic\deiaSurvey\classes\demographicResponse\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class RepositoryTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $demographicQuestionId;
    private $userId;

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
            'demographic_responses',
            'demographic_response_settings'
        ]);

        parent::setUp();
        $this->demographicQuestionId = $this->createDemographicQuestion();
        $this->userId = $this->createUserMock();
        $this->params = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'userId' => $this->userId,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text'
            ]
        ];
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponse');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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
        $userId = 1;
        $repository = app(Repository::class);
        $newParams = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'userId' => $userId,
            'responseValue' => [
                self::DEFAULT_LOCALE => 'Test text 2'
            ],
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
        $userId = 1;
        $newParams = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'userId' => $userId,
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
