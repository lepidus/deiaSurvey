<?php

namespace APP\plugins\generic\demographicData\classes\dispatchers;

use APP\plugins\generic\demographicData\classes\DemographicDataDAO;

class TemplateFilterDispatcher
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function dispatch($templateMgr)
    {
        $templateMgr->registerFilter('output', [$this, 'demographicDataTabFilter']);

        $request = \Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
        $userId = $request->getUser()->getId();
        $demographicDataDao = new DemographicDataDAO();
        $consent = $demographicDataDao->getDemographicConsent($contextId, $userId);

        if (is_null($consent)) {
            $templateMgr->registerFilter('output', [$this, 'requestMessageFilter']);
        }
    }

    public function demographicDataTabFilter($output, $templateMgr)
    {
        $regexListItemTabPosition = '/<div[^>]+id="profileTabs"[^>]*>.*?<ul[^>]*>((?:(?!<\/ul>).)*?<li>\s*<a[^>]*?name="(?:apiSettings)"[^>]*?>.*?<\/li>)/s';
        if (preg_match($regexListItemTabPosition, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $offset = $matches[0][1];
            $newOutput = substr($output, 0, $offset + strlen($match));
            $newOutput .= $templateMgr->fetch($this->plugin->getTemplateResource('demographicDataTab.tpl'));
            $newOutput .= substr($output, $offset + strlen($match));
            $output = $newOutput;
            $templateMgr->unregisterFilter('output', [$this, 'demographicDataTabFilter']);
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
