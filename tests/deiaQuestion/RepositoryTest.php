<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestion;

require_once(dirname(__DIR__, 2) . '/autoload.php');
require_once(dirname(__DIR__) . '/helpers/TestHelperTrait.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\Repository;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\Repository as DeiaQuestionBlockRepository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class RepositoryTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $questionBlockId;
    private $params;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_settings',
            'deia_questions',
            'deia_question_block_settings',
            'deia_question_blocks',
        ]);

        parent::setUp();

        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');

        $questionBlockRepository = app(DeiaQuestionBlockRepository::class);
        $questionBlock = $questionBlockRepository->newDataObject([
            'contextId' => $this->contextId,
            'title' => ['en' => 'Question block'],
            'description' => ['en' => 'Question block description'],
            'active' => 1,
            'sequence' => 1,
        ]);
        $this->questionBlockId = $questionBlockRepository->add($questionBlock);

        $this->params = [
            'contextId' => $this->contextId,
            'questionBlockId' => $this->questionBlockId,
            'sequence' => 1,
            'questionType' => DeiaQuestion::TYPE_TEXTAREA,
            'questionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title',
            'questionDescription' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.description',
            'isTranslated' => false,
            'isDefaultQuestion' => true,
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testCrudWithQuestionBlock(): void
    {
        $repository = app(Repository::class);
        $question = $repository->newDataObject($this->params);
        $insertedQuestionId = $repository->add($question);
        $this->params['id'] = $insertedQuestionId;

        $fetchedQuestion = $repository->get($insertedQuestionId, $this->contextId);
        self::assertInstanceOf(DeiaQuestion::class, $fetchedQuestion);
        self::assertEquals($this->params, $fetchedQuestion->_data);

        $this->params['sequence'] = 2;
        $repository->edit($question, $this->params);

        $fetchedQuestion = $repository->get($question->getId(), $this->contextId);
        self::assertEquals($this->params, $fetchedQuestion->_data);
    }

    public function testCollectorFiltersByQuestionBlockId(): void
    {
        $repository = app(Repository::class);
        $question = $repository->newDataObject($this->params);
        $repository->add($question);

        $questions = $repository->getCollector()
            ->filterByQuestionBlockIds([$this->questionBlockId])
            ->getMany()
            ->toArray();

        self::assertCount(1, $questions);
    }
}
