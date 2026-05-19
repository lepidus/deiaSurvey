<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock;

use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form\DeiaQuestionBlockForm;
use APP\plugins\generic\deiaSurvey\classes\DefaultQuestionsCreator;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use PKP\controllers\grid\feature\OrderGridItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\Role;

class DeiaQuestionBlockGridHandler extends GridHandler
{
    public $plugin;

    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            [
                'fetchGrid',
                'fetchRow',
                'createDeiaQuestionBlock',
                'editDeiaQuestionBlock',
                'updateDeiaQuestionBlock',
                'activateDeiaQuestionBlock',
                'deactivateDeiaQuestionBlock',
                'deleteDeiaQuestionBlock',
                'saveSequence',
            ]
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);

        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $this->plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');

        $this->setTitle('plugins.generic.deiaSurvey.questionBlocks');
        $this->setEmptyRowText('plugins.generic.deiaSurvey.questionBlocks.noneCreated');

        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'createDeiaQuestionBlock',
                new AjaxModal(
                    $router->url($request, null, null, 'createDeiaQuestionBlock'),
                    __('plugins.generic.deiaSurvey.questionBlocks.create'),
                    'modal_add_item',
                    true
                ),
                __('plugins.generic.deiaSurvey.questionBlocks.create'),
                'add_item'
            )
        );

        $cellProvider = new DeiaQuestionBlockGridCellProvider();
        $this->addColumn(
            new GridColumn(
                'name',
                'plugins.generic.deiaSurvey.questionBlocks.title',
                null,
                null,
                $cellProvider
            )
        );
        $this->addColumn(
            new GridColumn(
                'active',
                'plugins.generic.deiaSurvey.questionBlocks.active',
                null,
                'controllers/grid/common/cell/selectStatusCell.tpl',
                $cellProvider
            )
        );
    }

    protected function getRowInstance()
    {
        return new DeiaQuestionBlockGridRow();
    }

    protected function loadData($request, $filter = null)
    {
        $defaultQuestionsCreator = new DefaultQuestionsCreator();
        $defaultQuestionsCreator->ensureDefaultQuestionBlock($request->getContext()->getId());

        return Repo::deiaQuestionBlock()->getCollector()
            ->filterByContextIds([$request->getContext()->getId()])
            ->getMany()
            ->toArray();
    }

    public function initFeatures($request, $args)
    {
        return [new OrderGridItemsFeature()];
    }

    public function getDataElementSequence($gridDataElement)
    {
        return $gridDataElement->getSequence();
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        Repo::deiaQuestionBlock()->edit($gridDataElement, ['sequence' => $newSequence]);
    }

    public function createDeiaQuestionBlock($args, $request)
    {
        $form = new DeiaQuestionBlockForm($this->plugin);
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function editDeiaQuestionBlock($args, $request)
    {
        $form = new DeiaQuestionBlockForm($this->plugin, (int) $request->getUserVar('rowId'));
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function updateDeiaQuestionBlock($args, $request)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');
        $context = $request->getContext();

        if ($deiaQuestionBlockId && !Repo::deiaQuestionBlock()->exists($deiaQuestionBlockId, $context->getId())) {
            return new JSONMessage(false);
        }

        $form = new DeiaQuestionBlockForm($this->plugin, $deiaQuestionBlockId);
        $form->readInputData();

        if ($form->validate()) {
            $deiaQuestionBlockId = $form->execute();

            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification($request->getUser()->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }

    public function activateDeiaQuestionBlock($args, $request)
    {
        return $this->setDeiaQuestionBlockActive($request, true);
    }

    public function deactivateDeiaQuestionBlock($args, $request)
    {
        return $this->setDeiaQuestionBlockActive($request, false);
    }

    private function setDeiaQuestionBlockActive($request, bool $active)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');
        $context = $request->getContext();
        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($deiaQuestionBlockId, $context->getId());

        if ($request->checkCSRF() && $deiaQuestionBlock && (bool) $deiaQuestionBlock->getActive() !== $active) {
            Repo::deiaQuestionBlock()->edit($deiaQuestionBlock, ['active' => $active ? 1 : 0]);

            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification($request->getUser()->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }

    public function deleteDeiaQuestionBlock($args, $request)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('rowId');
        $context = $request->getContext();
        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($deiaQuestionBlockId, $context->getId());

        if ($request->checkCSRF() && $deiaQuestionBlock && !$deiaQuestionBlock->getActive()) {
            Repo::deiaQuestionBlock()->delete($deiaQuestionBlock);

            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification($request->getUser()->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias(
        '\APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridHandler',
        '\DeiaQuestionBlockGridHandler'
    );
}
