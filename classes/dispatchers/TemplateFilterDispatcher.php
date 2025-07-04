<?php

namespace APP\plugins\generic\deiaSurvey\classes\dispatchers;

use APP\core\Application;
use PKP\plugins\Hook;
use APP\plugins\generic\deiaSurvey\classes\dispatchers\DemographicDataDispatcher;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;

class TemplateFilterDispatcher extends DemographicDataDispatcher
{
    protected function registerHooks(): void
    {
        Hook::add('TemplateManager::display', [$this, 'addChangesOnTemplateDisplaying']);
    }

    public function addChangesOnTemplateDisplaying(string $hookName, array $params)
    {
        $templateMgr = $params[0];
        $template = $params[1];

        if ($template === 'user/profile.tpl') {
            $this->addChangesToUserProfilePage($templateMgr);
            return Hook::CONTINUE;
        }

        $backendMenuState = $templateMgr->getState('menu');
        if (!empty($backendMenuState)) {
            $request = Application::get()->getRequest();
            if ($this->plugin->userShouldBeRedirected($request)) {
                $request->redirect(null, 'user', 'profile');
            }
        }
    }

    public function addChangesToUserProfilePage($templateMgr)
    {
        $templateMgr->registerFilter('output', [$this, 'deiaSurveyTabFilter']);

        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
        $userId = $request->getUser()->getId();
        $demographicDataDao = new DemographicDataDAO();
        $consent = $demographicDataDao->getDemographicConsent($contextId, $userId);

        if (is_null($consent)) {
            $templateMgr->registerFilter('output', [$this, 'requestMessageFilter']);
        }
    }

    public function deiaSurveyTabFilter($output, $templateMgr)
    {
        $regexListItemTabPosition = '/<div[^>]+id="profileTabs"[^>]*>.*?<ul[^>]*>((?:(?!<\/ul>).)*?<li>\s*<a[^>]*?name="(?:apiSettings)"[^>]*?>.*?<\/li>)/s';
        if (preg_match($regexListItemTabPosition, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $offset = $matches[0][1];
            $newOutput = substr($output, 0, $offset + strlen($match));
            $newOutput .= $templateMgr->fetch($this->plugin->getTemplateResource('deiaSurveyTab.tpl'));
            $newOutput .= substr($output, $offset + strlen($match));
            $output = $newOutput;
            $templateMgr->unregisterFilter('output', [$this, 'deiaSurveyTabFilter']);
        }
        return $output;
    }

    public function requestMessageFilter($output, $templateMgr)
    {
        $profileTabsPattern = '/<div[^>]+id="profileTabs"/';
        if (preg_match($profileTabsPattern, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];

            $newOutput = substr($output, 0, $offset);
            $newOutput .= $templateMgr->fetch($this->plugin->getTemplateResource('requestMessage.tpl'));
            $newOutput .= substr($output, $offset);

            $output = $newOutput;
            $templateMgr->unregisterFilter('output', [$this, 'requestMessageFilter']);
        }
        return $output;
    }
}
