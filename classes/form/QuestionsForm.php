<?php

namespace APP\plugins\generic\deiaSurvey\classes\form;

use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
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
            $this->setData('deiaDataConsent', $args['deiaDataConsent']);
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
        $this->setData('applicationName', \Application::get()->getName());

        $context = $this->request->getContext();
        $this->initConsentData($context);

        $deiaDataService  = new DeiaDataService();
        $questionBlocks = $deiaDataService->retrieveQuestionBlocks($context->getId(), true);
        $this->setData('questionBlocks', $questionBlocks);
        $this->setData('questionTypeConsts', DeiaQuestion::getQuestionTypeConstants());

        parent::initData();
    }

    private function initConsentData($context)
    {
        $user = $this->request->getUser();
        $deiaDataDao = new DeiaDataDAO();

        $userConsent = $deiaDataDao->getDeiaConsentOption($context->getId(), $user->getId());
        $this->setData('deiaDataConsent', $userConsent);

        $userConsentSetting = $deiaDataDao->getConsentSetting($user->getId());
        if (!is_null($userConsentSetting)) {
            $contextDao = \DAORegistry::getDAO('JournalDAO');
            $userConsentSetting = array_map(function ($contextConsent) use ($contextDao) {
                $consentSettingContext = $contextDao->getById($contextConsent['contextId']);
                $contextConsent['contextName'] = $consentSettingContext->getLocalizedName();
                return $contextConsent;
            }, $userConsentSetting['value']);
        }
        $this->setData('userConsentSetting', $userConsentSetting);
    }

    public function validate($callHooks = true)
    {
        $dataConsentOption = $this->getData('deiaDataConsent');

        $deiaDataDao = new DeiaDataDAO();
        $context = $this->request->getContext();
        $user = $this->request->getUser();
        $userConsentSetting = $deiaDataDao->getConsentSetting($user->getId());
        $previousConsentOption = $deiaDataDao->getDeiaConsentOption($context->getId(), $user->getId());

        if (!is_null($userConsentSetting) && is_null($previousConsentOption)) {
            return false;
        }

        if ($dataConsentOption) {
            $locale = $this->defaultLocale;

            foreach ($this->getData('responses') as $questionId => $response) {
                $inputType = explode('-', $questionId)[2];

                if (($inputType == 'text' || $inputType == 'textarea') && empty($response[$locale])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function execute(...$functionArgs)
    {
        $deiaDataDao = new DeiaDataDAO();
        $deiaDataService  = new DeiaDataService();
        $context = $this->request->getContext();
        $user = $this->request->getUser();
        $previousConsent = $deiaDataDao->getDeiaConsentOption($context->getId(), $user->getId());
        $newConsent = $this->getData('deiaDataConsent');

        $deiaDataDao->updateDeiaConsent($context->getId(), $user->getId(), $newConsent);

        if ($newConsent == '1') {
            $deiaDataService->registerUserResponses($user->getId(), $this->getData('responses'), $this->getData('responseOptionsInputs'));
        } elseif ($newConsent == '0' && $previousConsent) {
            $deiaDataService->deleteUserResponses($user->getId(), $context->getId());
        }

        parent::execute(...$functionArgs);
    }
}
