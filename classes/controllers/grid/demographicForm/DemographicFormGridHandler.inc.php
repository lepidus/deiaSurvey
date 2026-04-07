<?php

use PKP\controllers\grid\GridHandler;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\demographicForm\DemographicFormGridCellProvider;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\demographicForm\DemographicFormGridRow;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\demographicForm\form\DemographicFormForm;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

class DemographicFormGridHandler extends \GridHandler
{
    public $plugin;

    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [ROLE_ID_MANAGER],
            [
                'fetchGrid',
                'fetchRow',
                'createDemographicForm',
                'editDemographicForm',
                'updateDemographicForm',
                'demographicFormBasics',
                'activateDemographicForm',
                'deactivateDemographicForm',
                'deleteDemographicForm',
                'saveSequence'
            ]
        );
    }

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        AppLocale::requireComponents(
            LOCALE_COMPONENT_APP_ADMIN,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_PKP_USER,
            LOCALE_COMPONENT_PKP_MANAGER
        );

        $plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $this->plugin = $plugin;

        $this->setTitle('plugins.generic.deiaSurvey.questionBlocks');
        $this->setEmptyRowText('plugins.generic.deiaSurvey.questionBlocks.noneCreated');

        $router = $request->getRouter();

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $this->addAction(
            new LinkAction(
                'createDemographicForm',
                new AjaxModal(
                    $router->url(
                        $request,
                        null,
                        null,
                        'createDemographicForm',
                        null,
                        null
                    ),
                    __('plugins.generic.deiaSurvey.questionBlocks.create'),
                    'modal_add_item',
                    true
                ),
                __('plugins.generic.deiaSurvey.questionBlocks.create'),
                'add_item'
            )
        );

        $demographicFormGridCellProvider = new DemographicFormGridCellProvider();

        $this->addColumn(
            new GridColumn(
                'name',
                'plugins.generic.deiaSurvey.questionBlocks.title',
                null,
                null,
                $demographicFormGridCellProvider
            )
        );

        $this->addColumn(
            new GridColumn(
                'completed',
                'plugins.generic.deiaSurvey.questionBlocks.completed',
                null,
                null,
                $demographicFormGridCellProvider
            )
        );

        $this->addColumn(
            new GridColumn(
                'active',
                'plugins.generic.deiaSurvey.questionBlocks.active',
                null,
                'controllers/grid/common/cell/selectStatusCell.tpl',
                $demographicFormGridCellProvider
            )
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PolicySet');
        $rolePolicy = new \PolicySet(COMBINING_PERMIT_OVERRIDES);

        import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new \RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    protected function getRowInstance()
    {
        return new DemographicFormGridRow();
    }

    protected function loadData($request, $filter = null)
    {
        $demographicForms = Repo::demographicForm()->getCollector()
            ->filterByContextIds([$request->getContext()->getId()])
            ->getMany();

        return $demographicForms->toArray();
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        Repo::demographicForm()->edit($gridDataElement, ['sequence' => $newSequence]);
    }

    public function getDataElementSequence($gridDataElement)
    {
        return $gridDataElement->getSequence();
    }

    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
        return array(new \OrderGridItemsFeature());
    }

    public function createDemographicForm($args, $request)
    {
        $demographicFormForm = new DemographicFormForm($this->plugin);
        $demographicFormForm->initData();
        return new JSONMessage(true, $demographicFormForm->fetch($request));
    }

    public function editDemographicForm($args, $request)
    {
        $context = $request->getContext();
        $demographicForm = Repo::demographicForm()->get(
            (int) $request->getUserVar('rowId'),
            $context->getId()
        );

        $templateMgr = \TemplateManager::getManager($request);
        $templateMgr->assign([
            'demographicFormId' => $demographicForm->getId(),
            'canEdit' => $demographicForm->getCompleteCount() == 0
        ]);

        return new JSONMessage(
            true,
            $templateMgr->fetch($this->plugin->getTemplateResource('demographicForms/editDemographicForm.tpl'))
        );
    }

    public function demographicFormBasics($args, $request)
    {
        $demographicFormId = (int) $request->getUserVar('demographicFormId');

        $demographicFormForm = new DemographicFormForm($this->plugin, $demographicFormId);
        $demographicFormForm->initData();
        return new JSONMessage(true, $demographicFormForm->fetch($request));
    }

    public function updateDemographicForm($args, $request)
    {
        $demographicFormId = (int) $request->getUserVar('demographicFormId');

        $context = $request->getContext();

        $demographicForm = Repo::demographicForm()->get($demographicFormId, $context->getId());

        $demographicFormForm = new DemographicFormForm($this->plugin, $demographicFormId);
        $demographicFormForm->readInputData();

        if ($demographicFormForm->validate()) {
            $demographicFormForm->execute();

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($demographicFormId);
        }

        return new JSONMessage(false);
    }

    public function activateDemographicForm($args, $request)
    {
        $demographicFormId = (int) $request->getUserVar('demographicFormId');

        $context = $request->getContext();

        $demographicForm = Repo::demographicForm()->get($demographicFormId, $context->getId());

        if ($request->checkCSRF() && isset($demographicForm) && !$demographicForm->getActive()) {
            Repo::demographicForm()->edit($demographicForm, ['active' => 1]);

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($demographicFormId);
        }

        return new JSONMessage(false);
    }


    public function deactivateDemographicForm($args, $request)
    {

        $demographicFormId = (int) $request->getUserVar('demographicFormId');

        $context = $request->getContext();

        $demographicForm = Repo::demographicForm()->get($demographicFormId, $context->getId());

        if ($request->checkCSRF() && isset($demographicForm) && $demographicForm->getActive()) {
            Repo::demographicForm()->edit($demographicForm, ['active' => 0]);

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($demographicFormId);
        }

        return new JSONMessage(false);
    }

    public function deleteDemographicForm($args, $request)
    {
        $demographicFormId = (int) $request->getUserVar('rowId');

        $context = $request->getContext();

        $demographicForm = Repo::demographicForm()->get($demographicFormId, $context->getId());

        if ($request->checkCSRF() && isset($demographicForm) && $demographicForm->getCompleteCount() == 0) {
            Repo::demographicForm()->delete($demographicForm);

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($demographicFormId);
        }

        return new JSONMessage(false);
    }
}
