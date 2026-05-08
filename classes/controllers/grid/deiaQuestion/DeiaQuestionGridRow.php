<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion;

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class DeiaQuestionGridRow extends \GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $element = $this->getData();
        $rowId = $this->getId();

        if (!empty($rowId) && is_numeric($rowId)) {
            $router = $request->getRouter();
            $deiaQuestionBlockId = $element->getQuestionBlockId();

            if ($element->getData('canEdit')) {
                $this->addAction(
                    new \LinkAction(
                        'edit',
                        new \AjaxModal(
                            $router->url(
                                $request,
                                null,
                                null,
                                'editDeiaQuestion',
                                null,
                                ['rowId' => $rowId, 'deiaQuestionBlockId' => $deiaQuestionBlockId]
                            ),
                            __('grid.action.edit'),
                            'modal_edit',
                            true
                        ),
                        __('grid.action.edit'),
                        'edit'
                    )
                );

                $this->addAction(
                    new \LinkAction(
                        'delete',
                        new \RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('plugins.generic.deiaSurvey.questionBlocks.questions.confirmDelete'),
                            null,
                            $router->url(
                                $request,
                                null,
                                null,
                                'deleteDeiaQuestion',
                                null,
                                ['rowId' => $rowId, 'deiaQuestionBlockId' => $deiaQuestionBlockId]
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
