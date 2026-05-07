<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion;

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class DeiaQuestionGridCellProvider extends \GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();
        $columnId = $column->getId();

        switch ($columnId) {
            case 'question':
                return ['label' => $element->getLocalizedQuestionText()];
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
