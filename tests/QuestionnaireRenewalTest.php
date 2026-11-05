<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\RequestCollectionContributorData;
use APP\plugins\generic\deiaSurvey\classes\QuestionnaireTokenService;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\pages\deia\QuestionnaireHandler;

require_once __DIR__ . '/helpers/QuestionnaireSecurityTestCase.php';
use APP\plugins\generic\deiaSurvey\tests\helpers\QuestionnaireSecurityTestCase;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\core\Registry;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use Symfony\Component\Mailer\Exception\TransportException;

class QuestionnaireRenewalTest extends QuestionnaireSecurityTestCase
{
    private int $authorId;
    private Request $request;
    private array $input;
    private bool $post = true;
    private bool $csrf = true;
    private string $operation = 'requestNewLink';
    private array $assigned = [];
    private $originalMailer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMailer = Mail::getFacadeRoot();
        Mail::fake();
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
        Hook::add('Schema::get::author', [$plugin, 'editAuthorSchema']);
        $plugins = &PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
        Repo::author()->edit(Repo::author()->get($this->authorId), ['givenName' => ['en' => 'External']]);
        $token = (new QuestionnaireTokenService())->create(Repo::author()->get($this->authorId));
        DB::table('author_settings')->where('author_id', $this->authorId)
            ->where('setting_name', 'deiaTokenExpiresAt')->update(['setting_value' => \Carbon\Carbon::now()->timestamp - 1]);
        DB::table('email_templates_default_data')->updateOrInsert([
            'email_key' => RequestCollectionContributorData::getEmailTemplateKey(), 'locale' => 'en',
        ], ['name' => 'Request DEIA data', 'subject' => 'Request DEIA data', 'body' => '{$questionnaireUrl}']);
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

    protected function tearDown(): void
    {
        Mail::swap($this->originalMailer);
        parent::tearDown();
    }

    public function testExpiredInvitationCanRequestOneReplacementSentOnlyToTheAuthorsEmail(): void
    {
        $this->input['email'] = 'attacker@example.org';
        $args = [];
        self::assertTrue((new QuestionnaireHandler())->authorize($this->request, $args, []));

        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        (new QuestionnaireHandler())->requestNewLink([], $this->request);

        Mail::assertSent(RequestCollectionContributorData::class, 1);
        $mail = Mail::sent(RequestCollectionContributorData::class)->first();
        self::assertSame('renewal-author@example.org', $mail->to[0]['address']);
        $data = $mail->viewData;
        parse_str(parse_url($data['questionnaireUrl'], PHP_URL_QUERY), $params);
        $author = Repo::author()->get($this->authorId);
        self::assertFalse((new QuestionnaireTokenService())->isValid($author, $this->input['authorToken']));
        self::assertTrue((new QuestionnaireTokenService())->isValid($author, $params['authorToken']));
    }

    public function testGetCannotRequestNewLink(): void
    {
        $this->post = false;
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        Mail::assertNothingSent();
    }

    public function testRequestWithoutCsrfCannotSendNewLink(): void
    {
        $this->csrf = false;
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        Mail::assertNothingSent();
    }

    public function testWrongSecretCannotRequestNewLink(): void
    {
        $this->input['authorToken'] = str_repeat('a', 64);
        $args = [];
        self::assertFalse((new QuestionnaireHandler())->authorize($this->request, $args, []));
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        Mail::assertNothingSent();
    }

    public function testUnexpiredInvitationCannotBeRotatedThroughRecovery(): void
    {
        $this->input['authorToken'] = (new QuestionnaireTokenService())->create(Repo::author()->get($this->authorId));
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        Mail::assertNothingSent();
        self::assertTrue((new QuestionnaireTokenService())->isValid(
            Repo::author()->get($this->authorId),
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
        Repo::author()->edit(Repo::author()->get($this->authorId), [
            'deiaToken' => $this->input['authorToken'], 'deiaTokenExpiresAt' => null,
            'deiaTokenEmail' => null, 'deiaOrcid' => 'https://orcid.org/0000-0002-1825-0097',
        ]);
        self::assertFalse((new QuestionnaireTokenService())->isValid(
            Repo::author()->get($this->authorId),
            $this->input['authorToken']
        ));
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        Mail::assertSent(RequestCollectionContributorData::class, 1);
        self::assertNull(Repo::author()->get($this->authorId)->getData('deiaOrcid'));
    }

    public function testMailFailurePreservesTheExpiredSecretForRetry(): void
    {
        $before = DB::table('author_settings')->where('author_id', $this->authorId)
            ->orderBy('setting_name')->get()->map(fn ($row) => (array) $row)->all();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Test delivery failure'));

        (new QuestionnaireHandler())->requestNewLink([], $this->request);

        self::assertSame($before, DB::table('author_settings')->where('author_id', $this->authorId)
            ->orderBy('setting_name')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function testAnotherContextCannotRequestReplacement(): void
    {
        $this->request->getContext()->setId($this->createTestContext() + 100000);
        (new QuestionnaireHandler())->requestNewLink([], $this->request);
        Mail::assertNothingSent();
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

        Mail::assertSent(RequestCollectionContributorData::class, 1);
        self::assertSame($before, (array) DB::table('deia_responses')->where('deia_response_id', $responseId)->first());
    }
}
