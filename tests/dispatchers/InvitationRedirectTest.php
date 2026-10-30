<?php

namespace APP\plugins\generic\deiaSurvey\tests\dispatchers;

use APP\core\Request;
use APP\plugins\generic\deiaSurvey\classes\dispatchers\TemplateFilterDispatcher;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use APP\template\TemplateManager;
use PKP\core\Registry;
use PKP\tests\PKPTestCase;

class InvitationRedirectTest extends PKPTestCase
{
    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'request', 'hooks'];
    }

    public function testDoesNotRedirectEmailConfirmationPageBeforeActivation(): void
    {
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getRequestedPage', 'redirect'])->getMock();
        $request->method('getRequestedPage')->willReturn('invitation');
        $request->expects(self::never())->method('redirect');
        Registry::set('request', $request);

        $dispatcher = new TemplateFilterDispatcher($this->createPluginRequiringDeiaConsent());
        $dispatcher->addChangesOnTemplateDisplaying('TemplateManager::display', [
            $this->createTemplateWithBackendMenu(),
            'frontend/pages/userConfirmActivation.tpl',
        ]);
    }

    public function testStillRedirectsBackendPagesWhenConsentIsMissing(): void
    {
        $request = $this->getMockBuilder(Request::class)->onlyMethods(['getRequestedPage', 'redirect'])->getMock();
        $request->method('getRequestedPage')->willReturn('submissions');
        $request->expects(self::once())->method('redirect')->with(null, 'user', 'profile');
        Registry::set('request', $request);

        $dispatcher = new TemplateFilterDispatcher($this->createPluginRequiringDeiaConsent());
        $dispatcher->addChangesOnTemplateDisplaying('TemplateManager::display', [
            $this->createTemplateWithBackendMenu(),
            'submissions.tpl',
        ]);
    }

    private function createPluginRequiringDeiaConsent(): DeiaSurveyPlugin
    {
        $plugin = $this->getMockBuilder(DeiaSurveyPlugin::class)->onlyMethods(['userShouldBeRedirected'])->getMock();
        $plugin->method('userShouldBeRedirected')->willReturn(true);
        return $plugin;
    }

    private function createTemplateWithBackendMenu(): TemplateManager
    {
        $template = $this->getMockBuilder(TemplateManager::class)
            ->disableOriginalConstructor()->onlyMethods(['getState'])->getMock();
        $template->method('getState')->with('menu')->willReturn(['submissions' => ['name' => 'Submissions']]);
        return $template;
    }
}
