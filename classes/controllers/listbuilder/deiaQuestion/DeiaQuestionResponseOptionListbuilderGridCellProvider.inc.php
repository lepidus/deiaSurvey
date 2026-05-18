<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class DeiaQuestionResponseOptionListbuilderGridCellProvider extends \GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        switch ($column->getId()) {
            case 'responseOption':
                $responseOption = $row->getData();
                $contentColumn = $responseOption[0];
                return ['label' => $contentColumn['content']];
            case 'hasInputField':
                $responseOption = $row->getData();
                $hasInputField = !empty($responseOption[1]['hasInputField']);
                return [
                    'label' => $hasInputField ? __('common.yes') : __('common.no'),
                    'selected' => $hasInputField,
                ];
        }

        assert(false);
    }
}
