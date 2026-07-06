<?php

namespace APP\plugins\generic\deiaSurvey\tests\dispatchers;

require_once(dirname(__DIR__, 2) . '/autoload.php');
require_once(dirname(__DIR__) . '/helpers/TestHelperTrait.php');
require_once(dirname(__DIR__) . '/stubs/RegisteredFiltersTemplateManagerStub.php');

use APP\plugins\generic\deiaSurvey\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use APP\plugins\generic\deiaSurvey\tests\stubs\RegisteredFiltersTemplateManagerStub;

import('lib.pkp.tests.DatabaseTestCase');

class TemplateFilterDispatcherTest extends \DatabaseTestCase
{
    use TestHelperTrait;

    protected function getAffectedTables(): array
    {
        return $this->affectedTables;
    }

    protected function setUp(): void
    {
        $this->setAffectedTables([
            'deia_question_blocks',
            'deia_question_block_settings',
            'user_settings',
        ]);

        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
        $this->deleteQuestionBlocks();
        $this->mockCurrentRequest();
    }

    protected function tearDown(): void
    {
        $this->restoreTables($this->getAffectedTables());
        $this->setAffectedTables([]);

        parent::tearDown();
    }

    public function testDoesNotAddProfileTabWhenThereAreNoActiveQuestionBlocks(): void
    {
        $templateMgr = new RegisteredFiltersTemplateManagerStub();
        $dispatcher = new TemplateFilterDispatcher(new \stdClass());

        $dispatcher->addChangesToUserProfilePage($templateMgr);

        self::assertSame([], $templateMgr->registeredFilters);
    }

    private function mockCurrentRequest(): void
    {
        $context = new class () {
            public function getId(): int
            {
                return 1;
            }
        };

        $user = new class () {
            public function getId(): int
            {
                return 1;
            }
        };

        $request = new class ($context, $user) {
            private $context;
            private $user;

            public function __construct($context, $user)
            {
                $this->context = $context;
                $this->user = $user;
            }

            public function getContext()
            {
                return $this->context;
            }

            public function getUser()
            {
                return $this->user;
            }
        };

        \Registry::set('request', $request);
    }

    private function deleteQuestionBlocks(): void
    {
        $dao = new \DAO();
        $dao->update('DELETE FROM deia_question_block_settings', [], true, false);
        $dao->update('DELETE FROM deia_question_blocks', [], true, false);
    }
}
