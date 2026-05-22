<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock;

use APP\template\TemplateManager;
use PKP\controllers\grid\feature\GridFeature;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\NullAction;

class DeiaQuestionBlockExportFeature extends GridFeature
{
    public function __construct()
    {
        parent::__construct('deiaQuestionBlockExport');
    }

    public function getJSClass()
    {
        return '$.pkp.classes.features.DeiaQuestionBlockExportFeature';
    }

    public function setOptions($request, $grid)
    {
        parent::setOptions($request, $grid);

        $router = $request->getRouter();
        $this->addOptions([
            'exportUrl' => $router->url($request, null, null, 'exportSelectedQuestionBlocks'),
            'csrfToken' => $request->getSession()->getCsrfToken(),
            'selectName' => 'selectedDeiaQuestionBlocks',
            'noSelectionMessage' => __('plugins.generic.deiaSurvey.questionBlocks.exportSelected.noneSelected'),
        ]);
    }

    public function fetchUIElements($request, $grid)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('gridId', $grid->getId());

        return [
            'exportFinishControls' => $templateMgr->fetch(
                $grid->plugin->getTemplateResource('deiaQuestionBlocks/exportFinishControls.tpl')
            ),
        ];
    }

    public function gridInitialize($args)
    {
        $grid = $args['grid'];

        $grid->addAction(
            new LinkAction(
                'exportQuestionBlocks',
                new NullAction(),
                __('plugins.generic.deiaSurvey.questionBlocks.export'),
                'download'
            )
        );
    }
}
