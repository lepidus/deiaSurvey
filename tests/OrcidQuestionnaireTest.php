<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . '/helpers/QuestionnaireSecurityTestCase.php';
import('classes.handler.Handler');
import('lib.pkp.classes.plugins.GenericPlugin');
require_once dirname(__DIR__) . '/DeiaSurveyPlugin.php';

use Application;
use Request;

use APP\plugins\generic\deiaSurvey\classes\OrcidClient;
use DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler;
require_once __DIR__ . '/helpers/QuestionnaireSecurityTestCase.php';
use APP\plugins\generic\deiaSurvey\tests\helpers\QuestionnaireSecurityTestCase;

use TemplateManager;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

use Session;
use Illuminate\Database\Capsule\Manager as DB;
use Registry;

use HookRegistry;
use PluginRegistry;

class OrcidQuestionnaireTest extends QuestionnaireSecurityTestCase
{


    private Request $request;
    private $handler;
    private $httpClient;
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
        $settings = \DAORegistry::getDAO('PluginSettingsDAO');
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
        HookRegistry::register('Schema::get::author', [$plugin, 'editAuthorSchema']);
        \Services::get('author')->edit(\Services::get('author')->get($this->authorId), ['deiaToken' => 'correct-token'], \Application::get()->getRequest());
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
        $this->handler = $this->getMockBuilder(QuestionnaireHandler::class)
            ->onlyMethods(['createOrcidClient'])->getMock();
        $this->handler->method('createOrcidClient')->willReturnCallback(function ($plugin, $contextId) {
            return new OrcidClient($plugin, $contextId, $this->httpClient);
        });
        $this->input = ['authorId' => $this->authorId, 'authorToken' => 'correct-token', 'code' => 'oauth-code'];
    }

    public function testCallbackRejectsWrongTokenBeforeContactingOrcid(): void
    {
        $this->input['authorToken'] = 'wrong-token';
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('request');
        $this->httpClient = $client;

        $this->handler->orcidVerify([], $this->request);

        self::assertNull(\Services::get('author')->get($this->authorId)->getData('deiaOrcid'));
    }

    public function testAuthorizationRejectsWrongQuestionnaireToken(): void
    {
        $this->input['authorToken'] = 'wrong-token';
        $args = [];
        self::assertFalse($this->handler->authorize($this->request, $args, []));
    }

    public function testStartRejectsWrongTokenWithoutRedirecting(): void
    {
        $this->input['authorToken'] = 'wrong-token';
        $this->request->expects(self::never())->method('redirectUrl');
        self::assertFalse($this->handler->orcidAuthorize([], $this->request));
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
        $publicationId = \Services::get('author')->get($this->authorId)->getData('publicationId');
        $otherId = DB::table('authors')->insertGetId([
            'email' => 'other@example.org', 'publication_id' => $publicationId,
        ]);
        \Services::get('author')->edit(\Services::get('author')->get($otherId), ['deiaToken' => 'correct-token'], \Application::get()->getRequest());
        $this->input['authorId'] = $otherId;
        $this->assertCallbackDoesNotContactOrcid();
        self::assertNull(\Services::get('author')->get($otherId)->getData('deiaOrcid'));
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
        $this->httpClient = $client;
        $this->request->expects(self::once())->method('redirect');

        $this->handler->orcidVerify([], $this->request);
        $this->handler->orcidVerify([], $this->request);

        self::assertSame(
            'https://sandbox.orcid.org/0000-0002-1825-0097',
            \Services::get('author')->get($this->authorId)->getData('deiaOrcid')
        );
    }

    public function testApiValidationRejectsWritingDeiaOrcidWithoutOAuth(): void
    {
        $plugin = new DeiaSurveyPlugin();
        HookRegistry::register('Author::validate', [$plugin, 'preventDeiaTokenApiWrite']);
        $author = \Services::get('author')->get($this->authorId);
        $publication = \Services::get('publication')->get($author->getData('publicationId'));
        $submission = \Services::get('submission')->get($publication->getData('submissionId'));
        $submission->setData('locale', 'en_US');
        $this->request->getContext()->setData('supportedSubmissionMetadataLocales', ['en_US']);
        $this->request->getContext()->setData('supportedSubmissionLocales', ['en_US']);
        $errors = \Services::get('author')->validate(VALIDATE_ACTION_EDIT,
            ['deiaOrcid' => 'https://orcid.org/0000-0002-1825-0097'], ['en_US'], 'en_US');
        self::assertArrayHasKey('deiaOrcid', $errors);
    }

    private function assertCallbackDoesNotContactOrcid(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('request');
        $this->httpClient = $client;
        $this->handler->orcidVerify([], $this->request);
        self::assertNull(\Services::get('author')->get($this->authorId)->getData('deiaOrcid'));
    }

    private function startOAuth(): string
    {
        $url = '';
        $this->request->expects(self::once())->method('redirectUrl')
            ->willReturnCallback(function ($value) use (&$url) { $url = $value; });
        $this->handler->orcidAuthorize([], $this->request);
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
