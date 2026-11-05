<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . '/helpers/EmailAssociationTestCase.php';

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\tests\helpers\EmailAssociationTestCase;
use Illuminate\Support\Facades\DB;

class OrcidResponseAssociationTest extends EmailAssociationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user->setOrcid('https://orcid.org/0000-0002-1825-0097');
        DB::table('deia_responses')->where('deia_response_id', $this->responseId)->update([
            'external_id' => $this->user->getOrcid(), 'external_type' => 'orcid',
        ]);
        $request = $this->createStub(\APP\core\Request::class);
        $request->method('getContext')->willReturn($this->context);
        \PKP\core\Registry::set('request', $request);
    }

    public function testPublicOrcidWithoutOAuthCannotClaimResponses(): void
    {
        (new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'orcid');
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testManualProfileEditCannotReuseAnotherOrcidsAccessToken(): void
    {
        $this->user->setData('orcidAccessToken', 'token-for-a-different-orcid');
        (new DeiaSurveyPlugin())->checkMigrateResponsesOrcid('User::edit', [$this->user, $this->user, []]);
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testNativeOAuthCallbackCanAssociateMatchingResponses(): void
    {
        $this->user->setData('orcidAccessToken', 'token-returned-by-orcid');
        $props = ['orcidAccessDenied', 'orcidAccessToken', 'orcidAccessScope', 'orcidRefreshToken', 'orcidAccessExpiresOn'];
        (new DeiaSurveyPlugin())->checkMigrateResponsesOrcid('User::edit', [$this->user, $this->user, $props]);
        self::assertSame($this->user->getId(), (int) DB::table('deia_responses')
            ->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testNativeFieldListReachesThePluginThroughTheRealUserRepository(): void
    {
        \PKP\plugins\Hook::add('User::edit', [new DeiaSurveyPlugin(), 'checkMigrateResponsesOrcid']);
        $this->user->setData('orcidAccessToken', 'native-oauth-token');
        \APP\facades\Repo::user()->edit($this->user);
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));

        \APP\facades\Repo::user()->edit($this->user, [
            'orcidAccessToken', 'orcidRefreshToken', 'orcidAccessExpiresOn',
        ]);
        self::assertSame($this->user->getId(), (int) DB::table('deia_responses')
            ->where('deia_response_id', $this->responseId)->value('user_id'));
    }
}
