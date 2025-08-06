<?php

namespace APP\plugins\generic\deiaSurvey\classes\form;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;
use PKP\db\DAORegistry;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataDAO;
use APP\plugins\generic\deiaSurvey\classes\DemographicDataService;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

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
            $this->setData('demographicDataConsent', $args['demographicDataConsent']);
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

        $demographicDataService  = new DemographicDataService();
        $questions = $demographicDataService->retrieveAllQuestions($context->getId(), true);
        $this->setData('questions', $questions);
        $this->setData('questionTypeConsts', DemographicQuestion::getQuestionTypeConstants());

        parent::initData();
    }

    private function initConsentData($context, $applicationName)
    {
        $user = $this->request->getUser();
        $demographicDataDao = new DemographicDataDAO();

        $userConsent = $demographicDataDao->getDemographicConsentOption($context->getId(), $user->getId());
        $this->setData('demographicDataConsent', $userConsent);

        $userConsentSetting = $demographicDataDao->getConsentSetting($user->getId());
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
        $dataConsentOption = $this->getData('demographicDataConsent');

        $demographicDataDao = new DemographicDataDAO();
        $context = $this->request->getContext();
        $user = $this->request->getUser();
        $userConsentSetting = $demographicDataDao->getConsentSetting($user->getId());
        $previousConsentOption = $demographicDataDao->getDemographicConsentOption($context->getId(), $user->getId());

        if (!is_null($userConsentSetting) && is_null($previousConsentOption)) {
            return false;
        }


        if ($dataConsentOption) {
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
        $previousConsent = $demographicDataDao->getDemographicConsentOption($context->getId(), $user->getId());
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\deiaSurvey\classes\form\QuestionsForm', '\QuestionsForm');
}
