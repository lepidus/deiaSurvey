<?php

namespace APP\plugins\generic\deiaSurvey\classes\form;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DeiaDataService;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;

class QuestionsForm extends Form
{
    private $request;

    public function __construct($request = null, $args = null)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        parent::__construct($plugin->getTemplateResource('questionsInProfile.tpl'));

        if ($request) {
            $this->request = $request;
        }

        if ($args) {
            $this->setData('deiaDataConsent', $args['deiaDataConsent']);
            $this->loadQuestionResponsesByForm($args);
        }

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
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
        $templateMgr = TemplateManager::getManager($request);

        return parent::fetch($request, $template, $display);
    }

    public function initData()
    {
        $applicationName = Application::get()->getName();
        $this->setData('applicationName', $applicationName);

        $context = $this->request->getContext();
        $this->initConsentData($context, $applicationName);

        $deiaDataService = new DeiaDataService();
        $this->setData('questionBlocks', $deiaDataService->retrieveQuestionBlocks($context->getId(), true));
        $this->setData('questionTypeConsts', DeiaQuestion::getQuestionTypeConstants());

        parent::initData();
    }

    private function initConsentData($context, $applicationName)
    {
        $user = $this->request->getUser();
        $deiaDataDao = new DeiaDataDAO();

        $userConsent = $deiaDataDao->getDeiaConsentOption($context->getId(), $user->getId());
        $this->setData('deiaDataConsent', $userConsent);

        $userConsentSetting = $deiaDataDao->getConsentSetting($user->getId());
        if (!is_null($userConsentSetting)) {
            $contextDao = DAORegistry::getDAO(($applicationName == 'ojs2') ? 'JournalDAO' : 'ServerDAO');
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
        $deiaDataService = new DeiaDataService();
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm', '\QuestionsForm');
}
