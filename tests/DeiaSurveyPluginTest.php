<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;

class DeiaSurveyPluginTest extends DatabaseTestCase
{
    use TestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addSchemaFile('deiaQuestionBlock');
    }

    public function testDoesNotRedirectUserWhenThereAreNoActiveQuestionBlocks(): void
    {
        $plugin = new DeiaSurveyPlugin();

        self::assertFalse($plugin->userShouldBeRedirected($this->createRequestStub()));
    }

    public function testDeiaTokenIsNeverSerializedOrAcceptedFromApiInput(): void
    {
        $schema = (object) ['properties' => (object) []];
        $plugin = new DeiaSurveyPlugin();

        $plugin->editAuthorSchema('Schema::get::author', [&$schema]);

        self::assertTrue($schema->properties->deiaToken->writeOnly);
        self::assertFalse(property_exists($schema->properties->deiaToken, 'readOnly'));
        self::assertFalse($schema->properties->deiaToken->apiSummary);

        $errors = [];
        $plugin->preventDeiaTokenApiWrite('Author::validate', [&$errors, null, ['deiaToken' => 'attacker']]);
        self::assertArrayHasKey('deiaToken', $errors);
    }

    private function createRequestStub(): object
    {
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getContext', 'getUser'])->getMock();
        $request->method('getContext')->willReturn(Application::getContextDAO()->getById($this->createTestContext()));
        $request->method('getUser')->willReturn(Repo::user()->get($this->createTestUser()));
        return $request;
    }
}
