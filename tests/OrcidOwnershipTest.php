<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . '/helpers/EmailAssociationTestCase.php';

use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\tests\helpers\EmailAssociationTestCase;
use Illuminate\Database\Capsule\Manager as DB;

class OrcidOwnershipTest extends EmailAssociationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user->setOrcid('https://orcid.org/0000-0002-1825-0097');
        DB::table('deia_responses')->where('deia_response_id', $this->responseId)->update([
            'external_id' => $this->user->getOrcid(), 'external_type' => 'orcid',
        ]);
    }

    public function testPublicIdentifierCannotClaimResponses(): void
    {
        (new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'orcid');
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testOldNativeAccessTokenDoesNotAuthenticateTheCurrentIdentifier(): void
    {
        $this->user->setData('orcidAccessToken', 'token-for-another-orcid');
        $this->testPublicIdentifierCannotClaimResponses();
    }
}
