<?php

namespace APP\plugins\generic\demographicData\classes\form;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;
use APP\plugins\generic\demographicData\classes\DemographicDataDAO;
use APP\plugins\generic\demographicData\classes\DemographicDataService;
use APP\plugins\generic\demographicData\classes\facades\Repo;

class QuestionsForm extends Form
{
    private $request;

    public function __construct($request = null, $args = null)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        parent::__construct($plugin->getTemplateResource('questions.tpl'));

        if ($request) {
            $this->request = $request;
        }

        if ($args) {
            $this->setData('demographicDataConsent', $args['demographicDataConsent']);
            $this->loadQuestionResponsesByForm($args);
        }

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    private function loadQuestionResponsesByForm($args)
    {
        $responses = [];
        foreach ($args as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            }
        }

        $this->setData('responses', $responses);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);

        return parent::fetch($request, $template, $display);
    }

    public function initData()
    {
        $context = $this->request->getContext();
        $user = $this->request->getUser();
        $demographicDataDao = new DemographicDataDAO();
        $userConsent = $demographicDataDao->getDemographicConsent($context->getId(), $user->getId());
        $this->setData('demographicDataConsent', $userConsent);

        $demographicDataService  = new DemographicDataService();
        $questions = $demographicDataService->retrieveQuestions(true);
        $this->setData('questions', $questions);
        parent::initData();
    }

    public function validate($callHooks = true)
    {
        $consent = $this->getData('demographicDataConsent');

        if ($consent) {
            $locale = $this->defaultLocale;

            foreach ($this->getData('responses') as $response) {
                if (empty($response[$locale])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function execute(...$functionArgs)
    {
        $context = $this->request->getContext();
        $user = $this->request->getUser();
        $consent = $this->getData('demographicDataConsent');

        $demographicDataDao = new DemographicDataDAO();
        $demographicDataDao->updateDemographicConsent($context->getId(), $user->getId(), $consent);

        if ($consent) {
            DemographicDataService::registerResponse($user->getId(), $this->getData('responses'));
        }

        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\classes\form\QuestionsForm', '\QuestionsForm');
}
