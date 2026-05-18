<?php

use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

import('lib.pkp.controllers.listbuilder.settings.SetupListbuilderHandler');

class DeiaQuestionResponseOptionListbuilderHandler extends \SetupListbuilderHandler
{
    public $_deiaQuestionId;
    public $_plugin;

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        \AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
        $this->_plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $this->_deiaQuestionId = (int) $request->getUserVar('deiaQuestionId');

        $this->setTitle('plugins.generic.deiaSurvey.questionBlocks.questions.options');
        $this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT);
        $this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
        $this->setSaveFieldName('responseOptions');

        $responseColumn = new \MultilingualListbuilderGridColumn(
            $this,
            'responseOption',
            'plugins.generic.deiaSurvey.questionBlocks.questions.optionText',
            null,
            null,
            null,
            null,
            ['tabIndex' => 1]
        );
        import(
            'plugins.generic.deiaSurvey.classes.controllers.listbuilder.deiaQuestion.'
                . 'DeiaQuestionResponseOptionListbuilderGridCellProvider'
        );
        $responseColumn->setCellProvider(new \DeiaQuestionResponseOptionListbuilderGridCellProvider());
        $this->addColumn($responseColumn);

        $hasInputFieldColumn = new \ListbuilderGridColumn(
            $this,
            'hasInputField',
            'plugins.generic.deiaSurvey.questionBlocks.questions.optionHasInputField',
            null,
            $this->_plugin->getTemplateResource('deiaQuestionBlocks/responseOptionHasInputFieldCell.tpl'),
            new \DeiaQuestionResponseOptionListbuilderGridCellProvider(),
            ['tabIndex' => 2]
        );
        $this->addColumn($hasInputFieldColumn);
    }

    protected function loadData($request, $filter = null)
    {
        $formattedResponses = [];

        if (!$this->_deiaQuestionId) {
            return $formattedResponses;
        }

        $locale = \AppLocale::getLocale();
        $responseOptions = Repo::deiaResponseOption()->getCollector()
            ->filterByQuestionIds([$this->_deiaQuestionId])
            ->getMany();

        $rowId = 1;
        foreach ($responseOptions as $responseOption) {
            $optionText = $responseOption->getData('optionText');
            $formattedResponses[$rowId][0]['content'] = is_array($optionText)
                ? $optionText
                : [$locale => $responseOption->getLocalizedOptionText()];
            $formattedResponses[$rowId][1]['hasInputField'] = $responseOption->hasInputField();
            $rowId++;
        }

        return $formattedResponses;
    }

    protected function getRowDataElement($request, &$rowId)
    {
        if (!empty($rowId)) {
            return parent::getRowDataElement($request, $rowId);
        }

        $rowData = $this->getNewRowId($request);
        if ($rowData) {
            return [
                ['content' => $rowData['responseOption']],
                ['hasInputField' => !empty($rowData['hasInputField'])],
            ];
        }

        return [
            ['content' => []],
            ['hasInputField' => false],
        ];
    }

    public function fetch($args, $request)
    {
        $templateMgr = \TemplateManager::getManager($request);
        $templateMgr->assign('availableOptions', true);
        return $this->fetchGrid($args, $request);
    }
}
