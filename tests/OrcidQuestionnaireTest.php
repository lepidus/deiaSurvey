<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler;
require_once __DIR__ . '/helpers/QuestionnaireSecurityTestCase.php';
use APP\plugins\generic\deiaSurvey\tests\helpers\QuestionnaireSecurityTestCase;

use APP\template\TemplateManager;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

use PKP\session\Session;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;

use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class OrcidQuestionnaireTest extends QuestionnaireSecurityTestCase
{


    private Request $request;
    private array $input = [];
    private int $authorId;
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->initializeRequestRouter();
        $router = Application::get()->getRequest()->getRouter();
        $dispatcher = Application::get()->getRequest()->getDispatcher();
        $context = Application::getContextDAO()->getById($this->createTestContext());
        $settings = \PKP\db\DAORegistry::getDAO('PluginSettingsDAO');
        foreach (['orcidProfileAPIPath' => 'https://pub.sandbox.orcid.org/',
            'orcidClientId' => 'APP-TEST', 'orcidClientSecret' => 'test-secret'] as $name => $value) {
            $settings->updateSetting($context->getId(), 'orcidprofileplugin', $name, $value);
        }
        $submissionId = DB::table('submissions')->insertGetId(['context_id' => $context->getId()]);
        $publicationId = DB::table('publications')->insertGetId(['submission_id' => $submissionId]);
        $this->authorId = DB::table('authors')->insertGetId([
            'email' => 'external@example.org', 'publication_id' => $publicationId,
        ]);
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
        Hook::add('Schema::get::author', [$plugin, 'editAuthorSchema']);
        Repo::author()->edit(Repo::author()->get($this->authorId), ['deiaToken' => 'correct-token']);
        $plugins = &PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
        $template = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display', 'addStyleSheet'])->getMock();
        Registry::set('templateManager', $template);
        $this->session = new Session();

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
        $this->input = ['authorId' => $this->authorId, 'authorToken' => 'correct-token', 'code' => 'oauth-code'];
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
        $_SESSION = [];
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
        Repo::author()->edit(Repo::author()->get($otherId), ['deiaToken' => 'correct-token']);
        $this->input['authorId'] = $otherId;
        $this->assertCallbackDoesNotContactOrcid();
        self::assertNull(Repo::author()->get($otherId)->getData('deiaOrcid'));
    }

    public function testStateCannotBeUsedWithAnotherTokenOrContext(): void
    {
        $this->startOAuth();
        $client = new OrcidClient(new DeiaSurveyPlugin(), $this->createTestContext());
        self::assertFalse($client->consumeOAuthState($this->input['state'], $this->authorId, 'another-token'));
        self::assertFalse($client->consumeOAuthState($this->input['state'], $this->authorId, 'correct-token'));
        $this->startOAuthWithoutRedirect();
        $otherContextClient = new OrcidClient(new DeiaSurveyPlugin(), $this->createTestContext() + 100000);
        self::assertFalse($otherContextClient->consumeOAuthState(
            $this->input['state'],
            $this->authorId,
            'correct-token'
        ));
    }

    public function testValidCallbackLinksOrcidOnceAndUsesTheSameRedirectUri(): void
    {
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

    public function testApiValidationRejectsWritingDeiaOrcidWithoutOAuth(): void
    {
        $plugin = new DeiaSurveyPlugin();
        Hook::add('Author::validate', [$plugin, 'preventDeiaTokenApiWrite']);
        $author = Repo::author()->get($this->authorId);
        $publication = Repo::publication()->get($author->getData('publicationId'));
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $submission->setData('locale', 'en');
        $this->request->getContext()->setData('supportedSubmissionMetadataLocales', ['en']);
        $this->request->getContext()->setData('supportedSubmissionLocales', ['en']);
        $errors = Repo::author()->validate(
            $author,
            ['deiaOrcid' => 'https://orcid.org/0000-0002-1825-0097'],
            $submission,
            $this->request->getContext()
        );
        self::assertArrayHasKey('deiaOrcid', $errors);
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
            'authorId' => $this->authorId, 'authorToken' => 'correct-token',
        ]);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->input['state'] = $query['state'];
    }
}
