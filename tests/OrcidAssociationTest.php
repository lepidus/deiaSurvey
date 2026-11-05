<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . '/helpers/EmailAssociationTestCase.php';
import('classes.handler.Handler');
import('classes.core.Request');
import('lib.pkp.classes.session.Session');
import('lib.pkp.classes.plugins.GenericPlugin');
require_once dirname(__DIR__) . '/DeiaSurveyPlugin.php';

use APP\plugins\generic\deiaSurvey\pages\deia\OrcidAssociationHandler;
use APP\plugins\generic\deiaSurvey\tests\helpers\EmailAssociationTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Capsule\Manager as DB;

class OrcidAssociationTest extends EmailAssociationTestCase
{
    private $request;
    private $handler;
    private $httpClient;
    private array $input = [];
    private bool $post = true;
    private bool $csrf = true;
    private array $originalSession;
    private const ORCID = 'https://sandbox.orcid.org/0000-0002-1825-0097';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSession = $_SESSION ?? [];
        $_SESSION = [];
        $this->user->setOrcid(self::ORCID);
        DB::table('user_settings')->insert([
            'user_id' => $this->user->getId(), 'locale' => '', 'setting_name' => 'orcid',
            'setting_value' => self::ORCID, 'setting_type' => 'string',
        ]);
        DB::table('deia_responses')->where('deia_response_id', $this->responseId)->update([
            'external_id' => self::ORCID, 'external_type' => 'orcid',
        ]);
        $settings = \DAORegistry::getDAO('PluginSettingsDAO');
        foreach (['orcidProfileAPIPath' => 'https://pub.sandbox.orcid.org/',
            'orcidClientId' => 'APP-TEST', 'orcidClientSecret' => 'test-secret'] as $name => $value) {
            $settings->updateSetting($this->context->getId(), 'orcidprofileplugin', $name, $value);
        }
        $dispatcher = \Application::get()->getDispatcher();
        $router = new \PageRouter();
        $router->setApplication(\Application::get());
        $router->setDispatcher($dispatcher);
        $session = new \Session();
        $this->request = $this->getMockBuilder(\Request::class)->onlyMethods([
            'getUser', 'getContext', 'getSession', 'getUserVar', 'isPost', 'checkCSRF', 'redirectUrl',
        ])->getMock();
        $this->request->setRouter($router);
        $this->request->setDispatcher($dispatcher);
        $this->request->method('getUser')->willReturn($this->user);
        $this->request->method('getContext')->willReturn($this->context);
        $this->request->method('getSession')->willReturn($session);
        $this->request->method('getUserVar')->willReturnCallback(function ($key) {
            return $this->input[$key] ?? null;
        });
        $this->request->method('isPost')->willReturnCallback(function () {
            return $this->post;
        });
        $this->request->method('checkCSRF')->willReturnCallback(function () {
            return $this->csrf;
        });
        \Registry::set('request', $this->request);
        $plugin = new \DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
        $plugins = &\PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
        $template = $this->getMockBuilder(\TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display'])->getMock();
        \Registry::set('templateManager', $template);
        $this->input = ['code' => 'oauth-code'];
        $this->handler = $this->getMockBuilder(OrcidAssociationHandler::class)
            ->onlyMethods(['createOrcidClient'])->getMock();
        $this->handler->method('createOrcidClient')->willReturnCallback(function ($contextId) {
            return new \APP\plugins\generic\deiaSurvey\classes\OrcidClient(null, $contextId, $this->httpClient);
        });
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            $_SESSION = $this->originalSession;
        }
    }

    public function testFreshOAuthAssociatesOnceAndPreservesEncryptedResponses(): void
    {
        $this->start();
        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('request')
            ->willReturn(new Response(200, [], json_encode(['orcid' => '0000-0002-1825-0097'])));
        $this->httpClient = $client;
        $this->handler->verify([], $this->request);
        $this->handler->verify([], $this->request);
        self::assertSame($this->user->getId(), (int) DB::table('deia_responses')
            ->where('deia_response_id', $this->responseId)->value('user_id'));
        self::assertSame($this->ciphertext, DB::table('deia_response_settings')->value('setting_value'));
    }

    public function testAnotherAuthenticatedOrcidCannotClaimThePublicIdentifier(): void
    {
        $this->start();
        $client = $this->createMock(Client::class);
        $client->method('request')->willReturn(new Response(200, [], json_encode(['orcid' => '0000-0001-2345-6789'])));
        $this->httpClient = $client;
        $this->handler->verify([], $this->request);
        self::assertNull(DB::table('deia_responses')->value('user_id'));
    }

    public function testWrongStateDoesNotContactOrcid(): void
    {
        $this->start();
        $this->input['state'] = 'wrong-state';
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('request');
        $this->httpClient = $client;
        $this->handler->verify([], $this->request);
        self::assertNull(DB::table('deia_responses')->value('user_id'));
    }

    public function testGetAndMissingCsrfDoNotStartOAuth(): void
    {
        $this->request->expects(self::never())->method('redirectUrl');
        $this->post = false;
        $this->handler->index([], $this->request);
        $this->post = true;
        $this->csrf = false;
        $this->handler->index([], $this->request);
    }

    public function testExpiredStateDoesNotContactOrcid(): void
    {
        $this->start();
        $previous = \Carbon\Carbon::getTestNow();
        try {
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::now()->addMinutes(10));
            $client = $this->createMock(Client::class);
            $client->expects(self::never())->method('request');
            $this->httpClient = $client;
            $this->handler->verify([], $this->request);
            self::assertNull(DB::table('deia_responses')->value('user_id'));
        } finally {
            \Carbon\Carbon::setTestNow($previous);
        }
    }

    public function testAnotherSessionCannotConfirmAssociation(): void
    {
        $this->start();
        $_SESSION = [];
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('request');
        $this->httpClient = $client;
        $this->handler->verify([], $this->request);
        self::assertNull(DB::table('deia_responses')->value('user_id'));
    }

    public function testOrcidChangedDuringOAuthDoesNotTransferResponses(): void
    {
        $this->start();
        $client = $this->createMock(Client::class);
        $client->method('request')->willReturnCallback(function () {
            DB::table('user_settings')->where('user_id', $this->user->getId())->where('setting_name', 'orcid')
                ->update(['setting_value' => 'https://sandbox.orcid.org/0000-0001-2345-6789']);
            return new Response(200, [], json_encode(['orcid' => '0000-0002-1825-0097']));
        });
        $this->httpClient = $client;
        $this->handler->verify([], $this->request);
        self::assertNull(DB::table('deia_responses')->value('user_id'));
    }

    public function testRefusedConsentIsNotOverwritten(): void
    {
        $this->start();
        (new \APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO())
            ->updateDeiaConsent($this->context->getId(), $this->user->getId(), false);
        $client = $this->createMock(Client::class);
        $client->method('request')->willReturn(new Response(200, [], json_encode(['orcid' => '0000-0002-1825-0097'])));
        $this->httpClient = $client;
        $this->handler->verify([], $this->request);
        self::assertNull(DB::table('deia_responses')->value('user_id'));
        self::assertFalse((new \APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO())
            ->getDeiaConsentOption($this->context->getId(), $this->user->getId()));
    }

    private function start(): void
    {
        $this->request->expects(self::once())->method('redirectUrl')->willReturnCallback(function ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            $this->input['state'] = $params['state'];
        });
        $this->handler->index([], $this->request);
    }
}
