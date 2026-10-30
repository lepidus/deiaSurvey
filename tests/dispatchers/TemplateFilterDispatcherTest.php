<?php

namespace APP\plugins\generic\deiaSurvey\tests\dispatchers;

use APP\core\Request;
use APP\plugins\generic\deiaSurvey\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use APP\plugins\generic\deiaSurvey\tests\stubs\RegisteredFiltersTemplateManagerStub;
use PKP\core\Registry;
use PKP\user\User;

class TemplateFilterDispatcherTest extends DatabaseTestCase
{
    use TestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
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
        $context->method('getId')->willReturn($this->createTestContext());

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $user->method('getId')->willReturn($this->createTestUser());

        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getContext', 'getUser'])
            ->getMock();
        $request->method('getContext')->willReturn($context);
        $request->method('getUser')->willReturn($user);

        Registry::set('request', $request);
    }
}
