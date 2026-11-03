<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once __DIR__ . "/helpers/EmailAssociationTestCase.php";
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\tests\helpers\EmailAssociationTestCase;
use Illuminate\Support\Facades\DB;

class NativeEmailResponseAssociationTest extends EmailAssociationTestCase
{
    public function testUnvalidatedAccountDoesNotReceiveExternalResponses(): void
    {
        $result = (new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email');
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
        self::assertFalse($result);
    }

    public function testPersistedNativeValidationAssociatesResponsesOnceAndPreservesCiphertext(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => date('Y-m-d H:i:s')]);
        $service = new DeiaDataService();

        self::assertTrue($service->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        $response = DB::table('deia_responses')->where('deia_response_id', $this->responseId)->first();
        self::assertSame((int) $this->user->getId(), (int) $response->user_id);
        self::assertNull($response->external_id);
        self::assertNull($response->external_type);
        self::assertSame($this->ciphertext, DB::table('deia_response_settings')->value('setting_value'));
        self::assertTrue((new DeiaDataDAO())->getDeiaConsentOption($this->context->getId(), $this->user->getId()));
        self::assertFalse($service->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testDisabledAccountCannotAssociateResponses(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update([
            'date_validated' => date('Y-m-d H:i:s'), 'disabled' => 1,
        ]);
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testConsentRefusalPreventsAssociation(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => date('Y-m-d H:i:s')]);
        (new DeiaDataDAO())->updateDeiaConsent($this->context->getId(), $this->user->getId(), false);
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testExistingAccountResponsesPreventAssociation(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => date('Y-m-d H:i:s')]);
        DB::table('deia_responses')->insert(['deia_question_id' => $this->questionId, 'user_id' => $this->user->getId()]);
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testProfileEmailChangeCannotReuseValidationOfTheOldAddress(): void
    {
        $originalEmail = 'original-' . $this->user->getId() . '@example.org';
        DB::table('users')->where('user_id', $this->user->getId())->update([
            'email' => $originalEmail, 'date_validated' => null,
        ]);
        $this->user->setEmail($originalEmail);
        DB::table('user_settings')->where('user_id', $this->user->getId())->where('setting_name', 'deiaRegistrationEmail')->delete();
        (new DeiaDataService())->recordRegistrationEmail($this->user);
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => '2026-01-01 00:00:00']);
        $this->user->setDateValidated('2026-01-01 00:00:00');
        $site = \APP\core\Application::get()->getRequest()->getSite();
        $request = $this->getMockBuilder(\APP\core\Request::class)
            ->onlyMethods(['getUser', 'getContext', 'getSite'])->getMock();
        $request->method('getUser')->willReturn($this->user);
        $request->method('getContext')->willReturn($this->context);
        $request->method('getSite')->willReturn($site);
        \PKP\core\Registry::set('request', $request);
        $template = new \APP\template\TemplateManager();
        \PKP\core\Registry::set('templateManager', $template);
        $plugin = $this->getMockBuilder(\APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin::class)
            ->onlyMethods(['getEnabled', 'loadDispatcherClasses', 'registerReportPlugin'])->getMock();
        $plugin->method('getEnabled')->willReturn(true);
        $plugin->register('generic', 'plugins/generic/deiaSurvey');
        $session = new \PKP\session\Session();
        $session->setId('deia980-isolated-session');
        $sessionManager = $this->getMockBuilder(\PKP\session\SessionManager::class)->disableOriginalConstructor()
            ->onlyMethods(['getUserSession', 'invalidateSessions'])->getMock();
        $sessionManager->method('getUserSession')->willReturn($session);
        $sessionManager->method('invalidateSessions')->willReturn(true);
        \PKP\core\Registry::set('sessionManager', $sessionManager);
        $form = new \PKP\user\form\ContactForm($this->user);
        $form->setData('email', DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('external_id'));
        $form->setData('locales', []);
        $form->setData('userLocales', []);
        $form->setData('affiliation', []);
        $form->setData('signature', []);
        $form->execute();

        self::assertSame('2026-01-01 00:00:00', DB::table('users')->where('user_id', $this->user->getId())->value('date_validated'));
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testValidatedAccountWithoutObservedRegistrationEmailRequiresConfirmation(): void
    {
        DB::table('user_settings')->where('user_id', $this->user->getId())
            ->where('setting_name', 'deiaRegistrationEmail')->delete();
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => date('Y-m-d H:i:s')]);
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testEmailChangedOutsidePluginContextCannotReuseOriginalProof(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update([
            'email' => 'victim@example.org', 'date_validated' => date('Y-m-d H:i:s'),
        ]);
        DB::table('deia_responses')->where('deia_response_id', $this->responseId)->update(['external_id' => 'victim@example.org']);
        self::assertFalse((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
    }

    public function testNativeRegistrationRecordsTheEmailWithoutAssociatingResponses(): void
    {
        DB::table('user_settings')->where('user_id', $this->user->getId())
            ->where('setting_name', 'deiaRegistrationEmail')->delete();
        $events = new \Illuminate\Events\Dispatcher();
        (new \APP\plugins\generic\deiaSurvey\classes\observers\listeners\RecordRegistrationEmail())->subscribe($events);
        $events->dispatch(new \PKP\observers\events\UserRegisteredContext($this->user, $this->context));
        self::assertSame($this->user->getEmail(), DB::table('user_settings')->where('user_id', $this->user->getId())
            ->where('setting_name', 'deiaRegistrationEmail')->value('setting_value'));
        self::assertNull(DB::table('deia_responses')->where('deia_response_id', $this->responseId)->value('user_id'));
    }

    public function testLaterObservationCannotOverwriteTheOriginalRegistrationEmail(): void
    {
        $originalEmail = $this->user->getEmail();
        DB::table('users')->where('user_id', $this->user->getId())->update(['email' => 'replacement@example.org']);
        $this->user->setEmail('replacement@example.org');
        (new DeiaDataService())->recordRegistrationEmail($this->user);

        self::assertSame($originalEmail, DB::table('user_settings')->where('user_id', $this->user->getId())
            ->where('setting_name', 'deiaRegistrationEmail')->value('setting_value'));
    }

    public function testOtherContextAndIdentifiersRemainUnassociated(): void
    {
        DB::table('users')->where('user_id', $this->user->getId())->update(['date_validated' => date('Y-m-d H:i:s')]);
        $otherQuestion = DB::table('deia_questions')->insertGetId(['context_id' => $this->context->getId() + 100000]);
        DB::table('deia_responses')->insert([
            ['deia_question_id' => $otherQuestion, 'external_id' => $this->user->getEmail(), 'external_type' => 'email'],
            ['deia_question_id' => $this->questionId, 'external_id' => 'another@example.org', 'external_type' => 'email'],
            ['deia_question_id' => $this->questionId, 'external_id' => $this->user->getEmail(), 'external_type' => 'orcid'],
        ]);
        self::assertTrue((new DeiaDataService())->migrateResponsesByUserIdentifier($this->context, $this->user, 'email'));
        self::assertSame(3, DB::table('deia_responses')->whereNull('user_id')->count());
    }
}
