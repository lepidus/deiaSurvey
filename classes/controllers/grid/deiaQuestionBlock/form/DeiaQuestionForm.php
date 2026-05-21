<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
import('lib.pkp.classes.form.Form');

class DeiaQuestionForm extends \Form
{
    public $plugin;
    public $deiaQuestionBlockId;
    public $deiaQuestionId;

    public function __construct($plugin, int $deiaQuestionBlockId, ?int $deiaQuestionId = null)
    {
        parent::__construct($plugin->getTemplateResource('deiaQuestionBlocks/deiaQuestionForm.tpl'));

        $this->plugin = $plugin;
        $this->deiaQuestionBlockId = $deiaQuestionBlockId;
        $this->deiaQuestionId = $deiaQuestionId;

        $this->addCheck(new \FormValidatorPost($this));
        $this->addCheck(new \FormValidatorCSRF($this));
        $this->addCheck(new \FormValidatorLocale(
            $this,
            'questionText',
            'required',
            'plugins.generic.deiaSurvey.questionBlocks.questions.form.questionTextRequired'
        ));
        $this->addCheck(new \FormValidator(
            $this,
            'questionType',
            'required',
            'plugins.generic.deiaSurvey.questionBlocks.questions.form.questionTypeRequired'
        ));
    }

    public function readInputData()
    {
        $this->readUserVars([
            'deiaQuestionBlockId',
            'deiaQuestionId',
            'questionText',
            'questionDescription',
            'questionType',
            'responseOptions',
        ]);
    }

    public function initData()
    {
        if (!$this->deiaQuestionId) {
            $this->setData('questionType', DeiaQuestion::TYPE_TEXT_FIELD);
            return;
        }

        $request = \Application::get()->getRequest();
        $context = $request->getContext();
        $deiaQuestion = Repo::deiaQuestion()->get($this->deiaQuestionId, $context->getId());

        $this->setData('questionText', $deiaQuestion->getData('questionText'));
        $this->setData('questionDescription', $deiaQuestion->getData('questionDescription'));
        $this->setData('questionType', $deiaQuestion->getQuestionType());

        $this->setData('responseOptions', $this->getResponseOptionsForListbuilder());
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = \TemplateManager::getManager($request);
        $responseOptionQuestionTypes = $this->getResponseOptionQuestionTypes();
        $questionType = (int) $this->getData('questionType');
        $templateMgr->assign([
            'deiaQuestionBlockId' => $this->deiaQuestionBlockId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'questionTypeOptions' => [
                DeiaQuestion::TYPE_SMALL_TEXT_FIELD => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.smallText',
                DeiaQuestion::TYPE_TEXT_FIELD => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.text',
                DeiaQuestion::TYPE_TEXTAREA => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.textarea',
                DeiaQuestion::TYPE_CHECKBOXES => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.checkboxes',
                DeiaQuestion::TYPE_RADIO_BUTTONS => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.radioButtons',
                DeiaQuestion::TYPE_DROP_DOWN_BOX => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.select',
            ],
            'responseOptionQuestionTypes' => $responseOptionQuestionTypes,
            'responseOptionQuestionTypesString' => ';' . implode(';', $responseOptionQuestionTypes) . ';',
            'questionTypeAllowsResponseOptions' => in_array($questionType, $responseOptionQuestionTypes, true),
        ]);

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $request = \Application::get()->getRequest();
        $context = $request->getContext();

        if ($this->deiaQuestionId) {
            $deiaQuestion = Repo::deiaQuestion()->get($this->deiaQuestionId, $context->getId());
        } else {
            $deiaQuestion = Repo::deiaQuestion()->newDataObject();
            $deiaQuestion->setContextId($context->getId());
            $deiaQuestion->setQuestionBlockId($this->deiaQuestionBlockId);
            $deiaQuestion->setSequence(REALLY_BIG_NUMBER);
        }

        $questionParams = [
            'contextId' => $context->getId(),
            'questionBlockId' => $this->deiaQuestionBlockId,
            'questionType' => (int) $this->getData('questionType'),
            'questionText' => $this->getData('questionText'),
            'questionDescription' => $this->getData('questionDescription'),
            'isTranslated' => true,
            'isDefaultQuestion' => false,
        ];

        if ($this->deiaQuestionId) {
            Repo::deiaQuestion()->edit($deiaQuestion, $questionParams);
        } else {
            $deiaQuestion->setAllData(array_merge($deiaQuestion->_data, $questionParams));
            $this->deiaQuestionId = Repo::deiaQuestion()->add($deiaQuestion);
            Repo::deiaQuestion()->dao->resequence($this->deiaQuestionBlockId);
        }

        if (in_array((int) $questionParams['questionType'], $this->getResponseOptionQuestionTypes())) {
            $this->setData('responseOptionsProcessed', $this->getResponseOptionsForListbuilder());
            \ListbuilderHandler::unpack(
                $request,
                $this->getData('responseOptions'),
                [$this, 'deleteEntry'],
                [$this, 'insertEntry'],
                [$this, 'updateEntry']
            );
            $this->replaceResponseOptions($this->getData('responseOptionsProcessed'));
        } else {
            $this->replaceResponseOptions([]);
        }

        parent::execute(...$functionArgs);

        return $this->deiaQuestionId;
    }

    public function insertEntry($request, $newRowId): bool
    {
        $responseOptionsProcessed = (array) $this->getData('responseOptionsProcessed');
        $responseOptionsProcessed[] = [
            'optionText' => $newRowId['responseOption'],
            'hasInputField' => !empty($newRowId['hasInputField']),
        ];
        $this->setData('responseOptionsProcessed', $responseOptionsProcessed);
        return true;
    }

    public function deleteEntry($request, $rowId): bool
    {
        $responseOptionsProcessed = (array) $this->getData('responseOptionsProcessed');
        unset($responseOptionsProcessed[$rowId - 1]);
        $this->setData('responseOptionsProcessed', $responseOptionsProcessed);
        return true;
    }

    public function updateEntry($request, $rowId, $newRowId): bool
    {
        $responseOptionsProcessed = (array) $this->getData('responseOptionsProcessed');
        $responseOptionsProcessed[$rowId - 1] = [
            'optionText' => $newRowId['responseOption'],
            'hasInputField' => !empty($newRowId['hasInputField']),
        ];
        $this->setData('responseOptionsProcessed', $responseOptionsProcessed);
        return true;
    }

    private function replaceResponseOptions(array $responseOptions): void
    {
        $responsesCount = Repo::deiaResponse()->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->getCount();

        if ($responsesCount > 0) {
            return;
        }

        $existingOptions = Repo::deiaResponseOption()->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->getMany();

        foreach ($existingOptions as $existingOption) {
            Repo::deiaResponseOption()->delete($existingOption);
        }

        $sequence = 0;
        foreach ($responseOptions as $responseOptionData) {
            if (empty(array_filter((array) $responseOptionData['optionText']))) {
                continue;
            }

            $responseOption = Repo::deiaResponseOption()->newDataObject([
                'deiaQuestionId' => $this->deiaQuestionId,
                'sequence' => ++$sequence,
                'optionText' => $responseOptionData['optionText'],
                'isTranslated' => true,
                'hasInputField' => !empty($responseOptionData['hasInputField']),
            ]);
            Repo::deiaResponseOption()->add($responseOption);
        }
    }

    public function getLocaleFieldNames()
    {
        return ['questionText', 'questionDescription'];
    }

    private function getResponseOptionQuestionTypes(): array
    {
        return [
            DeiaQuestion::TYPE_CHECKBOXES,
            DeiaQuestion::TYPE_RADIO_BUTTONS,
            DeiaQuestion::TYPE_DROP_DOWN_BOX,
        ];
    }

    private function getResponseOptionsForListbuilder(): array
    {
        if (!$this->deiaQuestionId) {
            return [];
        }

        $request = \Application::get()->getRequest();
        $locale = \AppLocale::getLocale();
        $context = $request->getContext();
        $deiaQuestion = Repo::deiaQuestion()->get($this->deiaQuestionId, $context->getId());
        $responseOptions = [];

        foreach ($deiaQuestion->getResponseOptions() as $responseOption) {
            $optionText = $responseOption->getData('optionText');
            $responseOptions[] = [
                'optionText' => is_array($optionText)
                    ? $optionText
                    : [$locale => $responseOption->getLocalizedOptionText()],
                'hasInputField' => $responseOption->hasInputField(),
            ];
        }

        return $responseOptions;
    }
}
