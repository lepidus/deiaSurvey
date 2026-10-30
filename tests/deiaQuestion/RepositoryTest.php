<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestion;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class RepositoryTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $locale;
    private array $params;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextId = $this->createTestContext();
        $this->locale = 'en';
        $this->params = [
            'contextId' => $this->contextId,
            'questionText' => self::TEST_QUESTION_TEXT,
            'questionDescription' => self::TEST_QUESTION_DESCRIPTION,
            'questionType' => DeiaQuestion::TYPE_TEXTAREA,
            'questionBlockId' => null,
            'sequence' => null,
            'isTranslated' => false,
            'isDefaultQuestion' => true,
        ];
        $this->addSchemaFile('deiaQuestion');
    }

    public function testGetNewDeiaQuestionObject(): void
    {
        $repository = app(Repository::class);
        $deiaQuestion = $repository->newDataObject();
        self::assertInstanceOf(DeiaQuestion::class, $deiaQuestion);
        $deiaQuestion = $repository->newDataObject($this->params);
        self::assertEquals($this->params, $deiaQuestion->_data);
    }

    public function testCrud(): void
    {
        $repository = app(Repository::class);
        $deiaQuestion = $repository->newDataObject($this->params);
        $insertedDeiaQuestionId = $repository->add($deiaQuestion);
        $this->params['id'] = $insertedDeiaQuestionId;

        $fetchedDeiaQuestion = $repository->get($insertedDeiaQuestionId, $this->contextId);
        self::assertEquals($this->params, $fetchedDeiaQuestion->_data);

        $this->params['questionText'] = self::TEST_UPDATED_QUESTION_TEXT;
        $this->params['questionDescription'] = self::TEST_UPDATED_QUESTION_DESCRIPTION;
        $repository->edit($deiaQuestion, $this->params);

        $fetchedDeiaQuestion = $repository->get($deiaQuestion->getId(), $this->contextId);
        self::assertEquals($this->params, $fetchedDeiaQuestion->_data);

        $repository->delete($deiaQuestion);
        self::assertFalse($repository->exists($deiaQuestion->getId()));
    }

    public function testCollectorFilterByContextId(): void
    {
        $repository = app(Repository::class);
        $deiaQuestion = $repository->newDataObject($this->params);

        $repository->add($deiaQuestion);

        $deiaQuestions = $repository->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();
        self::assertTrue(in_array($deiaQuestion, $deiaQuestions->all()));
    }
}
