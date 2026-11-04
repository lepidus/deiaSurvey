<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . '/helpers/EmailAssociationTestCase.php';

use APP\plugins\generic\deiaSurvey\classes\DataCollectionEmailSender;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\EmailAssociationService;
use APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm;
use APP\plugins\generic\deiaSurvey\classes\mail\mailables\ConfirmDeiaEmail;
use APP\plugins\generic\deiaSurvey\pages\deia\EmailAssociationHandler;
use APP\plugins\generic\deiaSurvey\tests\helpers\EmailAssociationTestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailValidationTest extends EmailAssociationTestCase
{
    private $request;
    private $plugin;
    private $originalMailer;
    private $originalNow;
    private array $messages = [];
    private bool $failDelivery = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalNow = Carbon::getTestNow();
        $this->request = $this->getMockBuilder(\APP\core\Request::class)
            ->onlyMethods(['getContext', 'getUser', 'isPost', 'checkCSRF', 'getUserVar'])->getMock();
        $this->request->method('getContext')->willReturn($this->context);
        $this->request->method('getUser')->willReturn($this->user);
        $dispatcher = \APP\core\Application::get()->getDispatcher();
        $router = new \APP\core\PageRouter();
        $router->setApplication(\APP\core\Application::get());
        $router->setDispatcher($dispatcher);
        $this->request->setRouter($router);
        $this->request->setDispatcher($dispatcher);
        \PKP\core\Registry::set('request', $this->request);
        $this->plugin = new \APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin();
        $this->plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $this->plugin->addLocaleData();
        $plugins = &\PKP\plugins\PluginRegistry::getPlugins('generic');
        $plugins['deiasurveyplugin'] = $this->plugin;
        $this->originalMailer = Mail::getFacadeRoot();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        try {
            Mail::swap($this->originalMailer);
            Carbon::setTestNow($this->originalNow);
        } finally {
            parent::tearDown();
        }
    }

    public function testAssociationLinkEscapesHtmlWithoutChangingItsToken(): void
    {
        $this->sendAndGetLink();
        self::assertStringContainsString('&amp;token=', $this->messageBody());
    }

    public function testConfirmationAssociatesResponsesWithoutActivatingTheAccount(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        $account = DB::table('users')->where('user_id', $id)->first();
        self::assertSame(hash('sha256', $token), $this->pending()['tokenHash']);
        self::assertStringNotContainsString($token, json_encode($this->pending()));

        self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $token));

        self::assertEquals($account, DB::table('users')->where('user_id', $id)->first());
        self::assertSame($id, (int) DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
        self::assertSame($this->ciphertext, DB::table('deia_response_settings')->value('setting_value'));
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $token));
    }

    public function testOldValidatedAccountWithoutRegistrationProofCanRequestConfirmation(): void
    {
        DB::table('user_settings')->where('user_id', $this->user->getId())->where('setting_name', 'deiaRegistrationEmail')->delete();
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => '2026-01-01 00:00:00']);
        [$id, $token] = $this->sendAndGetLink();
        self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $token));
        self::assertSame('2026-01-01 00:00:00', DB::table('users')->where('user_id', $id)->value('date_validated'));
    }

    public function testAccountWithReliableNativeValidationDoesNotReceiveConfirmation(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => date('Y-m-d H:i:s')]);
        self::assertFalse((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
    }

    public function testResendWaitsOneMinuteAndInvalidatesThePreviousToken(): void
    {
        Carbon::setTestNow('2026-11-03 12:00:00');
        [$id, $token] = $this->sendAndGetLink();
        Carbon::setTestNow('2026-11-03 12:00:59');
        self::assertFalse((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        Carbon::setTestNow('2026-11-03 12:01:00');
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $token));
    }

    public function testExpiredOrWrongTokenCannotAssociateResponses(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, str_repeat('a', 64)));
        Carbon::setTestNow(Carbon::now()->addDay());
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $token));
    }

    public function testTokenCannotBeUsedInAnotherContextOrAccount(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        $otherContext = clone $this->context;
        $otherContext->setId($this->context->getId() + 1000000);
        self::assertFalse((new EmailAssociationService())->confirm($otherContext, $id, $token));
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id + 1000000, $token));
        self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $token));
    }

    public function testEmailChangeInvalidatesTheToken(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        DB::table('users')->where('user_id', $id)->update(['email' => 'changed@example.org']);
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $token));
    }

    public function testDisabledAccountCannotRequestOrConfirmAssociation(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        DB::table('users')->where('user_id', $id)->update(['disabled' => 1]);
        self::assertFalse((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $token));
    }

    public function testExistingConsentOrResponsesAreNotOverwritten(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        (new DeiaDataDAO())->updateDeiaConsent($this->context->getId(), $id, false);
        self::assertFalse((new EmailAssociationService())->confirm($this->context, $id, $token));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testEmailUsesFreshPersistedRecipientAndOjsGreetingSignatureAndFooter(): void
    {
        $this->user->setEmail('untrusted@example.org');
        $this->sendAndGetLink();
        $body = $this->messageBody();
        self::assertStringContainsString('Test User', $body);
        self::assertStringContainsString('Test contact', $body);
        self::assertStringContainsString('DEIA test journal', $body);
        self::assertStringContainsString('automated message', $body);
        self::assertStringNotContainsString('{$', $body);
        self::assertTrue(Mail::sent(ConfirmDeiaEmail::class)->first()->hasTo($this->pending()['email']));
    }

    public function testFailedDeliveryRollsBackThePendingToken(): void
    {
        $this->failDelivery = true;
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Test transport failure'));
        try {
            (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
            self::fail('The transport must fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame([], $this->pending());
        }
    }

    public function testFailedResendPreservesThePreviouslyDeliveredToken(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        Carbon::setTestNow(Carbon::now()->addMinute());
        $this->failDelivery = true;
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Test transport failure'));
        try {
            (new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user);
            self::fail('The transport must fail.');
        } catch (\RuntimeException $exception) {
            self::assertTrue((new EmailAssociationService())->confirm($this->context, $id, $token));
        }
    }

    public function testGetConfirmationPageDoesNotMigrate(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        $request = $this->confirmationRequest($id, $token, false, true);
        $template = $this->getMockBuilder(\APP\template\TemplateManager::class)->onlyMethods(['display'])->getMock();
        $template->expects(self::once())->method('display')->with($this->plugin->getTemplateResource('confirmEmailAssociation.tpl'));
        \PKP\core\Registry::set('templateManager', $template);
        (new EmailAssociationHandler())->index([], $request);
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testConfirmationPostMigratesDirectlyAndRequiresCsrf(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        $template = $this->getMockBuilder(\APP\template\TemplateManager::class)->onlyMethods(['display'])->getMock();
        $template->expects(self::exactly(2))->method('display')->with('frontend/pages/message.tpl');
        \PKP\core\Registry::set('templateManager', $template);
        $handler = new EmailAssociationHandler();
        $handler->index([], $this->confirmationRequest($id, $token, true, false));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
        $handler->index([], $this->confirmationRequest($id, $token, true, true));
        self::assertSame($id, (int) DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testAnotherLoggedInAccountCannotConfirmTheAssociation(): void
    {
        [$id, $token] = $this->sendAndGetLink();
        $otherUser = clone $this->user;
        $otherUser->setId($id + 1000000);
        $template = $this->getMockBuilder(\APP\template\TemplateManager::class)->onlyMethods(['display'])->getMock();
        \PKP\core\Registry::set('templateManager', $template);
        (new EmailAssociationHandler())->index([], $this->confirmationRequest($id, $token, true, true, $otherUser));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testNoticeOnlyAppearsForMatchingExternalResponses(): void
    {
        $form = new QuestionsForm($this->request);
        $form->initData();
        self::assertTrue($form->getData('canValidateEmail'));
        DB::table('deia_responses')->where('deia_response_id', $this->responseId)->update(['external_id' => 'another@example.org']);
        $form->initData();
        self::assertFalse($form->getData('canValidateEmail'));
    }

    public function testGuestIsRedirectedToLoginToResumeConfirmation(): void
    {
        $request = $this->getMockBuilder(\APP\core\Request::class)->onlyMethods(['getUser', 'getCompleteUrl', 'redirect'])->getMock();
        $request->method('getUser')->willReturn(null);
        $request->method('getCompleteUrl')->willReturn('https://example.org/confirmation');
        $request->expects(self::once())->method('redirect')->with(null, 'login', null, null, ['source' => 'https://example.org/confirmation']);
        (new EmailAssociationHandler())->index([], $request);
    }

    public function testRequestEndpointRejectsGetAndPostWithoutCsrf(): void
    {

        $this->request->method('isPost')->willReturn(false);
        self::assertFalse((new \APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler())->requestEmailValidation([], $this->request)->getStatus());
        $post = $this->confirmationRequest($this->user->getId(), str_repeat('a', 64), true, false);
        self::assertFalse((new \APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler())->requestEmailValidation([], $post)->getStatus());
        self::assertSame([], $this->pending());
    }

    public function testAuthenticatedRequestEndpointSendsConfirmationAndNativeNotification(): void
    {

        $request = $this->confirmationRequest($this->user->getId(), '', true, true);
        $template = $this->getMockBuilder(\APP\template\TemplateManager::class)->onlyMethods(['fetch'])->getMock();
        $template->method('fetch')->willReturn('<div class="notifySuccess">Confirmation sent</div>');
        \PKP\core\Registry::set('templateManager', $template);
        $result = (new \APP\plugins\generic\deiaSurvey\classes\controllers\TabHandler())->requestEmailValidation([], $request);
        self::assertTrue($result->getStatus());
        self::assertStringContainsString('pkp_notification', $result->getContent());
        self::assertNotEmpty($this->pending());
    }

    public function testCustomContextEmailTemplateIsUsed(): void
    {
        $template = \APP\facades\Repo::emailTemplate()->newDataObject([
            'key' => 'CONFIRM_DEIA_EMAIL', 'contextId' => $this->context->getId(), 'enabled' => true,
            'subject' => ['en' => 'Custom association'],
            'body' => ['en' => '<a href="{$associationUrl}">Custom association link</a>'],
        ]);
        \APP\facades\Repo::emailTemplate()->add($template);
        $this->sendAndGetLink();
        self::assertStringContainsString('Custom association link', $this->messageBody());
    }

    private function confirmationRequest(int $id, string $token, bool $post, bool $csrf, $user = null)
    {
        $request = $this->getMockBuilder(\APP\core\Request::class)->onlyMethods(['getUser', 'getContext', 'getUserVar', 'isPost', 'checkCSRF'])->getMock();
        $request->method('getUser')->willReturn($user ?? $this->user);
        $request->method('getContext')->willReturn($this->context);
        $request->method('getUserVar')->willReturnMap([['userId', (string) $id], ['token', $token]]);
        $request->method('isPost')->willReturn($post);
        $request->method('checkCSRF')->willReturn($csrf);
        return $request;
    }

    private function pending(): array
    {
        return json_decode(DB::table('user_settings')->where('user_id', $this->user->getId())
            ->where('setting_name', EmailAssociationService::SETTING_NAME)->value('setting_value') ?? '{}', true);
    }

    private function messageBody(): string
    {
        $mail = Mail::sent(ConfirmDeiaEmail::class)->first();
        $fake = Mail::getFacadeRoot();
        Mail::swap($this->originalMailer);
        try {
            $mail->setData('en');
            return $mail->render();
        } finally {
            Mail::swap($fake);
        }
    }

    private function sendAndGetLink(): array
    {
        self::assertTrue((new DataCollectionEmailSender())->sendEmailValidation($this->context, $this->user));
        $document = new \DOMDocument();
        $document->loadHTML($this->messageBody());
        foreach ($document->getElementsByTagName('a') as $link) {
            if (strpos($link->getAttribute('href'), '/deiaAssociation') !== false) {
                parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
                return [(int) $query['userId'], $query['token']];
            }
        }
        self::fail('The email must contain the DEIA association link.');
    }
}
