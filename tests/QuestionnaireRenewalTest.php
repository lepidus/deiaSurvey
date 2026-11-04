<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . '/helpers/QuestionnaireSecurityTestCase.php';
import('classes.handler.Handler');
import('lib.pkp.classes.plugins.GenericPlugin');
require_once dirname(__DIR__) . '/DeiaSurveyPlugin.php';

use Application;
use Request;
use APP\plugins\generic\deiaSurvey\classes\QuestionnaireTokenService;
use DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler;
use APP\plugins\generic\deiaSurvey\tests\helpers\QuestionnaireSecurityTestCase;
use TemplateManager;
use Illuminate\Database\Capsule\Manager as DB;
use Registry;
use HookRegistry;
use PluginRegistry;

class QuestionnaireRenewalTest extends QuestionnaireSecurityTestCase
{
    private int $authorId;
    private Request $request;
    private array $input;
    private bool $post = true;
    private bool $csrf = true;
    private string $operation = 'requestNewLink';
    private array $assigned = [];
    private array $messages = [];
    private bool $failDelivery = false;

    protected function setUp(): void
    {
        parent::setUp();
        \HookRegistry::register('Mail::send', function ($hook, $params) {
            if ($this->failDelivery) {
                throw new \RuntimeException('Test delivery failure');
            }
            $this->messages[] = clone $params[0];
            return true;
        });
        $this->initializeRequestRouter();
        $router = Application::get()->getRequest()->getRouter();
        $dispatcher = Application::get()->getRequest()->getDispatcher();
        $context = Application::getContextDAO()->getById($this->createTestContext());
        $submissionId = DB::table('submissions')->insertGetId(['context_id' => $context->getId()]);
        $publicationId = DB::table('publications')->insertGetId(['submission_id' => $submissionId]);
        DB::table('submissions')->where('submission_id', $submissionId)
            ->update(['current_publication_id' => $publicationId]);
        $this->authorId = DB::table('authors')->insertGetId([
            'email' => 'renewal-author@example.org', 'publication_id' => $publicationId,
        ]);
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
        HookRegistry::register('Schema::get::author', [$plugin, 'editAuthorSchema']);
        $plugins = &PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
        \Services::get('author')->edit(\Services::get('author')->get($this->authorId), ['givenName' => ['en_US' => 'External']], \Application::get()->getRequest());
        $token = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        DB::table('author_settings')->where('author_id', $this->authorId)
            ->where('setting_name', 'deiaTokenExpiresAt')->update(['setting_value' => \Carbon\Carbon::now()->timestamp - 1]);
        $this->input = ['authorId' => $this->authorId, 'authorToken' => $token];
        $this->request = $this->getMockBuilder(Request::class)->onlyMethods([
            'getContext', 'getUserVar', 'getQueryArray', 'getDispatcher', 'getRequestedOp', 'isPost', 'checkCSRF',
        ])->getMock();
        $this->request->setRouter($router);
        $this->request->method('getDispatcher')->willReturn($dispatcher);
        $this->request->method('getContext')->willReturn($context);
        $this->request->method('getUserVar')->willReturnCallback(fn ($name) => $this->input[$name] ?? null);
        $this->request->method('getQueryArray')->willReturnCallback(fn () => $this->input);
        $this->request->method('getRequestedOp')->willReturnCallback(fn () => $this->operation);
        $this->request->method('isPost')->willReturnCallback(fn () => $this->post);
        $this->request->method('checkCSRF')->willReturnCallback(fn () => $this->csrf);
        Registry::set('request', $this->request);
        $template = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display', 'addStyleSheet'])->getMock();
        $template->method('assign')->willReturnCallback(function ($key, $value = null) {
            $this->assigned = array_merge($this->assigned, is_array($key) ? $key : [$key => $value]);
        });
        Registry::set('templateManager', $template);
    }

    public function testExpiredInvitationCanRequestOneReplacementSentOnlyToTheAuthorsEmail(): void
    {
        $this->input['email'] = 'attacker@example.org';
        $args = [];
        self::assertTrue((new QuestionnaireHandler())->authorize($this->request, $args, []));

        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        (new QuestionnaireHandler())->requestNewLink([], $this->request);

        self::assertCount(1, $this->messages);
        $mail = $this->messages[0];
        self::assertSame('renewal-author@example.org', $mail->getRecipients()[0]['email']);
        $data = $mail->params;
        parse_str(parse_url($data['questionnaireUrl'], PHP_URL_QUERY), $params);
        $author = \Services::get('author')->get($this->authorId);
        self::assertFalse((new QuestionnaireTokenService())->isValid($author, $this->input['authorToken']));
        self::assertTrue((new QuestionnaireTokenService())->isValid($author, $params['authorToken']));
    }

    public function testGetCannotRequestNewLink(): void
    {
        $this->post = false;
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        self::assertSame([], $this->messages);
    }

    public function testRequestWithoutCsrfCannotSendNewLink(): void
    {
        $this->csrf = false;
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        self::assertSame([], $this->messages);
    }

    public function testWrongSecretCannotRequestNewLink(): void
    {
        $this->input['authorToken'] = str_repeat('a', 64);
        $args = [];
        self::assertFalse((new QuestionnaireHandler())->authorize($this->request, $args, []));
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        self::assertSame([], $this->messages);
    }

    public function testUnexpiredInvitationCannotBeRotatedThroughRecovery(): void
    {
        $this->input['authorToken'] = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        self::assertSame([], $this->messages);
        self::assertTrue((new QuestionnaireTokenService())->isValid(
            \Services::get('author')->get($this->authorId),
            $this->input['authorToken']
        ));
    }

    public function testExpiredSecretNeverAuthorizesResponseAccess(): void
    {
        foreach (['saveQuestionnaire', 'deleteData', 'orcidVerify'] as $operation) {
            $this->operation = $operation;
            $args = [];
            self::assertFalse((new QuestionnaireHandler())->authorize($this->request, $args, []));
        }
    }

    public function testLegacyInvitationAllowsEmailRecoveryOnly(): void
    {
        $this->input['authorToken'] = md5('legacy-invitation');
        \Services::get('author')->edit(\Services::get('author')->get($this->authorId), [
            'deiaToken' => $this->input['authorToken'], 'deiaTokenExpiresAt' => null,
            'deiaTokenEmail' => null, 'deiaOrcid' => 'https://orcid.org/0000-0002-1825-0097',
        ], \Application::get()->getRequest());
        self::assertFalse((new QuestionnaireTokenService())->isValid(
            \Services::get('author')->get($this->authorId),
            $this->input['authorToken']
        ));
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        self::assertCount(1, $this->messages);
        self::assertNull(\Services::get('author')->get($this->authorId)->getData('deiaOrcid'));
    }

    public function testMailFailurePreservesTheExpiredSecretForRetry(): void
    {
        $before = DB::table('author_settings')->where('author_id', $this->authorId)
            ->orderBy('setting_name')->get()->map(fn ($row) => (array) $row)->all();
        $this->failDelivery = true;

        (new QuestionnaireHandler())->requestNewLink([], $this->request);

        self::assertSame($before, DB::table('author_settings')->where('author_id', $this->authorId)
            ->orderBy('setting_name')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function testAnotherContextCannotRequestReplacement(): void
    {
        $this->request->getContext()->setId($this->createTestContext() + 100000);
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        self::assertSame([], $this->messages);
    }

    public function testExpiredPageShowsRecoveryWithoutLoadingResponses(): void
    {
        $this->operation = 'index';
        $this->post = false;
        $args = [];
        self::assertTrue((new QuestionnaireHandler())->authorize($this->request, $args, []));
        $template = TemplateManager::getManager($this->request);
        $template->expects(self::once())->method('display')
            ->with(self::stringContains('questionnairePage/expiredLink.tpl'));

        (new QuestionnaireHandler())->index([], $this->request);

        self::assertSame($this->authorId, $this->assigned['authorId']);
        self::assertSame($this->input['authorToken'], $this->assigned['authorToken']);
        self::assertArrayNotHasKey('responses', $this->assigned);
    }

    public function testExpiredOrcidLinkShowsRecoveryWithoutStartingOAuth(): void
    {
        $this->operation = 'orcidAuthorize';
        $args = [];
        self::assertTrue((new QuestionnaireHandler())->authorize($this->request, $args, []));
        $template = TemplateManager::getManager($this->request);
        $template->expects(self::once())->method('display')
            ->with(self::stringContains('questionnairePage/expiredLink.tpl'));

        (new QuestionnaireHandler())->orcidAuthorize([], $this->request);

        self::assertSame($this->authorId, $this->assigned['authorId']);
    }

    public function testRenewingAnAnsweredQuestionnairePreservesAllResponses(): void
    {
        $this->addSchemaFile('deiaQuestion');
        $questionId = $this->createDeiaQuestion();
        $responseId = DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $questionId,
            'external_id' => 'renewal-author@example.org', 'external_type' => 'email',
        ]);
        $before = (array) DB::table('deia_responses')->where('deia_response_id', $responseId)->first();

        (new QuestionnaireHandler())->requestNewLink([], $this->request);

        self::assertCount(1, $this->messages);
        self::assertSame($before, (array) DB::table('deia_responses')->where('deia_response_id', $responseId)->first());
    }
}
