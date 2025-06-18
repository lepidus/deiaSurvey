<?php

namespace APP\plugins\generic\deiaSurvey\classes\form;

use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\template\TemplateManager;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\plugins\PluginRegistry;

import('lib.pkp.classes.form.Form');

class QuestionsForm extends \Form
{
    private $request;

    public function __construct($request = null, $args = null)
    {
        $plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        parent::__construct($plugin->getTemplateResource('questionsInProfile.tpl'));

        if ($request) {
            $this->request = $request;
        }

        if ($args) {
            $this->setData('demographicDataConsent', $args['demographicDataConsent']);
            $this->loadQuestionResponsesByForm($args);
        }

        $this->addCheck(new \FormValidatorPost($this));
        $this->addCheck(new \FormValidatorCSRF($this));
    }

    private function loadQuestionResponsesByForm($args)
    {
        $responses = [];
        $responseOptionsInputs = [];

        foreach ($args as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            } elseif (strpos($key, 'responseOptionInput-') === 0) {
                $responseOptionsInputs[$key] = $value;
            }
        }

        $this->setData('responses', $responses);
        $this->setData('responseOptionsInputs', $responseOptionsInputs);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = \TemplateManager::getManager($request);

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
        $questions = $demographicDataService->retrieveAllQuestions($context->getId(), true);
        $this->setData('questions', $questions);
        $this->setData('questionTypeConsts', DemographicQuestion::getQuestionTypeConstants());

        parent::initData();
    }

    public function validate($callHooks = true)
    {
        $consent = $this->getData('demographicDataConsent');

        if ($consent) {
            $locale = $this->defaultLocale;

            foreach ($this->getData('responses') as $questionId => $response) {
                $inputType = explode('-', $questionId)[2];

                if (($inputType == 'text' or $inputType == 'textarea') and empty($response[$locale])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function execute(...$functionArgs)
    {
        $demographicDataDao = new DemographicDataDAO();
        $demographicDataService  = new DemographicDataService();
        $context = $this->request->getContext();
        $user = $this->request->getUser();
        $previousConsent = $demographicDataDao->getDemographicConsent($context->getId(), $user->getId());
        $newConsent = $this->getData('demographicDataConsent');

        $demographicDataDao->updateDemographicConsent($context->getId(), $user->getId(), $newConsent);

        if ($newConsent == '1') {
            $demographicDataService->registerUserResponses($user->getId(), $this->getData('responses'), $this->getData('responseOptionsInputs'));
        } elseif ($newConsent == '0' and $previousConsent) {
            $demographicDataService->deleteUserResponses($user->getId(), $context->getId());
        }

        parent::execute(...$functionArgs);
    }
}
