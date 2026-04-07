<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\demographicForm;

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class DemographicFormGridCellProvider extends \GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();
        $columnId = $column->getId();
        switch ($columnId) {
            case 'name':
                return array('label' => $element->getLocalizedTitle());
            case 'completed':
                return array('label' => $element->getCompleteCount() ?? 0);
            case 'active':
                return array('selected' => $element->getActive());
        }
        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    public function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT)
    {
        switch ($column->getId()) {
            case 'active':
                $element = $row->getData();

                $router = $request->getRouter();
                import('lib.pkp.classes.linkAction.LinkAction');

                if ($element->getActive()) {
                    return array(new \LinkAction(
                        'deactivateDemographicForm',
                        new \RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('plugins.generic.deiaSurvey.questionBlocks.confirmDeactivate'),
                            null,
                            $router->url(
                                $request,
                                null,
                                null,
                                'deactivateDemographicForm',
                                null,
                                ['demographicFormId' => $element->getId()]
                            )
                        )
                    ));
                } else {
                    return array(new \LinkAction(
                        'activateDemographicForm',
                        new \RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('plugins.generic.deiaSurvey.questionBlocks.confirmActivate'),
                            null,
                            $router->url(
                                $request,
                                null,
                                null,
                                'activateDemographicForm',
                                null,
                                ['demographicFormId' => $element->getId()]
                            )
                        )
                    ));
                }
        }
        return parent::getCellActions($request, $row, $column, $position);
    }
}
