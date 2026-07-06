<?php

namespace APP\plugins\generic\deiaSurvey\tests\dispatchers;

use APP\plugins\generic\deiaSurvey\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use APP\plugins\generic\deiaSurvey\tests\stubs\RegisteredFiltersTemplateManagerStub;
use APP\core\Request;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;
use PKP\tests\DatabaseTestCase;
use PKP\user\User;

class TemplateFilterDispatcherTest extends DatabaseTestCase
{
    use TestHelperTrait;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'deia_question_blocks',
            'deia_question_block_settings',
            'user_settings',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
        DB::table('deia_question_block_settings')->delete();
        DB::table('deia_question_blocks')->delete();
        $this->mockCurrentRequest();
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
        $context = $this->getMockBuilder(\APP\journal\Journal::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $context->method('getId')->willReturn($this->createJournalMock());

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $user->method('getId')->willReturn(1);

        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getContext', 'getUser'])
            ->getMock();
        $request->method('getContext')->willReturn($context);
        $request->method('getUser')->willReturn($user);

        Registry::set('request', $request);
    }
}
