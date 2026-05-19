<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion;

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class DeiaQuestionGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $element = $this->getData();
        $rowId = $this->getId();

        if (empty($rowId) || !is_numeric($rowId) || !$element->getData('canEdit')) {
            return;
        }

        $router = $request->getRouter();
        $deiaQuestionBlockId = $element->getQuestionBlockId();

        $this->addAction(
            new LinkAction(
                'edit',
                new AjaxModal(
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
            new LinkAction(
                'delete',
                new RemoteActionConfirmationModal(
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
