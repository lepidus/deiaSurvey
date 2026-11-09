<?php

namespace APP\plugins\generic\deiaSurvey\tests;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler;
use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\EmailAssociationService;
use APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\ConfirmDeiaEmail;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\plugins\generic\deiaSurvey\pages\deia\EmailAssociationHandler;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use APP\template\TemplateManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\Registry;
use PKP\facades\Locale;
use PKP\invitation\models\InvitationModel;
use PKP\plugins\PluginRegistry;
use PKP\template\PKPTemplateResource;
use PKP\user\User;
use Symfony\Component\Mailer\Exception\TransportException;

class EmailValidationTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private Context $context;
    private User $user;
    private Request $request;
    private $originalMailer;
    private $originalNow;
    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMailer = Mail::getFacadeRoot();
        $this->originalNow = Carbon::getTestNow();
        $this->originalLocale = Locale::getLocale();
        Mail::fake();
        $this->addSchemaFile('deiaQuestionBlock');
        $this->initializeRequestRouter();
        $router = Application::get()->getRequest()->getRouter();
        $this->context = Application::getContextDAO()->getById($this->createTestContext());
        $this->user = Repo::user()->get($this->createTestUser(), true);
        // Mirror native registration, observed before the account email is validated.
        (new DeiaDataService())->recordRegistrationEmail($this->user);
        $this->request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getContext', 'getUser', 'isPost', 'checkCSRF'])->getMock();
        $this->request->method('getContext')->willReturn($this->context);
        $this->request->method('getUser')->willReturn($this->user);
        $this->request->setRouter($router);
        Registry::set('request', $this->request);

        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
        $plugins = &PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $plugin;
    }

    protected function tearDown(): void
    {
        Mail::swap($this->originalMailer);
        Carbon::setTestNow($this->originalNow);
        Locale::setLocale($this->originalLocale);
        parent::tearDown();
    }

    public function testAssociationLinkEscapesHtmlWithoutChangingItsToken(): void
    {
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        $this->getConfirmationLink();
        self::assertStringContainsString('&amp;token=', Mail::sent(ConfirmDeiaEmail::class)->first()->render());
    }

    public function testUnvalidatedAccountWithoutExternalResponsesDoesNotShowConfirmation(): void
    {
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testValidatedAccountDoesNotShowEmailConfirmation(): void
    {
        $this->createExternalResponse();
        $this->user->setDateValidated(date('Y-m-d H:i:s'));
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testResponsesFromAnotherContextDoNotShowConfirmation(): void
    {
        $contextId = (int) DB::table('journals')->insertGetId([
            'path' => 'deia-notification-' . bin2hex(random_bytes(4)),
            'primary_locale' => 'en',
        ]);
        $this->createExternalResponse(contextId: $contextId);
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testResponsesForAnotherEmailDoNotShowConfirmation(): void
    {
        $this->createExternalResponse(email: 'different@example.org');
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testOrcidResponsesDoNotShowEmailConfirmation(): void
    {
        $this->createExternalResponse(type: 'orcid');
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testResponsesAlreadyAssociatedWithAnAccountDoNotShowConfirmation(): void
    {
        $id = $this->createExternalResponse();
        DB::table('deia_responses')->where('deia_response_id', $id)->update(['user_id' => $this->user->getId()]);
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testProfileRendersSeparatePostFormWithCsrfForEmailConfirmation(): void
    {
        $this->createExternalResponse();
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        TemplateManager::getManager($this->request)->registerResource(
            $plugin->getTemplateResource(),
            new PKPTemplateResource($plugin->getTemplatePath())
        );
        $form = new QuestionsForm($this->request);
        $form->initData();
        $document = new \DOMDocument();
        $document->loadHTML($form->fetch($this->request));
        $xpath = new \DOMXPath($document);
        $confirmation = $xpath->query('//form[@id="deiaEmailValidationForm"]');

        self::assertSame(1, $confirmation->length);
        self::assertSame('post', $confirmation->item(0)->getAttribute('method'));
        self::assertStringContainsString('requestEmailValidation', $confirmation->item(0)->getAttribute('action'));
        self::assertSame(1, $xpath->query('//form[@id="deiaEmailValidationForm"]//input[@name="csrfToken"]')->length);
        self::assertSame(0, $xpath->query('//form[@id="deiaSurveyForm"]//form')->length);
        self::assertSame(1, $xpath->query(
            '//form[@id="deiaEmailValidationForm"]'
            . '[contains(concat(" ", normalize-space(@class), " "), " pkp_notification ")]'
        )->length);
        self::assertSame(1, $xpath->query(
            '//form[@id="deiaEmailValidationForm"]//span[@class="description"]'
            . '//button[@type="submit" and contains(concat(" ", normalize-space(@class), " "), " pkp_button_link ")]'
        )->length);
        self::assertSame(1, $xpath->query(
            '//form[@id="deiaEmailValidationForm"]/following::h2[@id="deiaSurveyTitle"]'
        )->length);
    }

    public function testGetRequestDoesNotSendEmail(): void
    {
        $this->request->method('isPost')->willReturn(false);

        self::assertFalse((new TabHandler())->requestEmailValidation([], $this->request)->getStatus());
        Mail::assertNothingSent();
    }

    public function testRequestWithoutCsrfDoesNotSendEmail(): void
    {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('checkCSRF')->willReturn(false);

        self::assertFalse((new TabHandler())->requestEmailValidation([], $this->request)->getStatus());
        Mail::assertNothingSent();
    }

    public function testAnonymousPostDoesNotSendEmail(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['isPost', 'checkCSRF', 'getUser'])->getMock();
        $request->method('isPost')->willReturn(true);
        $request->method('checkCSRF')->willReturn(true);
        $request->method('getUser')->willReturn(null);

        self::assertFalse((new TabHandler())->requestEmailValidation([], $request)->getStatus());
        Mail::assertNothingSent();
    }

    public function testPostWithoutContextDoesNotSendEmail(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['isPost', 'checkCSRF', 'getUser', 'getContext'])->getMock();
        $request->method('isPost')->willReturn(true);
        $request->method('checkCSRF')->willReturn(true);
        $request->method('getUser')->willReturn($this->user);
        $request->method('getContext')->willReturn(null);

        self::assertFalse((new TabHandler())->requestEmailValidation([], $request)->getStatus());
        Mail::assertNothingSent();
    }

    public function testAuthenticatedPostSendsPluginEmailWithoutEnablingGlobalValidation(): void
    {
        $config = &Config::getData();
        $config['email']['require_validation'] = false;
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('checkCSRF')->willReturn(true);

        $response = (new TabHandler())->requestEmailValidation([], $this->request);
        self::assertTrue($response->getStatus());
        self::assertStringContainsString('class="pkp_notification"', $response->getContent());
        Mail::assertSent(ConfirmDeiaEmail::class, 1);
        self::assertFalse(Config::getVar('email', 'require_validation'));
        self::assertNull(Repo::user()->get($this->user->getId(), true)->getDateValidated());
        [$id, $key] = $this->getConfirmationLink();
        self::assertSame($this->user->getId(), $id);
        $pending = $this->getPendingAssociation();
        self::assertSame($this->context->getId(), $pending['contextId']);
        self::assertSame($this->user->getEmail(), $pending['email']);
        self::assertSame(hash('sha256', $key), $pending['tokenHash']);
        self::assertStringNotContainsString($key, json_encode($pending));
        self::assertSame(0, InvitationModel::byUserId($this->user->getId())->count());
    }

    public function testConfirmationAssociatesResponsesWithoutChangingTheOjsAccount(): void
    {
        $this->addSchemaFile('deiaQuestion');
        $questionId = $this->createDeiaQuestion();
        $responseId = DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $questionId,
            'external_id' => $this->user->getEmail(),
            'external_type' => 'email',
        ]);
        $encrypted = Crypt::encryptString(serialize(['en' => 'Control answer']));
        DB::table('deia_response_settings')->insert([
            'deia_response_id' => $responseId,
            'locale' => '',
            'setting_name' => 'responseValue',
            'setting_value' => $encrypted,
        ]);
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        [$id, $key] = $this->getConfirmationLink();
        $service = new DeiaDataService();
        self::assertFalse($service->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));

        $accountBefore = DB::table('users')->where('user_id', $id)->first();
        self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $key));

        self::assertEquals($accountBefore, DB::table('users')->where('user_id', $id)->first());
        self::assertNull(Repo::user()->get($id, true)->getDateValidated());
        self::assertSame($this->user->getId(), (int) DB::table('deia_responses')
            ->where('deia_response_id', $responseId)->value('user_id'));
        self::assertSame($encrypted, DB::table('deia_response_settings')
            ->where('deia_response_id', $responseId)->value('setting_value'));
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $key));
    }

    public function testSenderUsesPersistedEmailInsteadOfSubmittedIdentity(): void
    {
        $email = $this->user->getEmail();
        $this->user->setEmail('different@example.org');

        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::assertSent(ConfirmDeiaEmail::class, fn ($mail) => $mail->hasTo($email)
            && !$mail->hasTo('different@example.org'));
    }

    public function testValidatedAccountDoesNotReceiveAnotherConfirmation(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update([
            'date_validated' => date('Y-m-d H:i:s'),
        ]);

        self::assertFalse((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::assertNothingSent();
    }

    public function testValidatedAccountWithChangedEmailMustConfirmAssociation(): void
    {
        $changedEmail = 'changed-' . $this->user->getId() . '@example.org';
        $validatedAt = date('Y-m-d H:i:s');
        DB::table('users')->where('user_id', $this->user->getId())->update([
            'email' => $changedEmail,
            'date_validated' => $validatedAt,
        ]);
        $this->user->setEmail($changedEmail);
        $this->user->setDateValidated($validatedAt);
        $this->createExternalResponse();
        $form = new QuestionsForm($this->request);
        $form->initData();

        self::assertTrue($form->getData('canValidateEmail'));
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::assertSent(ConfirmDeiaEmail::class, fn ($mail) => $mail->hasTo($changedEmail));
    }

    public function testDisabledAccountDoesNotReceiveConfirmation(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update(['disabled' => 1]);

        self::assertFalse((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::assertNothingSent();
    }

    public function testResendWaitsOneMinuteAndReplacesThePreviousLink(): void
    {
        Carbon::setTestNow('2026-10-30 12:00:00');
        $this->createExternalResponse();
        $sender = new DataCollectionEmailSender();
        self::assertTrue($sender->sendEmailValidation($this->context, $this->user));
        [$id, $key] = $this->getConfirmationLink();
        Carbon::setTestNow('2026-10-30 12:00:59');
        self::assertFalse($sender->sendEmailValidation($this->context, $this->user));
        Mail::assertSent(ConfirmDeiaEmail::class, 1);

        Carbon::setTestNow('2026-10-30 12:01:00');
        self::assertTrue($sender->sendEmailValidation($this->context, $this->user));
        Mail::assertSent(ConfirmDeiaEmail::class, 2);
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $key));
    }

    public function testExpiredOrIncorrectLinkCannotAssociateResponses(): void
    {
        $this->createExternalResponse();
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        [$id, $key] = $this->getConfirmationLink();
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, str_repeat('a', 64)));
        Carbon::setTestNow(now()->addDay());

        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $key));
        self::assertNull(Repo::user()->get($this->user->getId(), true)->getDateValidated());
    }

    public function testCustomContextTemplateIsUsed(): void
    {
        $template = Repo::emailTemplate()->newDataObject([
            'key' => ConfirmDeiaEmail::getEmailTemplateKey(),
            'contextId' => $this->context->getId(),
            'enabled' => true,
            'subject' => ['en' => 'Custom confirmation'],
            'body' => ['en' => '<a href="{$associationUrl}">Custom confirmation link</a>'],
        ]);
        Repo::emailTemplate()->add($template);

        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::assertSent(ConfirmDeiaEmail::class, fn ($mail) => $mail->subject === 'Custom confirmation'
            && str_contains($mail->render(), 'Custom confirmation link'));
        $this->getConfirmationLink();
    }

    public function testDefaultEmailUsesPortuguesePluginTranslations(): void
    {
        $this->installDefaultEmailForLocale('pt_BR');

        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::sent(ConfirmDeiaEmail::class)->first()->setData('pt_BR');
        Mail::assertSent(ConfirmDeiaEmail::class, fn ($mail) => $mail->subject === 'Confirme a associação das suas respostas DEIA'
            && str_contains($mail->render(), 'Prezado(a) Test User,')
            && str_contains($mail->render(), 'Esta é uma mensagem automática de')
            && str_contains($mail->render(), 'Atenciosamente,')
            && str_contains($mail->render(), 'Test contact')
            && str_contains($mail->render(), 'DEIA test journal')
            && !str_contains($mail->render(), '{$'));
        $this->getConfirmationLink();
    }

    public function testDefaultEmailUsesSpanishPluginTranslations(): void
    {
        $this->installDefaultEmailForLocale('es');

        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Mail::sent(ConfirmDeiaEmail::class)->first()->setData('es');
        Mail::assertSent(ConfirmDeiaEmail::class, fn ($mail) => $mail->subject === 'Confirme la asociación de sus respuestas DEIA'
            && str_contains($mail->render(), 'Atentamente,')
            && str_contains($mail->render(), 'Test contact'));
        $this->getConfirmationLink();
    }

    public function testMailFailureDoesNotLeaveAnUndeliveredToken(): void
    {
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Test transport failure'));
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('checkCSRF')->willReturn(true);

        self::assertFalse((new TabHandler())->requestEmailValidation([], $this->request)->getStatus());
        self::assertSame([], $this->getPendingAssociation());
        self::assertNull(Repo::user()->get($this->user->getId(), true)->getDateValidated());
    }

    public function testFailedResendPreservesThePreviouslyDeliveredLink(): void
    {
        $this->createExternalResponse();
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        [$id, $key] = $this->getConfirmationLink();
        Carbon::setTestNow(now()->addMinute());
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Test transport failure'));
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('checkCSRF')->willReturn(true);

        self::assertFalse((new TabHandler())->requestEmailValidation([], $this->request)->getStatus());
        self::assertSame(hash('sha256', $key), $this->getPendingAssociation()['tokenHash']);
        self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $key));
    }

    public function testAssociationDoesNotConsumeOrReplaceNativeAccountValidation(): void
    {
        $this->createExternalResponse();
        $native = new \PKP\invitation\invitations\registrationAccess\RegistrationAccessInvite();
        $native->initialize($this->user->getId(), $this->context->getId());
        $native->invite();
        $nativeBefore = InvitationModel::find($native->getId())->getAttributes();

        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        [$id, $key] = $this->getConfirmationLink();
        self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $key));

        self::assertSame($nativeBefore, InvitationModel::find($native->getId())->getAttributes());
        self::assertNull(Repo::user()->get($id, true)->getDateValidated());
    }

    public function testTokenCannotBeUsedInAnotherContextOrAccount(): void
    {
        $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        $otherContext = clone $this->context;
        $otherContext->setId($this->context->getId() + 1);
        $service = new EmailAssociationService();

        self::assertFalse($service->confirm($otherContext, $id, $key));
        self::assertFalse($service->confirm($this->context, $id + 1000000, $key));
        self::assertTrue($service->confirm($this->context, $id, $key));
    }

    public function testEmailChangeInvalidatesTheToken(): void
    {
        $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        DB::table('users')->where('user_id', $id)->update(['email' => 'changed@example.org']);

        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $key));
    }

    public function testDisabledAccountCannotConfirmAssociation(): void
    {
        $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        DB::table('users')->where('user_id', $id)->update(['disabled' => 1]);

        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $key));
        self::assertSame(1, (int) DB::table('users')->where('user_id', $id)->value('disabled'));
        self::assertNull(Repo::user()->get($id, true)->getDateValidated());
    }

    public function testConfirmationDoesNotReplaceAnExistingConsentChoice(): void
    {
        $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        (new \APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO())
            ->updateDeiaConsent($this->context->getId(), $id, false);

        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $key));
        self::assertSame([], $this->getPendingAssociation());
    }

    public function testGetConfirmationPageDoesNotAssociateResponses(): void
    {
        $responseId = $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        $request = $this->confirmationRequest($id, $key, false, true);
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $template = $this->getMockBuilder(TemplateManager::class)
            ->onlyMethods(['display'])->getMock();
        $template->expects(self::once())->method('display')
            ->with($plugin->getTemplateResource('confirmEmailAssociation.tpl'));
        Registry::set('templateManager', $template);

        (new EmailAssociationHandler())->index([], $request);

        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
        self::assertNotEmpty($this->getPendingAssociation());
    }

    public function testConfirmationPostAssociatesResponsesWithoutAProfileVisit(): void
    {
        $responseId = $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        $request = $this->confirmationRequest($id, $key, true, true);
        $template = $this->getMockBuilder(TemplateManager::class)
            ->onlyMethods(['display'])->getMock();
        $template->expects(self::once())->method('display')->with('frontend/pages/message.tpl');
        Registry::set('templateManager', $template);

        (new EmailAssociationHandler())->index([], $request);

        self::assertSame('plugins.generic.deiaSurvey.emailValidation.associated', $template->getTemplateVars('message'));
        self::assertSame($id, (int) DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
        self::assertNull(Repo::user()->get($id, true)->getDateValidated());
    }

    public function testConfirmationWithoutCsrfDoesNotAssociateResponses(): void
    {
        $responseId = $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        $request = $this->confirmationRequest($id, $key, true, false);
        $template = $this->getMockBuilder(TemplateManager::class)
            ->onlyMethods(['display'])->getMock();
        $template->expects(self::once())->method('display')->with('frontend/pages/message.tpl');
        Registry::set('templateManager', $template);

        (new EmailAssociationHandler())->index([], $request);

        self::assertSame('plugins.generic.deiaSurvey.emailValidation.invalid', $template->getTemplateVars('message'));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
        self::assertNotEmpty($this->getPendingAssociation());
    }

    public function testAnotherLoggedInAccountCannotConfirmTheAssociation(): void
    {
        $responseId = $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        $otherUser = clone $this->user;
        $otherUser->setId($id + 1000000);
        $request = $this->confirmationRequest($id, $key, true, true, $otherUser);
        $template = $this->getMockBuilder(TemplateManager::class)->onlyMethods(['display'])->getMock();
        $template->expects(self::once())->method('display')->with('frontend/pages/message.tpl');
        Registry::set('templateManager', $template);

        (new EmailAssociationHandler())->index([], $request);

        self::assertSame('plugins.generic.deiaSurvey.emailValidation.invalid', $template->getTemplateVars('message'));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
    }

    public function testConfirmationPageRendersPostFormWithCsrfAndBoundToken(): void
    {
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $template = TemplateManager::getManager($this->request);
        $template->registerResource($plugin->getTemplateResource(), new PKPTemplateResource($plugin->getTemplatePath()));
        $template->assign([
            'pageTitle' => 'plugins.generic.deiaSurvey.emailValidation.confirm',
            'associationUserId' => $this->user->getId(),
            'associationToken' => str_repeat('b', 64),
            'pageTitleTranslated' => '',
            'isFullWidth' => false,
            'pageFooter' => '',
        ]);
        $document = new \DOMDocument();
        @$document->loadHTML($template->fetch($plugin->getTemplateResource('confirmEmailAssociation.tpl')));
        $xpath = new \DOMXPath($document);

        self::assertSame(1, $xpath->query('//form[@method="post"]//input[@name="csrfToken"]')->length);
        self::assertSame(1, $xpath->query('//form[@method="post"]//input[@name="userId" and @value="' . $this->user->getId() . '"]')->length);
        self::assertSame(1, $xpath->query('//form[@method="post"]//input[@name="token" and @value="' . str_repeat('b', 64) . '"]')->length);
    }

    public function testGuestMustLogInBeforeConfirmingAssociation(): void
    {
        $responseId = $this->createExternalResponse();
        (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
        [$id, $key] = $this->getConfirmationLink();
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getUser', 'getContext', 'getUserVar', 'isPost', 'getCompleteUrl', 'redirect'])->getMock();
        $request->method('getContext')->willReturn($this->context);
        $request->method('getUserVar')->willReturnMap([['userId', (string) $id], ['token', $key]]);
        $template = $this->getMockBuilder(TemplateManager::class)->onlyMethods(['display'])->getMock();
        Registry::set('templateManager', $template);
        $request->method('getUser')->willReturn(null);
        $request->method('getCompleteUrl')->willReturn('https://example.org/confirmation');
        $request->expects(self::once())->method('redirect')
            ->with(null, 'login', null, null, ['source' => 'https://example.org/confirmation']);

        (new EmailAssociationHandler())->index([], $request);

        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $responseId)->value('user_id'));
    }

    private function confirmationRequest(int $userId, string $token, bool $post, bool $csrf, ?User $loggedUser = null): Request
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getContext', 'getUser', 'getUserVar', 'isPost', 'checkCSRF'])->getMock();
        $request->method('getContext')->willReturn($this->context);
        $request->method('getUser')->willReturn($loggedUser ?? $this->user);
        $request->method('getUserVar')->willReturnMap([['userId', (string) $userId], ['token', $token]]);
        $request->method('isPost')->willReturn($post);
        $request->method('checkCSRF')->willReturn($csrf);
        return $request;
    }

    private function getPendingAssociation(): array
    {
        return json_decode(DB::table('user_settings')->where('user_id', $this->user->getId())
            ->where('setting_name', EmailAssociationService::SETTING_NAME)->value('setting_value') ?? '{}', true);
    }

    private function installDefaultEmailForLocale(string $locale): void
    {
        Locale::setLocale($locale);
        DB::table('journal_settings')->where('journal_id', $this->context->getId())
            ->where('setting_name', 'supportedLocales')->update(['setting_value' => json_encode(['en', $locale])]);
        DB::table('email_templates_default_data')->updateOrInsert([
            'email_key' => ConfirmDeiaEmail::getEmailTemplateKey(), 'locale' => $locale,
        ], [
            'name' => __('emails.confirmDeiaEmail.name'),
            'subject' => __('emails.confirmDeiaEmail.subject'),
            'body' => __('emails.confirmDeiaEmail.body'),
        ]);
    }

    private function getConfirmationLink(): array
    {
        $mail = Mail::sent(ConfirmDeiaEmail::class)->first();
        $document = new \DOMDocument();
        $document->loadHTML($mail->render());
        foreach ($document->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            if (str_contains($href, '/deiaAssociation')) {
                parse_str(parse_url($href, PHP_URL_QUERY), $query);
                return [(int) $query['userId'], $query['token']];
            }
        }
        self::fail('The email must contain the DEIA association confirmation link.');
    }

    private function createExternalResponse(?int $contextId = null, ?string $email = null, string $type = 'email'): int
    {
        $questionId = DB::table('deia_questions')->insertGetId([
            'context_id' => $contextId ?? $this->context->getId(),
            'question_type' => 1,
        ]);
        return (int) DB::table('deia_responses')->insertGetId([
            'deia_question_id' => $questionId,
            'external_id' => $email ?? $this->user->getEmail(),
            'external_type' => $type,
        ]);
    }
}
