<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock;

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class DeiaQuestionBlockGridRow extends \GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $element = $this->getData();

        $rowId = $this->getId();

        if (!empty($rowId) && is_numeric($rowId)) {
            $router = $request->getRouter();

            $this->addAction(
                new \LinkAction(
                    'edit',
                    new \AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'editDeiaQuestionBlock',
                            null,
                            ['rowId' => $rowId]
                        ),
                        __('grid.action.edit'),
                        'modal_edit',
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            if (!$element->getActive()) {
                $this->addAction(
                    new \LinkAction(
                        'delete',
                        new \RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('plugins.generic.deiaSurvey.questionBlocks.confirmDelete'),
                            null,
                            $router->url(
                                $request,
                                null,
                                null,
                                'deleteDeiaQuestionBlock',
                                null,
                                ['rowId' => $rowId]
                            )
                        ),
                        __('grid.action.delete'),
                        'delete'
                    )
                );
            }
        }
    }
}
