<?php

namespace APP\plugins\generic\deiaSurvey\tests\demographicResponseOption;

use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DemographicResponseOption;
use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class RepositoryTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $demographicQuestionId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings',
            'demographic_response_options',
            'demographic_response_option_settings'
        ];
    }

    protected function setUp(): void
    {
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
