<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock;

use PKP\controllers\grid\GridCellProvider;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class DeiaQuestionBlockGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();

        switch ($column->getId()) {
            case 'name':
                return ['label' => $element->getLocalizedTitle()];
            case 'active':
                return ['selected' => $element->getActive()];
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    public function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT)
    {
        if ($column->getId() !== 'active') {
            return parent::getCellActions($request, $row, $column, $position);
        }

        $element = $row->getData();
        $router = $request->getRouter();

        if ($element->getActive()) {
            return [
                new LinkAction(
                    'deactivateDeiaQuestionBlock',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('plugins.generic.deiaSurvey.questionBlocks.confirmDeactivate'),
                        null,
                        $router->url(
                            $request,
                            null,
                            null,
                            'deactivateDeiaQuestionBlock',
                            null,
                            ['deiaQuestionBlockId' => $element->getId()]
                        )
                    )
                )
            ];
        }

        return [
            new LinkAction(
                'activateDeiaQuestionBlock',
                new RemoteActionConfirmationModal(
                    $request->getSession(),
                    __('plugins.generic.deiaSurvey.questionBlocks.confirmActivate'),
                    null,
                    $router->url(
                        $request,
                        null,
                        null,
                        'activateDeiaQuestionBlock',
                        null,
                        ['deiaQuestionBlockId' => $element->getId()]
                    )
                )
            )
        ];
    }
}
