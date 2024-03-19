<?php

namespace APP\plugins\generic\demographicData\classes\form;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\user\User;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\demographicQuestion\Collector;

class QuestionsForm extends Form
{
    public function __construct()
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        parent::__construct($plugin->getTemplateResource('questions.tpl'));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);

        return parent::fetch($request, $template, $display);
    }

    public function initData()
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $questions = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();
        $this->setData('questions', $questions);
        parent::initData();
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
    class_alias('\APP\plugins\generic\demographicData\classes\form\QuestionsForm', '\QuestionsForm');
}
