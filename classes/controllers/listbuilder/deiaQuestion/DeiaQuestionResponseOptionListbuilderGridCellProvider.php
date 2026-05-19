<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\listbuilder\deiaQuestion;

use PKP\controllers\grid\GridCellProvider;

class DeiaQuestionResponseOptionListbuilderGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $responseOption = $row->getData();

        switch ($column->getId()) {
            case 'responseOption':
                return ['label' => $responseOption[0]['content']];
            case 'hasInputField':
                $hasInputField = !empty($responseOption[1]['hasInputField']);
                return [
                    'label' => $hasInputField ? __('common.yes') : __('common.no'),
                    'selected' => $hasInputField,
                ];
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
