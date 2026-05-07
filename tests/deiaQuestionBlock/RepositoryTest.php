<?php

namespace APP\plugins\generic\deiaSurvey\tests\deiaQuestionBlock;

require_once(dirname(__DIR__, 2) . '/autoload.php');
require_once(dirname(__DIR__) . '/helpers/TestHelperTrait.php');

use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DeiaQuestionBlock;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\Repository;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

import('lib.pkp.tests.DatabaseTestCase');

class RepositoryTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;
    private $params;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_block_settings',
            'deia_question_blocks',
        ]);

        parent::setUp();

        $this->contextId = $this->createJournalMock();
        $this->params = [
            'contextId' => $this->contextId,
            'title' => ['en' => 'Question block'],
            'description' => ['en' => 'Question block description'],
            'active' => 1,
            'sequence' => 1,
        ];
        $this->addSchemaFile('deiaQuestionBlock');
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testCrud(): void
    {
        $repository = app(Repository::class);
        $questionBlock = $repository->newDataObject($this->params);
        $insertedQuestionBlockId = $repository->add($questionBlock);
        $this->params['id'] = $insertedQuestionBlockId;

        $fetchedQuestionBlock = $repository->get($insertedQuestionBlockId, $this->contextId);
        self::assertInstanceOf(DeiaQuestionBlock::class, $fetchedQuestionBlock);
        self::assertEquals($this->params, $fetchedQuestionBlock->_data);

        $this->params['title'] = ['en' => 'Updated block'];
        $repository->edit($questionBlock, $this->params);

        $fetchedQuestionBlock = $repository->get($questionBlock->getId(), $this->contextId);
        self::assertEquals($this->params, $fetchedQuestionBlock->_data);

        $repository->delete($questionBlock);
        self::assertFalse($repository->exists($questionBlock->getId(), $this->contextId));
    }
}
