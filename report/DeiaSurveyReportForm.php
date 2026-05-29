<?php

namespace APP\plugins\generic\deiaSurvey\report;

use PKP\form\Form;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\security\Validation;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class DeiaSurveyReportForm extends Form
{
    private const FORM_TEMPLATE = 'report/deiaSurveyReportForm.tpl';

    private $plugin;
    private $contextId;

    public function __construct($plugin)
    {
        $request = Application::get()->getRequest();

        $this->plugin = $plugin;
        $this->contextId = $request->getContext()->getId();

        parent::__construct($plugin->getTemplateResource(self::FORM_TEMPLATE));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function initData()
    {
        $userIsSiteAdmin = Validation::isSiteAdmin();
        $this->setData('userIsSiteAdmin', $userIsSiteAdmin);
    }

    public function display($request = null, $template = null)
    {
        $templateManager = TemplateManager::getManager($request);
        $application = Application::get()->getName();
        $templateManager->assign([
            'application' => $application,
            'userIsSiteAdmin' => $this->getData('userIsSiteAdmin')
        ]);
        $templateManager->display($this->plugin->getTemplateResource(self::FORM_TEMPLATE));
    }
}
