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
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Registry;
use HookRegistry;
use PluginRegistry;

class QuestionnaireTokenTest extends QuestionnaireSecurityTestCase
{
    private int $authorId;
    private Request $request;
    private array $input;
    private bool $post = true;
    private bool $csrf = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeRequestRouter();
        $router = Application::get()->getRequest()->getRouter();
        $context = Application::getContextDAO()->getById($this->createTestContext());
        $submissionId = DB::table('submissions')->insertGetId(['context_id' => $context->getId()]);
        $publicationId = DB::table('publications')->insertGetId(['submission_id' => $submissionId]);
        $this->authorId = DB::table('authors')->insertGetId([
            'email' => 'token-test@example.org', 'publication_id' => $publicationId,
        ]);
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        HookRegistry::register('Schema::get::author', [$plugin, 'editAuthorSchema']);
        $plugins = &PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
        $this->request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getContext', 'getUserVar', 'getUserVars', 'getQueryArray', 'getRequestedOp',
                'redirectUrl', 'isPost', 'checkCSRF'])->getMock();
        $this->request->setRouter($router);
        $this->request->method('getContext')->willReturn($context);
        $this->request->method('getRequestedOp')->willReturn('saveQuestionnaire');
        $this->request->method('getUserVar')->willReturnCallback(fn ($name) => $this->input[$name] ?? null);
        $this->request->method('getQueryArray')->willReturnCallback(fn () => $this->input);
        $this->request->method('getUserVars')->willReturnCallback(fn () => $this->input);
        $this->request->method('isPost')->willReturnCallback(function () {
            return $this->post;
        });
        $this->request->method('checkCSRF')->willReturnCallback(function () {
            return $this->csrf;
        });
        Registry::set('request', $this->request);
        $this->input = ['authorId' => $this->authorId, 'authorToken' => md5('legacy-token')];
    }

    public function testLegacyQuestionnaireTokenIsRejectedByAuthorization(): void
    {
        \Services::get('author')->edit(\Services::get('author')->get($this->authorId), ['deiaToken' => $this->input['authorToken']], \Application::get()->getRequest());
        $args = [];
        self::assertFalse((new QuestionnaireHandler())->authorize($this->request, $args, []));
    }

    public function testIssuedTokenIsRandomAndOnlyItsHashIsPersisted(): void
    {
        $token = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        $author = \Services::get('author')->get($this->authorId);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $token);
        self::assertSame(hash('sha256', $token), $author->getData('deiaToken'));
        self::assertNotSame($token, $author->getData('deiaToken'));
        self::assertTrue((new QuestionnaireTokenService())->isValid($author, $token));
        $this->input['authorToken'] = $token;
        $args = [];
        self::assertTrue((new QuestionnaireHandler())->authorize($this->request, $args, []));
    }

    public function testReissuingTokenInvalidatesThePreviouslyDeliveredLink(): void
    {
        $service = new QuestionnaireTokenService();
        $oldToken = $service->create(\Services::get('author')->get($this->authorId));
        $newToken = $service->create(\Services::get('author')->get($this->authorId));
        $author = \Services::get('author')->get($this->authorId);
        self::assertNotSame($oldToken, $newToken);
        self::assertFalse($service->isValid($author, $oldToken));
        self::assertTrue($service->isValid($author, $newToken));
    }

    public function testIssuingTokenFromStaleAuthorPreservesTheCurrentOrcid(): void
    {
        (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        $staleAuthor = \Services::get('author')->get($this->authorId);
        \Services::get('author')->edit($staleAuthor, ['deiaOrcid' => 'https://orcid.org/0000-0002-1825-0097'], \Application::get()->getRequest());

        (new QuestionnaireTokenService())->create($staleAuthor);

        self::assertSame(
            'https://orcid.org/0000-0002-1825-0097',
            \Services::get('author')->get($this->authorId)->getData('deiaOrcid')
        );
    }

    public function testChangingAuthorEmailInvalidatesBothAccessAndRecovery(): void
    {
        $service = new QuestionnaireTokenService();
        $token = $service->create(\Services::get('author')->get($this->authorId));
        \Services::get('author')->edit(\Services::get('author')->get($this->authorId), ['email' => 'victim@example.org'], \Application::get()->getRequest());
        $author = \Services::get('author')->get($this->authorId);

        self::assertFalse($service->isValid($author, $token));
        self::assertFalse($service->canRenew($author, $token));
    }

    public function testTokenExpiresExactlyAfterSevenDays(): void
    {
        $originalNow = Carbon::getTestNow();
        $issuedAt = \Carbon\Carbon::now();
        $this->input['authorToken'] = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        try {
            Carbon::setTestNow($issuedAt->copy()->addDays(7)->subSecond());
            $args = [];
            self::assertTrue((new QuestionnaireHandler())->authorize($this->request, $args, []));
            Carbon::setTestNow($issuedAt->copy()->addDays(7));
            self::assertFalse((new QuestionnaireHandler())->authorize($this->request, $args, []));
        } finally {
            Carbon::setTestNow($originalNow);
        }
    }

    public function testPersistedHashCannotBeUsedAsBearerToken(): void
    {
        $service = new QuestionnaireTokenService();
        $token = $service->create(\Services::get('author')->get($this->authorId));
        self::assertFalse($service->isValid(\Services::get('author')->get($this->authorId), hash('sha256', $token)));
    }

    public function testTokenCannotBeUsedForAnotherAuthor(): void
    {
        $service = new QuestionnaireTokenService();
        $token = $service->create(\Services::get('author')->get($this->authorId));
        $otherId = DB::table('authors')->insertGetId([
            'email' => 'other-token@example.org',
            'publication_id' => \Services::get('author')->get($this->authorId)->getData('publicationId'),
        ]);
        $service->create(\Services::get('author')->get($otherId));
        self::assertFalse($service->isValid(\Services::get('author')->get($otherId), $token));
    }

    /** @dataProvider protectedOperations */
    public function testExpiredTokenCannotAccessQuestionnaireOperations(string $operation): void
    {
        $this->input['authorToken'] = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        DB::table('author_settings')->where('author_id', $this->authorId)
            ->where('setting_name', 'deiaTokenExpiresAt')->update(['setting_value' => \Carbon\Carbon::now()->timestamp]);
        $template = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display', 'addStyleSheet'])->getMock();
        $template->expects(self::once())->method('display')
            ->with(self::stringContains($operation === 'index'
                ? 'questionnairePage/expiredLink.tpl' : 'questionnairePage/displayMessage.tpl'));
        Registry::set('templateManager', $template);

        (new QuestionnaireHandler())->$operation([], $this->request);

        self::assertNull(\Services::get('author')->get($this->authorId)->getData('deiaOrcid'));
    }

    public static function protectedOperations(): array
    {
        return [['index'], ['saveQuestionnaire'], ['deleteData'], ['orcidVerify']];
    }

    public function testSaveSuccessKeepsBearerTokenInLinksInsteadOfStoredHash(): void
    {
        $this->addSchemaFile('deiaQuestionBlock');
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->input['authorToken'] = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        $assigned = [];
        $template = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display', 'addStyleSheet'])->getMock();
        $template->method('assign')->willReturnCallback(function ($key, $value = null) use (&$assigned) {
            $assigned = array_merge($assigned, is_array($key) ? $key : [$key => $value]);
        });
        $template->expects(self::once())->method('display')
            ->with(self::stringContains('questionnairePage/saveSuccess.tpl'));
        Registry::set('templateManager', $template);

        (new QuestionnaireHandler())->saveQuestionnaire([], $this->request);

        self::assertSame($this->input['authorToken'], $assigned['authorToken']);
        self::assertNotSame(\Services::get('author')->get($this->authorId)->getData('deiaToken'), $assigned['authorToken']);
    }

    public function testGetAndPostWithoutCsrfCannotSaveResponses(): void
    {
        $this->input['authorToken'] = (new QuestionnaireTokenService())->create(\Services::get('author')->get($this->authorId));
        $template = $this->getMockBuilder(TemplateManager::class)->disableOriginalConstructor()
            ->onlyMethods(['assign', 'display', 'addStyleSheet'])->getMock();
        $template->expects(self::exactly(2))->method('display')
            ->with(self::stringContains('questionnairePage/displayMessage.tpl'));
        Registry::set('templateManager', $template);
        $this->post = false;
        (new QuestionnaireHandler())->saveQuestionnaire([], $this->request);
        $this->post = true;
        $this->csrf = false;
        (new QuestionnaireHandler())->saveQuestionnaire([], $this->request);
        self::assertSame(0, DB::table('deia_responses')->count());
    }
}
