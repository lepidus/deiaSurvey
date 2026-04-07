<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\demographicForm\form;

use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

import('lib.pkp.classes.form.Form');

class DemographicFormForm extends \Form
{
    public $plugin;

    public $demographicFormId;

    public function __construct($plugin, $demographicFormId = null)
    {
        parent::__construct($plugin->getTemplateResource('demographicForms/demographicFormForm.tpl'));
        $this->demographicFormId = $demographicFormId ? (int) $demographicFormId : null;

        $this->addCheck(new \FormValidatorPost($this));
        $this->addCheck(new \FormValidatorCSRF($this));
        $this->addCheck(new \FormValidatorLocale(
            $this,
            'title',
            'required',
            'plugins.generic.deiaSurvey.questionBlocks.form.titleRequired'
        ));
    }

    public function readInputData()
    {
        $this->readUserVars(['title', 'description']);
    }

    public function initData()
    {
        if ($this->demographicFormId) {
            $request = \Application::get()->getRequest();
            $context = $request->getContext();
            $demographicForm = Repo::demographicForm()->get($this->demographicFormId, $context->getId());

            $this->setData('title', $demographicForm->getData('title'));
            $this->setData('description', $demographicForm->getData('description'));
        }
    }

    public function fetch($request, $template = null, $display = false)
    {
        $json = new \JSONMessage();

        $templateMgr = \TemplateManager::getManager($request);
        $templateMgr->assign('demographicFormId', $this->demographicFormId);

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $request = \Application::get()->getRequest();
        $context = $request->getContext();

        if ($this->demographicFormId) {
            $demographicForm = Repo::demographicForm()->get($this->demographicFormId, $context->getId());
        } else {
            $demographicForm = Repo::demographicForm()->newDataObject();
            $demographicForm->setContextId($context->getId());
            $demographicForm->setActive(0);
            $demographicForm->setSequence(REALLY_BIG_NUMBER);
        }

        $demographicForm->setData('title', $this->getData('title'));
        $demographicForm->setData('description', $this->getData('description'));

        if ($this->demographicFormId) {
            Repo::demographicForm()->edit($demographicForm);
            $this->demographicFormId = $demographicForm->getId();
        } else {
            $this->demographicFormId = Repo::demographicForm()->add($demographicForm);
            Repo::demographicForm()->dao->resequence($context->getId());
        }
        parent::execute(...$functionArgs);
    }

    public function getLocaleFieldNames()
    {
        return ['title', 'description'];
    }
}
