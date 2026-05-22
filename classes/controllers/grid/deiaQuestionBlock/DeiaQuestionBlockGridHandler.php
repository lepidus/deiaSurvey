<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock;

use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form\DeiaQuestionBlockForm;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonImporter;
use APP\plugins\generic\deiaSurvey\classes\importExport\DeiaQuestionBlockJsonSerializer;
use APP\template\TemplateManager;
use PKP\controllers\grid\feature\OrderGridItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\file\TemporaryFileDAO;
use PKP\file\TemporaryFileManager;
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
                'deiaQuestionBlockBasics',
                'deiaQuestionBlockElements',
                'activateDeiaQuestionBlock',
                'deactivateDeiaQuestionBlock',
                'deleteDeiaQuestionBlock',
                'exportSelectedQuestionBlocks',
                'importQuestionBlocks',
                'uploadQuestionBlocksFile',
                'uploadQuestionBlocks',
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
        return Repo::deiaQuestionBlock()->getCollector()
            ->filterByContextIds([$request->getContext()->getId()])
            ->getMany()
            ->toArray();
    }

    public function initFeatures($request, $args)
    {
        import('plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockExportFeature');
        return [new OrderGridItemsFeature(), new DeiaQuestionBlockExportFeature()];
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
        $context = $request->getContext();
        $deiaQuestionBlock = Repo::deiaQuestionBlock()->get((int) $request->getUserVar('rowId'), $context->getId());

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'deiaQuestionBlockId' => $deiaQuestionBlock->getId(),
            'canEdit' => true,
        ]);

        return new JSONMessage(
            true,
            $templateMgr->fetch($this->plugin->getTemplateResource('deiaQuestionBlocks/editDeiaQuestionBlock.tpl'))
        );
    }

    public function deiaQuestionBlockBasics($args, $request)
    {
        $form = new DeiaQuestionBlockForm($this->plugin, (int) $request->getUserVar('deiaQuestionBlockId'));
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    public function deiaQuestionBlockElements($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
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
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('baseUrl', $request->getBaseUrl());

        return new JSONMessage(
            true,
            $templateMgr->fetch($this->plugin->getTemplateResource('deiaQuestionBlocks/importQuestionBlocks.tpl'))
        );
    }

    public function uploadQuestionBlocksFile($args, $request)
    {
        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $request->getUser()->getId());

        if (!$temporaryFile) {
            return new JSONMessage(false, __('plugins.generic.deiaSurvey.questionBlocks.import.uploadError'));
        }

        $json = new JSONMessage(true);
        $json->setAdditionalAttributes([
            'temporaryFileId' => $temporaryFile->getId(),
        ]);

        return $json;
    }

    public function uploadQuestionBlocks($args, $request)
    {
        if (!$request->checkCSRF() || !$request->getUserVar('temporaryFileId')) {
            return new JSONMessage(false);
        }

        $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /** @var TemporaryFileDAO $temporaryFileDao */
        $temporaryFile = $temporaryFileDao->getTemporaryFile(
            $request->getUserVar('temporaryFileId'),
            $request->getUser()->getId()
        );

        if (!$temporaryFile) {
            return new JSONMessage(false, __('plugins.generic.deiaSurvey.questionBlocks.import.uploadError'));
        }

        $temporaryFileManager = new TemporaryFileManager();

        try {
            $json = file_get_contents($temporaryFile->getFilePath());
            (new DeiaQuestionBlockJsonImporter())->import($json, $request->getContext()->getId());
        } catch (\InvalidArgumentException $exception) {
            return new JSONMessage(false, $exception->getMessage());
        } finally {
            $temporaryFileManager->deleteById($temporaryFile->getId(), $request->getUser()->getId());
        }

        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification($request->getUser()->getId());

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
}

if (!PKP_STRICT_MODE) {
    class_alias(
        '\APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\DeiaQuestionBlockGridHandler',
        '\DeiaQuestionBlockGridHandler'
    );
}
