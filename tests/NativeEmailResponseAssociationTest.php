<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use APP\template\TemplateManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\config\Config;
use PKP\core\Registry;
use PKP\invitation\invitations\registrationAccess\RegistrationAccessInvite;
use PKP\invitation\models\InvitationModel;
use PKP\observers\events\UserRegisteredContext;
use PKP\observers\listeners\ValidateRegisteredEmail;
use PKP\plugins\PluginRegistry;
use PKP\template\PKPTemplateResource;
use PKP\tests\PKPTestCase;
use PKP\user\User;

class NativeEmailResponseAssociationTest extends PKPTestCase
{
    use TestHelperTrait;

    private User $user;
    private Journal $context;
    private int $responseId;
    private RegistrationAccessInvite $invite;
    private $originalRequireValidation;
    private $originalMailer;

    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'request', 'plugins', 'hooks'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->originalMailer = Mail::getFacadeRoot();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaResponse');
        $this->addSchemaFile('deiaResponseOption');
        $this->originalRequireValidation = Config::getVar('email', 'require_validation');
        $config = &Config::getData();
        $config['email']['require_validation'] = true;

        $this->context = new Journal();
        $this->context->setId(1);
        $userId = DB::table('users')->insertGetId([
            'username' => 'deia_native_validation_test',
            'email' => 'native-validation@example.org',
            'password' => 'unused',
            'date_registered' => date('Y-m-d H:i:s'),
            'disabled' => 0,
        ]);
        $this->user = Repo::user()->get($userId, true);
        $questionId = $this->createDeiaQuestion();
        $this->responseId = DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $questionId,
            'external_id' => $this->user->getEmail(),
            'external_type' => 'email',
        ]);
        DB::table('deia_response_settings')->insert([
            'deia_response_id' => $this->responseId,
            'locale' => '',
            'setting_name' => 'responseValue',
            'setting_value' => Crypt::encryptString(serialize(['en' => 'Sensitive answer'])),
        ]);
        DB::table('deia_response_settings')->insert([
            'deia_response_id' => $this->responseId,
            'locale' => '',
            'setting_name' => 'optionsInputValue',
            'setting_value' => Crypt::encryptString(serialize([])),
        ]);

        $this->invite = new RegistrationAccessInvite();
        $this->invite->initialize($userId, $this->context->getId());
        $this->invite->invite();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Mail::swap($this->originalMailer);
        $config = &Config::getData();
        $config['email']['require_validation'] = $this->originalRequireValidation;
        parent::tearDown();
    }

    public function testNativeConfirmationAllowsTransferOnceAndPreservesEncryptedAnswers(): void
    {
        $before = DB::table('deia_response_settings')->where('deia_response_id', $this->responseId)
            ->pluck('setting_value', 'setting_name')->all();
        $this->invite->finalize();

        $service = new DeiaDataService();
        // The user object predates confirmation: the service must read current persisted state.
        self::assertTrue($service->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        $response = DB::table('deia_responses')->where('deia_response_id', $this->responseId)->first();
        self::assertSame($this->user->getId(), (int) $response->user_id);
        self::assertNull($response->external_id);
        self::assertNull($response->external_type);
        self::assertSame($before, DB::table('deia_response_settings')
            ->where('deia_response_id', $this->responseId)->pluck('setting_value', 'setting_name')->all());
        self::assertTrue((new DeiaDataDAO())->getDeiaConsentOption($this->context->getId(), $this->user->getId()));
        self::assertFalse($service->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testEnabledAccountWithoutValidatedEmailDoesNotTransfer(): void
    {
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        $this->assertResponseRemainsExternal();
    }

    public function testUnvalidatedAccountDoesNotTransferWhenValidationIsNotRequired(): void
    {
        $config = &Config::getData();
        $config['email']['require_validation'] = false;

        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier(
            $this->context,
            $this->user,
            'email'
        ));
        $this->assertResponseRemainsExternal();
    }

    public function testPreviouslyValidatedAccountDoesNotRequireValidationToRemainEnabled(): void
    {
        $this->invite->finalize();
        $config = &Config::getData();
        $config['email']['require_validation'] = false;

        self::assertTrue((new DeiaDataService())->migrateResponsesByUserIdentifier(
            $this->context,
            $this->user,
            'email'
        ));
    }

    public function testPreviouslyValidatedAccountDoesNotRequireInvitation(): void
    {
        $this->invite->finalize();
        InvitationModel::where('invitation_id', $this->invite->getId())->delete();

        self::assertTrue((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        self::assertSame($this->user->getId(), (int) DB::table('deia_responses')
            ->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testContextWithoutMatchingResponsesDoesNotTransfer(): void
    {
        $this->invite->finalize();
        $otherContext = new Journal();
        $otherContext->setId(2);
        $service = new DeiaDataService();
        self::assertFalse($service->migrateResponsesByUserIdentifier($otherContext, $this->user, 'email'));
        $this->assertResponseRemainsExternal();
        self::assertTrue($service->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testExistingAccountResponsesPreventTransfer(): void
    {
        $this->invite->finalize();
        $questionId = DB::table('deia_responses')->where('deia_response_id', $this->responseId)
            ->value('deia_question_id');
        DB::table('deia_responses')->insert([
            'deia_question_id' => $questionId,
            'user_id' => $this->user->getId(),
        ]);

        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        $this->assertResponseRemainsExternal();
    }

    public function testExplicitConsentRefusalPreventsTransfer(): void
    {
        $this->invite->finalize();
        (new DeiaDataDAO())->updateDeiaConsent($this->context->getId(), $this->user->getId(), false);

        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        $this->assertResponseRemainsExternal();
        self::assertFalse((new DeiaDataDAO())->getDeiaConsentOption($this->context->getId(), $this->user->getId()));
    }

    public function testTransferLeavesOtherContextsIdentifiersAndOwnersUntouched(): void
    {
        $questionId = DB::table('deia_responses')->where('deia_response_id', $this->responseId)
            ->value('deia_question_id');
        $otherContextId = DB::table('journals')->insertGetId([
            'path' => 'deia-native-other-context',
            'primary_locale' => 'en',
        ]);
        $otherQuestionId = DB::table('deia_questions')->insertGetId([
            'context_id' => $otherContextId,
            'question_type' => 1,
        ]);
        $otherResponseIds = [];
        foreach ([
            [$otherQuestionId, null, $this->user->getEmail(), 'email'],
            [$questionId, null, 'different@example.org', 'email'],
            [$questionId, null, $this->user->getEmail(), 'orcid'],
            [$questionId, 1, $this->user->getEmail(), 'email'],
        ] as [$question, $owner, $identifier, $type]) {
            $otherResponseIds[] = DB::table('deia_responses')->insertGetId([
                'deia_question_id' => $question,
                'user_id' => $owner,
                'external_id' => $identifier,
                'external_type' => $type,
            ]);
        }
        $before = DB::table('deia_responses')->whereIn('deia_response_id', $otherResponseIds)
            ->orderBy('deia_response_id')->get()->map(fn ($row) => (array) $row)->all();
        $this->invite->finalize();

        self::assertTrue((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        self::assertSame($before, DB::table('deia_responses')->whereIn('deia_response_id', $otherResponseIds)
            ->orderBy('deia_response_id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function testAccountDisabledAfterConfirmationDoesNotTransfer(): void
    {
        $this->invite->finalize();
        DB::table('users')->where('user_id', $this->user->getId())->update(['disabled' => 1]);

        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        $this->assertResponseRemainsExternal();
    }

    public function testNativeRegistrationValidationAllowsTransfer(): void
    {
        Mail::fake();
        $this->initializeRequestRouter();
        $context = Application::getContextDAO()->getById($this->context->getId());
        $context->setData('supportEmail', 'support@example.org');
        $context->setData('supportName', 'Support');
        InvitationModel::where('invitation_id', $this->invite->getId())->delete();

        $events = new Dispatcher(app());
        $events->subscribe(new ValidateRegisteredEmail());
        $events->dispatch(new UserRegisteredContext($this->user, $context));

        $invitation = InvitationModel::byUserId($this->user->getId())->firstOrFail();
        $this->assertResponseRemainsExternal();
        Mail::assertSent(\PKP\mail\mailables\ValidateEmailContext::class);
        (new RegistrationAccessInvite($invitation))->finalize();
        self::assertTrue((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testOpeningAuthenticatedDeiaTabTriggersAssociation(): void
    {
        $this->addSchemaFile('deiaQuestionBlock');
        $this->initializeRequestRouter();
        $originalRequest = Application::get()->getRequest();
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getUser', 'getContext'])->getMock();
        $request->method('getUser')->willReturn($this->user);
        $request->method('getContext')->willReturn(Application::getContextDAO()->getById($this->context->getId()));
        $request->setRouter($originalRequest->getRouter());
        Registry::set('request', $request);
        $plugins = &PluginRegistry::getPlugins('generic');
        $originalPlugin = $plugins['deiasurveyplugin'] ?? null;
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
        $plugins['deiasurveyplugin'] = $plugin;
        TemplateManager::getManager($request)->registerResource(
            $plugin->getTemplateResource(),
            new PKPTemplateResource($plugin->getTemplatePath())
        );
        $blockId = DB::table('deia_question_blocks')->insertGetId(['context_id' => 1, 'is_active' => 1]);
        $questionId = DB::table('deia_responses')->where('deia_response_id', $this->responseId)
            ->value('deia_question_id');
        DB::table('deia_questions')->where('deia_question_id', $questionId)->update([
            'deia_question_block_id' => $blockId,
        ]);
        $this->invite->finalize();

        try {
            $result = (new TabHandler())->deiaSurvey([], $request);
            self::assertTrue($result->getStatus());
            self::assertStringContainsString('Sensitive answer', $result->getContent());
            self::assertSame($this->user->getId(), (int) DB::table('deia_responses')
                ->where('deia_response_id', $this->responseId)->value('user_id'));
        } finally {
            if ($originalPlugin) {
                $plugins['deiasurveyplugin'] = $originalPlugin;
            } else {
                unset($plugins['deiasurveyplugin']);
            }
            Registry::set('request', $originalRequest);
        }
    }

    private function assertResponseRemainsExternal(): void
    {
        $response = DB::table('deia_responses')->where('deia_response_id', $this->responseId)->first();
        self::assertNull($response->user_id);
        self::assertSame($this->user->getEmail(), $response->external_id);
        self::assertSame('email', $response->external_type);
    }
}
