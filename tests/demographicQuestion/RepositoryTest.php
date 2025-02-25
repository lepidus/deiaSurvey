<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\demographicQuestion\Repository;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class RepositoryTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $locale;
    private array $params;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'demographic_question_settings',
            'demographic_questions',
            'demographic_response_settings',
            'demographic_responses',
        ]);

        parent::setUp();
        $this->contextId = $this->createJournalMock();
        $this->locale = "en";
        $this->params = [
            'contextId' => $this->contextId,
            'questionType' => DemographicQuestion::TYPE_TEXTAREA,
            'questionText' => [
                $this->locale => 'Test text'
            ],
            'questionDescription' => [
                $this->locale => 'Test description'
            ]
        ];
        $this->addSchemaFile('demographicQuestion');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testGetNewDemographicQuestionObject(): void
    {
        $repository = app(Repository::class);
        $demographicQuestion = $repository->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);
        $demographicQuestion = $repository->newDataObject($this->params);
        self::assertEquals($this->params, $demographicQuestion->_data);
    }

    public function testCrud(): void
    {
        $repository = app(Repository::class);
        $demographicQuestion = $repository->newDataObject($this->params);
        $insertedDemographicQuestionId = $repository->add($demographicQuestion);
        $this->params['id'] = $insertedDemographicQuestionId;

        $fetchedDemographicQuestion = $repository->get($insertedDemographicQuestionId, $this->contextId);
        self::assertEquals($this->params, $fetchedDemographicQuestion->_data);

        $this->params['questionText'][$this->locale] = 'Updated text';
        $this->params['questionDescription'][$this->locale] = 'Updated description';
        $repository->edit($demographicQuestion, $this->params);

        $fetchedDemographicQuestion = $repository->get($demographicQuestion->getId(), $this->contextId);
        self::assertEquals($this->params, $fetchedDemographicQuestion->_data);

        $repository->delete($demographicQuestion);
        self::assertFalse($repository->exists($demographicQuestion->getId()));
    }

    public function testCollectorFilterByContextId(): void
    {
        $repository = app(Repository::class);
        $demographicQuestion = $repository->newDataObject($this->params);

        $repository->add($demographicQuestion);

        $demographicQuestions = $repository->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();
        self::assertTrue(in_array($demographicQuestion, $demographicQuestions->all()));
    }
}
