<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;

class OrcidResponseAssociationTest extends DatabaseTestCase
{
    use TestHelperTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $request = $this->createStub(Request::class);
        $request->method('getContext')->willReturn(
            Application::getContextDAO()->getById($this->createTestContext())
        );
        Registry::set('request', $request);
    }

    public function testUnverifiedPublicOrcidCannotClaimExternalResponses(): void
    {
        $user = Repo::user()->get($this->createTestUser());
        $user->setOrcid('https://orcid.org/0000-0002-1825-0097');
        $user->setOrcidVerified(false);
        $responseId = $this->createExternalResponse($user->getOrcid());

        (new DeiaDataService())->migrateResponsesByUserIdentifier(
            Application::getContextDAO()->getById($this->createTestContext()),
            $user,
            'orcid'
        );

        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
    }

    public function testUserEditHookDoesNotClaimResponsesForUnverifiedOrcid(): void
    {
        $user = Repo::user()->get($this->createTestUser());
        $user->setOrcid('https://orcid.org/0000-0002-1825-0097');
        $responseId = $this->createExternalResponse($user->getOrcid());

        (new DeiaSurveyPlugin())->checkMigrateResponsesOrcid('User::edit', [$user]);

        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
    }

    public function testVerifiedOrcidCanClaimMatchingExternalResponses(): void
    {
        $user = Repo::user()->get($this->createTestUser());
        $user->setOrcid('https://orcid.org/0000-0002-1825-0097');
        $user->setOrcidVerified(true);
        $responseId = $this->createExternalResponse($user->getOrcid());

        (new DeiaDataService())->migrateResponsesByUserIdentifier(
            Application::getContextDAO()->getById($this->createTestContext()),
            $user,
            'orcid'
        );

        $response = DB::table('deia_responses')->where('deia_response_id', $responseId)->first();
        self::assertSame($user->getId(), (int) $response->user_id);
        self::assertNull($response->external_id);
        self::assertNull($response->external_type);
    }

    private function createExternalResponse(string $orcid): int
    {
        $id = DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $this->createDeiaQuestion(),
            'external_id' => $orcid,
            'external_type' => 'orcid',
        ]);
        foreach (['responseValue' => ['en' => 'Sensitive answer'], 'optionsInputValue' => []] as $name => $value) {
            DB::table('deia_response_settings')->insert([
                'deia_response_id' => $id,
                'locale' => '',
                'setting_name' => $name,
                'setting_value' => Crypt::encryptString(serialize($value)),
            ]);
        }
        return $id;
    }
}
