<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class RepositoryTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $demographicQuestionId;

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
            'demographic_response_options',
            'demographic_response_option_settings'
        ]);

        parent::setUp();
        $this->addSchemaFile('demographicQuestion');
        $this->addSchemaFile('demographicResponseOption');
        $this->demographicQuestionId = $this->createDemographicQuestion();
        $this->params = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'optionText' => [self::DEFAULT_LOCALE => 'First response option, with input field'],
            'hasInputField' => true,
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testGetNewDemographicResponseOptionObject(): void
    {
        $repository = app(Repository::class);
        $responseOption = $repository->newDataObject();
        self::assertInstanceOf(DemographicResponseOption::class, $responseOption);
        $responseOption = $repository->newDataObject($this->params);
        self::assertEquals($this->params, $responseOption->_data);
    }

    public function testResponseOptionCrud(): void
    {
        $repository = app(Repository::class);
        $responseOption = $repository->newDataObject($this->params);
        $insertedResponseOptionId = $repository->add($responseOption);
        $this->params['id'] = $insertedResponseOptionId;

        $fetchedResponseOption = $repository->get($insertedResponseOptionId);
        self::assertEquals($this->params, $fetchedResponseOption->getAllData());

        $this->params['optionText']['en'] = 'Updated text';
        $repository->edit($responseOption, $this->params);

        $fetchedResponseOption = $repository->get($responseOption->getId());
        self::assertEquals($this->params, $fetchedResponseOption->getAllData());

        $repository->delete($responseOption);
        self::assertFalse($repository->exists($responseOption->getId()));
    }

    public function testCollectorFilterByQuestion(): void
    {
        $repository = app(Repository::class);
        $responseOption = $repository->newDataObject($this->params);

        $repository->add($responseOption);

        $responseOptions = $repository->getCollector()
            ->filterByQuestionIds([$this->demographicQuestionId])
            ->getMany();

        self::assertTrue(in_array($responseOption, $responseOptions->all()));
    }
}
