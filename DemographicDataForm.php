<?php

namespace APP\plugins\generic\demographicData;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\user\User;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;

class DemographicDataForm extends Form
{
    public function __construct()
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        parent::__construct($plugin->getTemplateResource('demographicData.tpl'));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);

        return parent::fetch($request, $template, $display);
    }

    public function initData()
    {
    }

    public function readInputData()
    {
        parent::readInputData();
    }

    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\DemographicDataForm', '\DemographicDataForm');
}
