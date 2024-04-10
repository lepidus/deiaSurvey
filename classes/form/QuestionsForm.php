<?php

namespace APP\plugins\generic\demographicData\classes\form;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;
use APP\plugins\generic\demographicData\classes\DemographicDataService;

class QuestionsForm extends Form
{
    private $request;
    private $args;

    public function __construct($request = null, $args = null)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        if ($request) {
            $this->request = $request;
        }

        if ($args) {
            $this->args = $this->getQuestionResponsesByForm($args);
        }

        parent::__construct($plugin->getTemplateResource('questions.tpl'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    private function getQuestionResponsesByForm($args)
    {
        $responses = array();
        foreach ($args as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            }
        }
        return $responses;
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);

        return parent::fetch($request, $template, $display);
    }

    public function initData()
    {
        $questions = DemographicDataService::retrieveQuestions();
        $this->setData('questions', $questions);
        parent::initData();
    }

    public function execute(...$functionArgs)
    {
        $userId = $this->request->getUser()->getId();
        DemographicDataService::registerResponse($userId, $this->args);
        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\classes\form\QuestionsForm', '\QuestionsForm');
}
