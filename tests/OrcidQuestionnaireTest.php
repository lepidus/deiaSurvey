<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\RequestCollectionContributorData;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\classes\QuestionnaireTokenService;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use APP\template\TemplateManager;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\core\Registry;
use PKP\orcid\OrcidManager;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class OrcidQuestionnaireTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private Request $request;
    private array $input = [];
    private int $authorId;
    private Store $session;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->initializeRequestRouter();
        $router = Application::get()->getRequest()->getRouter();
        $dispatcher = Application::get()->getRequest()->getDispatcher();
        $context = Application::getContextDAO()->getById($this->createTestContext());
        foreach ([
            OrcidManager::ENABLED => '1',
            OrcidManager::API_TYPE => OrcidManager::API_PUBLIC_SANDBOX,
            OrcidManager::CLIENT_ID => 'APP-TEST',
            OrcidManager::CLIENT_SECRET => 'test-secret',
        ] as $name => $value) {
            DB::table('journal_settings')->insert([
                'journal_id' => $context->getId(), 'locale' => '',
                'setting_name' => $name, 'setting_value' => $value,
            ]);
        }
        $submissionId = DB::table('submissions')->insertGetId(['context_id' => $context->getId()]);
        $publicationId = DB::table('publications')->insertGetId(['submission_id' => $submissionId]);
        $this->authorId = DB::table('authors')->insertGetId([
            'email' => 'external@example.org', 'publication_id' => $publicationId,
        ]);
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        Hook::add('Schema::get::author', [$plugin, 'editAuthorSchema']);
        $this->token = (new QuestionnaireTokenService())->create(Repo::author()->get($this->authorId));
        $plugins = &PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
        $template = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display', 'addStyleSheet'])->getMock();
        Registry::set('templateManager', $template);
        $this->session = new Store('deia-test', new ArraySessionHandler(120));
        $this->session->start();
        $this->request = $this->getMockBuilder(Request::class)->onlyMethods([
            'getContext', 'getUserVar', 'getQueryArray', 'getSession', 'redirect', 'redirectUrl', 'getDispatcher',
        ])->getMock();
        $this->request->setRouter($router);
        $this->request->method('getDispatcher')->willReturn($dispatcher);
        $this->request->method('getContext')->willReturn($context);
        $this->request->method('getSession')->willReturn($this->session);
        $this->request->method('getUserVar')->willReturnCallback(fn ($name) => $this->input[$name] ?? null);
        $this->request->method('getQueryArray')->willReturnCallback(fn () => $this->input);
        Registry::set('request', $this->request);
        $this->input = ['authorId' => $this->authorId, 'authorToken' => $this->token, 'code' => 'oauth-code'];
    }

    public function testCallbackRejectsWrongTokenBeforeContactingOrcid(): void
    {
        $this->input['authorToken'] = 'wrong-token';
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('request');
        Registry::set(self::MOCKED_GUZZLE_CLIENT_NAME, $client);

        (new QuestionnaireHandler())->orcidVerify([], $this->request);

        self::assertNull(Repo::author()->get($this->authorId)->getData('deiaOrcid'));
    }

    public function testAuthorizationRejectsWrongQuestionnaireToken(): void
    {
        $this->input['authorToken'] = 'wrong-token';
        $args = [];
        self::assertFalse((new QuestionnaireHandler())->authorize($this->request, $args, []));
    }

    public function testStartRejectsWrongTokenWithoutRedirecting(): void
    {
        $this->input['authorToken'] = 'wrong-token';
        $this->request->expects(self::never())->method('redirectUrl');
        self::assertFalse((new QuestionnaireHandler())->orcidAuthorize([], $this->request));
    }

    public function testCallbackWithoutStateDoesNotContactOrcid(): void
    {
        $this->assertCallbackDoesNotContactOrcid();
    }

    public function testCallbackWithWrongStateDoesNotContactOrcid(): void
    {
        $this->startOAuth();
        $this->input['state'] = 'wrong-state';
        $this->assertCallbackDoesNotContactOrcid();
    }

    public function testCallbackFromAnotherSessionDoesNotContactOrcid(): void
    {
        $this->startOAuth();
        $this->session->flush();
        $this->assertCallbackDoesNotContactOrcid();
    }

    public function testExpiredStateDoesNotContactOrcid(): void
    {
        $originalNow = Carbon::getTestNow();
        $this->startOAuth();
        try {
            Carbon::setTestNow(now()->addMinutes(10));
            $this->assertCallbackDoesNotContactOrcid();
        } finally {
            Carbon::setTestNow($originalNow);
        }
    }

    public function testStateCannotBeUsedForAnotherAuthor(): void
    {
        $this->startOAuth();
        $publicationId = Repo::author()->get($this->authorId)->getData('publicationId');
        $otherId = DB::table('authors')->insertGetId([
            'email' => 'other@example.org', 'publication_id' => $publicationId,
        ]);
        Repo::author()->edit(Repo::author()->get($otherId), [
            'deiaToken' => hash('sha256', $this->token),
            'deiaTokenExpiresAt' => now()->addDay()->timestamp,
            'deiaTokenEmail' => 'other@example.org',
        ]);
        $this->input['authorId'] = $otherId;
        $this->assertCallbackDoesNotContactOrcid();
        self::assertNull(Repo::author()->get($otherId)->getData('deiaOrcid'));
    }

    public function testStateCannotBeUsedWithAnotherTokenOrContext(): void
    {
        $this->startOAuth();
        $client = new OrcidClient(new DeiaSurveyPlugin(), $this->createTestContext());
        self::assertFalse($client->consumeOAuthState($this->input['state'], $this->authorId, 'another-token'));
        self::assertFalse($client->consumeOAuthState($this->input['state'], $this->authorId, $this->token));
        $this->startOAuthWithoutRedirect();
        $otherContextClient = new OrcidClient(new DeiaSurveyPlugin(), $this->createTestContext() + 100000);
        self::assertFalse($otherContextClient->consumeOAuthState(
            $this->input['state'],
            $this->authorId,
            $this->token
        ));
    }

    public function testValidCallbackLinksOrcidOnceAndUsesTheSameRedirectUri(): void
    {
        DB::table('deia_responses')->insert([
            'deia_question_id' => $this->createDeiaQuestion(),
            'external_id' => 'https://sandbox.orcid.org/0000-0002-1825-0097',
            'external_type' => 'orcid',
        ]);
        $url = $this->startOAuth();
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('request')
            ->with('POST', self::anything(), self::callback(
                fn ($options) => $options['form_params']['redirect_uri'] === $query['redirect_uri']
            ))->willReturn(new Response(200, [], json_encode(['orcid' => '0000-0002-1825-0097'])));
        Registry::set(self::MOCKED_GUZZLE_CLIENT_NAME, $client);
        $this->request->expects(self::once())->method('redirect');

        (new QuestionnaireHandler())->orcidVerify([], $this->request);
        (new QuestionnaireHandler())->orcidVerify([], $this->request);

        self::assertSame(
            'https://sandbox.orcid.org/0000-0002-1825-0097',
            Repo::author()->get($this->authorId)->getData('deiaOrcid')
        );
    }

    public function testCallbackDoesNotLinkOrcidAfterTheAuthorAnsweredByEmail(): void
    {
        DB::table('deia_responses')->insert([
            'deia_question_id' => $this->createDeiaQuestion(),
            'external_id' => 'external@example.org',
            'external_type' => 'email',
        ]);
        $this->request->expects(self::never())->method('redirect');

        $this->verifyOrcidReturnedByOAuth('0000-0002-1825-0097');

        self::assertNull(Repo::author()->get($this->authorId)->getData('deiaOrcid'));
    }

    public function testCallbackDoesNotReplaceTheLinkedOrcidAfterTheAuthorAnswered(): void
    {
        $linkedOrcid = 'https://sandbox.orcid.org/0000-0002-1825-0097';
        Repo::author()->edit(Repo::author()->get($this->authorId), ['deiaOrcid' => $linkedOrcid]);
        DB::table('deia_responses')->insert([
            'deia_question_id' => $this->createDeiaQuestion(),
            'external_id' => $linkedOrcid,
            'external_type' => 'orcid',
        ]);
        $this->request->expects(self::never())->method('redirect');

        $this->verifyOrcidReturnedByOAuth('0000-0001-5109-3700');

        self::assertSame($linkedOrcid, Repo::author()->get($this->authorId)->getData('deiaOrcid'));
    }

    public function testApiValidationRejectsWritingDeiaOrcidWithoutOAuth(): void
    {
        $plugin = new DeiaSurveyPlugin();
        Hook::add('Author::validate', [$plugin, 'preventDeiaTokenApiWrite']);
        $author = Repo::author()->get($this->authorId);
        $publication = Repo::publication()->get($author->getData('publicationId'));
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $submission->setData('locale', 'en');
        $this->request->getContext()->setData('supportedSubmissionMetadataLocales', ['en']);
        $errors = Repo::author()->validate(
            $author,
            ['deiaOrcid' => 'https://orcid.org/0000-0002-1825-0097'],
            $submission,
            $this->request->getContext()
        );
        self::assertArrayHasKey('deiaOrcid', $errors);
    }

    public function testTokenRotationDuringOAuthCannotRestoreTheRevokedToken(): void
    {
        $this->startOAuth();
        $newToken = '';
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('request')->willReturnCallback(function () use (&$newToken) {
            $newToken = (new QuestionnaireTokenService())->create(Repo::author()->get($this->authorId));
            return new Response(200, [], json_encode(['orcid' => '0000-0002-1825-0097']));
        });
        Registry::set(self::MOCKED_GUZZLE_CLIENT_NAME, $client);
        $this->request->expects(self::never())->method('redirect');

        (new QuestionnaireHandler())->orcidVerify([], $this->request);

        $author = Repo::author()->get($this->authorId);
        self::assertSame(hash('sha256', $newToken), $author->getData('deiaToken'));
        self::assertFalse((new QuestionnaireTokenService())->isValid($author, $this->token));
        self::assertNull($author->getData('deiaOrcid'));
    }

    public function testEmailDeliversMatchingTokensAndStartsOAuthInTheContributorSession(): void
    {
        $mailer = Mail::getFacadeRoot();
        Mail::fake();
        try {
            $author = Repo::author()->get($this->authorId);
            Repo::author()->edit($author, ['givenName' => ['en' => 'External'], 'familyName' => ['en' => 'Author']]);
            $publication = Repo::publication()->get($author->getData('publicationId'));
            $submissionId = $publication->getData('submissionId');
            DB::table('submissions')->where('submission_id', $submissionId)
                ->update(['current_publication_id' => $publication->getId()]);
            DB::table('email_templates_default_data')->updateOrInsert([
                'email_key' => RequestCollectionContributorData::getEmailTemplateKey(), 'locale' => 'en',
            ], ['name' => 'Request DEIA data', 'subject' => 'Request DEIA data',
                'body' => '{$questionnaireUrl} {$orcidQuestionnaireUrl}']);

            (new DataCollectionEmailSender())->sendRequestDataCollectionEmails($submissionId);

            Mail::assertSent(RequestCollectionContributorData::class, 1);
            $data = Mail::sent(RequestCollectionContributorData::class)->first()->getData();
            self::assertStringContainsString('/deiaQuestionnaire/orcidAuthorize', $data['orcidQuestionnaireUrl']);
            self::assertStringNotContainsString('state=', $data['orcidQuestionnaireUrl']);
            parse_str(parse_url($data['questionnaireUrl'], PHP_URL_QUERY), $emailParams);
            parse_str(parse_url($data['orcidQuestionnaireUrl'], PHP_URL_QUERY), $orcidParams);
            self::assertSame($emailParams, $orcidParams);
            self::assertTrue((new QuestionnaireTokenService())->isValid(
                Repo::author()->get($this->authorId),
                $emailParams['authorToken']
            ));
            self::assertFalse($this->session->has('deiaOrcidOAuth'));
        } finally {
            Mail::swap($mailer);
        }
    }

    public function testInvitationUsesTheCurrentEmailIfAuthorChangesAfterSelection(): void
    {
        $mailer = Mail::getFacadeRoot();
        Mail::fake();
        $author = Repo::author()->get($this->authorId);
        $publication = Repo::publication()->get($author->getData('publicationId'));
        $submissionId = $publication->getData('submissionId');
        DB::table('submissions')->where('submission_id', $submissionId)
            ->update(['current_publication_id' => $publication->getId()]);
        DB::table('email_templates_default_data')->updateOrInsert([
            'email_key' => RequestCollectionContributorData::getEmailTemplateKey(), 'locale' => 'en',
        ], ['name' => 'Request DEIA data', 'subject' => 'Request DEIA data', 'body' => '{$questionnaireUrl}']);
        $connection = DB::connection();
        $originalDispatcher = $connection->getEventDispatcher();
        $dispatcher = clone $originalDispatcher;
        $changed = false;
        $dispatcher->listen(QueryExecuted::class, function ($query) use (&$changed) {
            if (!$changed && str_contains($query->sql, 'from `authors`')) {
                $changed = true;
                DB::table('authors')->where('author_id', $this->authorId)->update(['email' => 'new-owner@example.org']);
            }
        });
        $connection->setEventDispatcher($dispatcher);
        try {
            (new DataCollectionEmailSender())->sendRequestDataCollectionEmails($submissionId);
            self::assertTrue($changed);
            Mail::assertSent(RequestCollectionContributorData::class, 1);
            $mail = Mail::sent(RequestCollectionContributorData::class)->first();
            self::assertSame('new-owner@example.org', $mail->to[0]['address']);
        } finally {
            $connection->setEventDispatcher($originalDispatcher);
            Mail::swap($mailer);
        }
    }

    private function verifyOrcidReturnedByOAuth(string $orcid): void
    {
        $this->startOAuth();
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('request')
            ->willReturn(new Response(200, [], json_encode(['orcid' => $orcid])));
        Registry::set(self::MOCKED_GUZZLE_CLIENT_NAME, $client);

        (new QuestionnaireHandler())->orcidVerify([], $this->request);
    }

    private function assertCallbackDoesNotContactOrcid(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('request');
        Registry::set(self::MOCKED_GUZZLE_CLIENT_NAME, $client);
        (new QuestionnaireHandler())->orcidVerify([], $this->request);
        self::assertNull(Repo::author()->get($this->authorId)->getData('deiaOrcid'));
    }

    private function startOAuth(): string
    {
        $url = '';
        $this->request->expects(self::once())->method('redirectUrl')
            ->willReturnCallback(function ($value) use (&$url) { $url = $value; });
        (new QuestionnaireHandler())->orcidAuthorize([], $this->request);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $query['state']);
        $this->input['state'] = $query['state'];
        return $url;
    }

    private function startOAuthWithoutRedirect(): void
    {
        $url = (new OrcidClient(new DeiaSurveyPlugin(), $this->createTestContext()))->buildOAuthUrl([
            'authorId' => $this->authorId, 'authorToken' => $this->token,
        ]);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->input['state'] = $query['state'];
    }
}
