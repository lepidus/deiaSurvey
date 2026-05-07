<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaResponseOption;

require_once(dirname(__DIR__, 2) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\DeiaResponseOption;
use APP\plugins\generic\deiaSurvey\classes\deiaResponseOption\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class RepositoryTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $params;
    private $deiaQuestionId;

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
            'deia_response_options',
            'deia_response_option_settings'
        ]);

        parent::setUp();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponseOption');
        $this->deiaQuestionId = $this->createDeiaQuestion();
        $this->params = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'sequence' => 1,
            'optionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.text',
            'isTranslated' => false,
            'hasInputField' => true,
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
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

        $this->params['optionText'] = 'plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.updatedText';
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
