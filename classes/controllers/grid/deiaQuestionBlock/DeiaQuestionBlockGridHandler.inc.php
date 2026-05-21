<?php

use PKP\controllers\grid\GridHandler;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridCellProvider;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockExportFeature;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridRow;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form\DeiaQuestionBlockForm;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonImporter;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonSerializer;

class DeiaQuestionBlockGridHandler extends \GridHandler
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
                'createDeiaQuestionBlock',
                'editDeiaQuestionBlock',
                'updateDeiaQuestionBlock',
                'deiaQuestionBlockBasics',
                'activateDeiaQuestionBlock',
                'deactivateDeiaQuestionBlock',
                'deleteDeiaQuestionBlock',
                'deiaQuestionBlockElements',
                'exportSelectedQuestionBlocks',
                'importQuestionBlocks',
                'uploadQuestionBlocksFile',
                'uploadQuestionBlocks',
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
                'importQuestionBlocks',
                new AjaxModal(
                    $router->url($request, null, null, 'importQuestionBlocks'),
                    __('plugins.generic.deiaSurvey.questionBlocks.import'),
                    'modal_add_item',
                    true
                ),
                __('plugins.generic.deiaSurvey.questionBlocks.import'),
                'add_item'
            )
        );
        $this->addAction(
            new LinkAction(
                'createDeiaQuestionBlock',
                new AjaxModal(
                    $router->url(
                        $request,
                        null,
                        null,
                        'createDeiaQuestionBlock',
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

        $deiaQuestionBlockGridCellProvider = new DeiaQuestionBlockGridCellProvider();

        $this->addColumn(
            new GridColumn(
                'name',
                'plugins.generic.deiaSurvey.questionBlocks.title',
                null,
                null,
                $deiaQuestionBlockGridCellProvider
            )
        );

        $this->addColumn(
            new GridColumn(
                'active',
                'plugins.generic.deiaSurvey.questionBlocks.active',
                null,
                'controllers/grid/common/cell/selectStatusCell.tpl',
                $deiaQuestionBlockGridCellProvider
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
        return new DeiaQuestionBlockGridRow();
    }

    protected function loadData($request, $filter = null)
    {
        $deiaQuestionBlocks = Repo::deiaQuestionBlock()->getCollector()
            ->filterByContextIds([$request->getContext()->getId()])
            ->getMany();

        return $deiaQuestionBlocks->toArray();
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        Repo::deiaQuestionBlock()->edit($gridDataElement, ['sequence' => $newSequence]);
    }

    public function getDataElementSequence($gridDataElement)
    {
        return $gridDataElement->getSequence();
    }

    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
        return array(new \OrderGridItemsFeature(), new DeiaQuestionBlockExportFeature());
    }

    public function createDeiaQuestionBlock($args, $request)
    {
        $deiaQuestionBlockForm = new DeiaQuestionBlockForm($this->plugin);
        $deiaQuestionBlockForm->initData();
        return new JSONMessage(true, $deiaQuestionBlockForm->fetch($request));
    }

    public function editDeiaQuestionBlock($args, $request)
    {
        $context = $request->getContext();
        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get(
            (int) $request->getUserVar('rowId'),
            $context->getId()
        );

        $templateMgr = \TemplateManager::getManager($request);
        $templateMgr->assign([
            'deiaQuestionBlockId' => $deiaQuestionBlock->getId(),
            'canEdit' => true
        ]);

        return new JSONMessage(
            true,
            $templateMgr->fetch($this->plugin->getTemplateResource('deiaQuestionBlocks/editDeiaQuestionBlock.tpl'))
        );
    }

    public function deiaQuestionBlockBasics($args, $request)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');

        $deiaQuestionBlockForm = new DeiaQuestionBlockForm($this->plugin, $deiaQuestionBlockId);
        $deiaQuestionBlockForm->initData();
        return new JSONMessage(true, $deiaQuestionBlockForm->fetch($request));
    }

    public function updateDeiaQuestionBlock($args, $request)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');

        $context = $request->getContext();

        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($deiaQuestionBlockId, $context->getId());

        $deiaQuestionBlockForm = new DeiaQuestionBlockForm($this->plugin, $deiaQuestionBlockId);
        $deiaQuestionBlockForm->readInputData();

        if ($deiaQuestionBlockForm->validate()) {
            $deiaQuestionBlockForm->execute();

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }

    public function activateDeiaQuestionBlock($args, $request)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');

        $context = $request->getContext();

        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($deiaQuestionBlockId, $context->getId());

        if ($request->checkCSRF() && isset($deiaQuestionBlock) && !$deiaQuestionBlock->getActive()) {
            Repo::deiaQuestionBlock()->edit($deiaQuestionBlock, ['active' => 1]);

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }


    public function deactivateDeiaQuestionBlock($args, $request)
    {

        $deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');

        $context = $request->getContext();

        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($deiaQuestionBlockId, $context->getId());

        if ($request->checkCSRF() && isset($deiaQuestionBlock) && $deiaQuestionBlock->getActive()) {
            Repo::deiaQuestionBlock()->edit($deiaQuestionBlock, ['active' => 0]);

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }

    public function deleteDeiaQuestionBlock($args, $request)
    {
        $deiaQuestionBlockId = (int) $request->getUserVar('rowId');

        $context = $request->getContext();

        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get($deiaQuestionBlockId, $context->getId());

        if ($request->checkCSRF() && isset($deiaQuestionBlock) && !$deiaQuestionBlock->getActive()) {
            Repo::deiaQuestionBlock()->delete($deiaQuestionBlock);

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return DAO::getDataChangedEvent($deiaQuestionBlockId);
        }

        return new JSONMessage(false);
    }

    public function exportSelectedQuestionBlocks($args, $request): void
    {
        if (!$request->checkCSRF()) {
            exit;
        }

        $context = $request->getContext();
        $selectedBlockIds = array_map('intval', (array) $request->getUserVar('selectedDeiaQuestionBlocks'));
        $blocks = $this->getBlocksForExport($selectedBlockIds, $context->getId());

        if (empty($blocks)) {
            exit;
        }

        $serializer = new DeiaQuestionBlockJsonSerializer();
        $json = json_encode($serializer->serializeBlocks($blocks), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        header('content-type: application/json');
        header('content-disposition: attachment; filename=deia-question-blocks-' . date('Ymd') . '.json');
        echo $json;
        exit;
    }

    public function importQuestionBlocks($args, $request)
    {
        $templateMgr = \TemplateManager::getManager($request);
        $templateMgr->assign('baseUrl', $request->getBaseUrl());

        return new JSONMessage(
            true,
            $templateMgr->fetch($this->plugin->getTemplateResource('deiaQuestionBlocks/importQuestionBlocks.tpl'))
        );
    }

    public function uploadQuestionBlocksFile($args, $request)
    {
        import('lib.pkp.classes.file.TemporaryFileManager');
        $temporaryFileManager = new \TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $request->getUser()->getId());

        if (!$temporaryFile) {
            return new JSONMessage(false, __('plugins.generic.deiaSurvey.questionBlocks.import.uploadError'));
        }

        $json = new JSONMessage(true);
        $json->setAdditionalAttributes([
            'temporaryFileId' => $temporaryFile->getId()
        ]);

        return $json;
    }

    public function uploadQuestionBlocks($args, $request)
    {
        if (!$request->checkCSRF() || !$request->getUserVar('temporaryFileId')) {
            return new JSONMessage(false);
        }

        $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
        $temporaryFile = $temporaryFileDao->getTemporaryFile(
            $request->getUserVar('temporaryFileId'),
            $request->getUser()->getId()
        );

        if (!$temporaryFile) {
            return new JSONMessage(false, __('plugins.generic.deiaSurvey.questionBlocks.import.uploadError'));
        }

        import('lib.pkp.classes.file.TemporaryFileManager');
        $temporaryFileManager = new \TemporaryFileManager();

        try {
            $json = file_get_contents($temporaryFile->getFilePath());
            (new DeiaQuestionBlockJsonImporter())->import($json, $request->getContext()->getId());
        } catch (\InvalidArgumentException $exception) {
            return new JSONMessage(false, $exception->getMessage());
        } finally {
            $temporaryFileManager->deleteById($temporaryFile->getId(), $request->getUser()->getId());
        }

        $notificationMgr = new NotificationManager();
        $user = $request->getUser();
        $notificationMgr->createTrivialNotification($user->getId());

        return DAO::getDataChangedEvent();
    }

    private function getBlocksForExport(array $blockIds, int $contextId): array
    {
        $blocks = [];

        foreach ($blockIds as $blockId) {
            $block = Repo::deiaQuestionBlock()->get($blockId, $contextId);
            if (!$block) {
                continue;
            }

            $questions = Repo::deiaQuestion()->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByQuestionBlockIds([$block->getId()])
                ->getMany()
                ->toArray();

            foreach ($questions as $question) {
                $question->setData('responseOptions', Repo::deiaResponseOption()->getCollector()
                    ->filterByQuestionIds([$question->getId()])
                    ->getMany()
                    ->toArray());
            }

            $block->setData('questions', $questions);
            $blocks[] = $block;
        }

        return $blocks;
    }

    public function deiaQuestionBlockElements($args, $request)
    {
        $templateMgr = \TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();

        return $templateMgr->fetchAjax(
            'deiaQuestionGridContainer',
            $dispatcher->url(
                $request,
                ROUTE_COMPONENT,
                null,
                'plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestion.DeiaQuestionGridHandler',
                'fetchGrid',
                null,
                ['deiaQuestionBlockId' => (int) $request->getUserVar('deiaQuestionBlockId')]
            )
        );
    }
}
