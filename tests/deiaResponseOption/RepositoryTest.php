<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponseOption;

use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class RepositoryTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $deiaQuestionId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_questions',
            'deia_question_settings',
            'deia_response_options',
            'deia_response_option_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->deiaQuestionId = $this->createDeiaQuestion();
        $this->params = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'optionText' => self::TEST_OPTION_TEXT,
            'isTranslated' => false,
            'hasInputField' => true,
        ];
    }

    public function testGetNewDeiaResponseOptionObject(): void
    {
        $repository = app(Repository::class);
        $responseOption = $repository->newDataObject();
        self::assertInstanceOf(DeiaResponseOption::class, $responseOption);
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

        $this->params['optionText'] = self::TEST_UPDATED_OPTION_TEXT;
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
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->getMany();

        self::assertTrue(in_array($responseOption, $responseOptions->all()));
    }
}
