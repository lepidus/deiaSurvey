<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorLocale;
use PKP\form\validation\FormValidatorPost;

class DeiaQuestionBlockForm extends Form
{
    public $plugin;
    public ?int $deiaQuestionBlockId;

    public function __construct($plugin, $deiaQuestionBlockId = null)
    {
        parent::__construct($plugin->getTemplateResource('deiaQuestionBlocks/deiaQuestionBlockForm.tpl'));

        $this->plugin = $plugin;
        $this->deiaQuestionBlockId = $deiaQuestionBlockId ? (int) $deiaQuestionBlockId : null;

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        $this->addCheck(new FormValidatorLocale(
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
        if (!$this->deiaQuestionBlockId) {
            return;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($this->deiaQuestionBlockId, $context->getId());

        $this->setData('title', $deiaQuestionBlock->getData('title'));
        $this->setData('description', $deiaQuestionBlock->getData('description'));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('deiaQuestionBlockId', $this->deiaQuestionBlockId);

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if ($this->deiaQuestionBlockId) {
            $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($this->deiaQuestionBlockId, $context->getId());
        } else {
            $deiaQuestionBlock = Repo::deiaQuestionBlock()->newDataObject();
            $deiaQuestionBlock->setContextId($context->getId());
            $deiaQuestionBlock->setActive(0);
            $deiaQuestionBlock->setSequence(REALLY_BIG_NUMBER);
        }

        $deiaQuestionBlock->setData('title', $this->getData('title'));
        $deiaQuestionBlock->setData('description', $this->getData('description'));

        if ($this->deiaQuestionBlockId) {
            Repo::deiaQuestionBlock()->edit($deiaQuestionBlock, [
                'title' => $this->getData('title'),
                'description' => $this->getData('description'),
            ]);
        } else {
            $this->deiaQuestionBlockId = Repo::deiaQuestionBlock()->add($deiaQuestionBlock);
            Repo::deiaQuestionBlock()->dao->resequence($context->getId());
        }

        parent::execute(...$functionArgs);
        return $this->deiaQuestionBlockId;
    }

    public function getLocaleFieldNames(): array
    {
        return ['title', 'description'];
    }
}
