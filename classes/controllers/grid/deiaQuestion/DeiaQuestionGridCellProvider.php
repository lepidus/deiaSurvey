<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion;

use PKP\controllers\grid\GridCellProvider;

class DeiaQuestionGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();

        if ($column->getId() === 'question') {
            return ['label' => $element->getLocalizedQuestionText()];
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
