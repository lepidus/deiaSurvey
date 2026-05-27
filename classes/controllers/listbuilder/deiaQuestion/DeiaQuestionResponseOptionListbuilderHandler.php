<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\listbuilder\deiaQuestion;

use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\template\TemplateManager;
use PKP\controllers\listbuilder\ListbuilderGridColumn;
use PKP\controllers\listbuilder\ListbuilderHandler;
use PKP\controllers\listbuilder\MultilingualListbuilderGridColumn;
use PKP\controllers\listbuilder\settings\SetupListbuilderHandler;
use PKP\facades\Locale;
use PKP\plugins\PluginRegistry;

class DeiaQuestionResponseOptionListbuilderHandler extends SetupListbuilderHandler
{
    public int $deiaQuestionId;
    public $plugin;

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $this->plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $this->deiaQuestionId = (int) $request->getUserVar('deiaQuestionId');

        $this->setTitle('plugins.generic.deiaSurvey.questionBlocks.questions.options');
        $this->setSourceType(ListbuilderHandler::LISTBUILDER_SOURCE_TYPE_TEXT);
        $this->setSaveType(ListbuilderHandler::LISTBUILDER_SAVE_TYPE_EXTERNAL);
        $this->setSaveFieldName('responseOptions');

        $cellProvider = new DeiaQuestionResponseOptionListbuilderGridCellProvider();
        $responseColumn = new MultilingualListbuilderGridColumn(
            $this,
            'responseOption',
            'plugins.generic.deiaSurvey.questionBlocks.questions.optionText',
            null,
            null,
            null,
            null,
            ['tabIndex' => 1]
        );
        $responseColumn->setCellProvider($cellProvider);
        $this->addColumn($responseColumn);

        $this->addColumn(new ListbuilderGridColumn(
            $this,
            'hasInputField',
            'plugins.generic.deiaSurvey.questionBlocks.questions.optionHasInputField',
            null,
            $this->plugin->getTemplateResource('deiaQuestionBlocks/responseOptionHasInputFieldCell.tpl'),
            $cellProvider,
            ['tabIndex' => 2]
        ));
    }

    protected function loadData($request, $filter = null)
    {
        $formattedResponses = [];

        if (!$this->deiaQuestionId) {
            return $formattedResponses;
        }

        $locale = Locale::getLocale();
        $responseOptions = Repo::deiaResponseOption()->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
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
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('availableOptions', true);
        return $this->fetchGrid($args, $request);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias(
        '\APP\plugins\generic\deiaSurvey\classes\controllers\listbuilder\deiaQuestion'
            . '\DeiaQuestionResponseOptionListbuilderHandler',
        '\DeiaQuestionResponseOptionListbuilderHandler'
    );
}
