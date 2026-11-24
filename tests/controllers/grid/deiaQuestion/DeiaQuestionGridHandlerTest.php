<?php

namespace APP\plugins\generic\deiaSurvey\tests\controllers\grid\deiaQuestion;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo as AppRepo;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion\DeiaQuestionGridHandler;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\tests\helpers\DatabaseTestCase;
use APP\plugins\generic\deiaSurvey\tests\helpers\TestHelperTrait;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\notification\Notification;

class DeiaQuestionGridHandlerTest extends DatabaseTestCase
{
    use TestHelperTrait;

    public function testInvalidConditionalOrderNotifiesUserAndRefreshesSavedOrder(): void
    {
        $this->initializeRequestRouter();
        $this->initializePluginLocaleData();
        $this->addSchemaFile('deiaQuestion');
        $this->addSchemaFile('deiaQuestionBlock');

        $contextId = $this->createTestContext();
        $block = Repo::deiaQuestionBlock()->newDataObject(['contextId' => $contextId, 'active' => 0]);
        $blockId = Repo::deiaQuestionBlock()->add($block);
        $sourceId = Repo::deiaQuestion()->add(Repo::deiaQuestion()->newDataObject([
            'contextId' => $contextId,
            'questionBlockId' => $blockId,
            'questionType' => DeiaQuestion::TYPE_RADIO_BUTTONS,
            'sequence' => 1,
        ]));
        $dependentId = Repo::deiaQuestion()->add(Repo::deiaQuestion()->newDataObject([
            'contextId' => $contextId,
            'questionBlockId' => $blockId,
            'questionType' => DeiaQuestion::TYPE_TEXT_FIELD,
            'sequence' => 2,
            'showWhen' => [$sourceId, 1],
        ]));

        $userId = $this->createTestUser();
        $request = $this->createMock(Request::class);
        $request->method('checkCSRF')->willReturn(true);
        $request->method('getContext')->willReturn(Application::getContextDAO()->getById($contextId));
        $request->method('getUser')->willReturn(AppRepo::user()->get($userId));
        $request->method('getUserVar')->with('data')->willReturn(json_encode([$dependentId, $sourceId]));

        $handler = new DeiaQuestionGridHandler();
        $handler->deiaQuestionBlockId = $blockId;
        $response = $handler->saveSequence([], $request);

        self::assertTrue($response->getStatus());
        self::assertSame('', $response->getContent());
        self::assertSame(['dataChanged', 'notifyUser'], array_column($response->getEvents(), 'name'));
        $questions = Repo::deiaQuestion()->getBlockQuestions($contextId, $blockId);
        self::assertSame([$sourceId, $dependentId], array_keys($questions));

        $notificationId = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('type', Notification::NOTIFICATION_TYPE_ERROR)
            ->value('notification_id');
        self::assertNotNull($notificationId);
        $settings = DAORegistry::getDAO('NotificationSettingsDAO')->getNotificationSettings($notificationId);
        self::assertSame(
            __('plugins.generic.deiaSurvey.questionBlocks.questions.invalidConditionOrder'),
            $settings['contents']
        );
    }

    public function testQuestionBlockElementsTemplateShowsEditRestrictionWarningAboveGrid(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 4) . '/templates/deiaQuestionBlocks/deiaQuestionBlockElements.tpl'
        );

        self::assertStringContainsString(
            'plugins.generic.deiaSurvey.questionBlocks.questions.editWarning',
            $template
        );
        self::assertStringContainsString('class="deiaQuestionBlockEditWarning"', $template);
        self::assertStringContainsString(
            'margin: 0 0 1rem; font-size: .875rem; line-height: 1.5rem; font-weight: 400; color: rgba(0, 0, 0, 0.54);',
            $template
        );
        self::assertLessThan(
            strpos($template, 'id="deiaQuestionGridContainer"'),
            strpos($template, 'deiaQuestionBlockEditWarning')
        );
    }
}
