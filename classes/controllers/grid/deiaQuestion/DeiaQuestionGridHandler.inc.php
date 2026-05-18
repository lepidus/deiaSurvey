<?php

use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion\DeiaQuestionGridCellProvider;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion\DeiaQuestionGridRow;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form\DeiaQuestionForm;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;

import('lib.pkp.classes.controllers.grid.GridHandler');

class DeiaQuestionGridHandler extends \GridHandler
{
    public $plugin;
    public $deiaQuestionBlockId;
    public $deiaQuestionBlock;

    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [ROLE_ID_MANAGER],
            [
                'fetchGrid',
                'fetchRow',
                'saveSequence',
                'createDeiaQuestion',
                'editDeiaQuestion',
                'updateDeiaQuestion',
                'deleteDeiaQuestion'
            ]
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

        $this->deiaQuestionBlockId = (int) $request->getUserVar('deiaQuestionBlockId');
        $context = $request->getContext();
        $this->deiaQuestionBlock = Repo::deiaQuestionBlock()->get($this->deiaQuestionBlockId, $context->getId());

        if (!$this->deiaQuestionBlock) {
            return false;
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        \AppLocale::requireComponents(
            LOCALE_COMPONENT_APP_ADMIN,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_APP_COMMON,
            LOCALE_COMPONENT_PKP_MANAGER,
            LOCALE_COMPONENT_PKP_USER
        );

        $this->plugin = \PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $this->setTitle('plugins.generic.deiaSurvey.questionBlocks.deiaQuestions');
        $this->setEmptyRowText('plugins.generic.deiaSurvey.questionBlocks.questions.noneCreated');

        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        if ($this->canModifyQuestionBlock()) {
            $this->addAction(
                new \LinkAction(
                    'createDeiaQuestion',
                    new \AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'createDeiaQuestion',
                            null,
                            ['deiaQuestionBlockId' => $this->deiaQuestionBlockId]
                        ),
                        __('plugins.generic.deiaSurvey.questionBlocks.questions.create'),
                        'modal_add_item',
                        true
                    ),
                    __('plugins.generic.deiaSurvey.questionBlocks.questions.create'),
                    'add_item'
                )
            );
        }

        $cellProvider = new DeiaQuestionGridCellProvider();
        $this->addColumn(
            new \GridColumn(
                'question',
                'plugins.generic.deiaSurvey.questionBlocks.questions.text',
                null,
                null,
                $cellProvider,
                ['html' => true, 'maxLength' => 220]
            )
        );
    }

    public function initFeatures($request, $args)
    {
        if (!$this->canModifyQuestionBlock()) {
            return [];
        }

        import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
        return [new \OrderGridItemsFeature()];
    }

    protected function getRowInstance()
    {
        return new DeiaQuestionGridRow();
    }

    protected function loadData($request, $filter = null)
    {
        $context = $request->getContext();
        $questions = Repo::deiaQuestion()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByQuestionBlockIds([$this->deiaQuestionBlockId])
            ->getMany();

        return $questions->map(function ($question) {
            $question->setData('canEdit', $this->canModifyQuestion($question));
            return $question;
        })->toArray();
    }

    public function getRequestArgs()
    {
        return array_merge(
            ['deiaQuestionBlockId' => $this->deiaQuestionBlockId],
            parent::getRequestArgs()
        );
    }

    public function getDataElementSequence($gridDataElement)
    {
        return $gridDataElement->getSequence();
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence)
    {
        if (!$this->canModifyQuestionBlock()) {
            return;
        }

        Repo::deiaQuestion()->edit($gridDataElement, ['sequence' => $newSequence]);
    }

    public function createDeiaQuestion($args, $request)
    {
        if (!$this->canModifyQuestionBlock()) {
            return new \JSONMessage(false);
        }

        $form = new DeiaQuestionForm($this->plugin, $this->deiaQuestionBlockId);
        $form->initData();

        return new \JSONMessage(true, $form->fetch($request));
    }

    public function editDeiaQuestion($args, $request)
    {
        $deiaQuestionId = (int) $request->getUserVar('rowId');
        $context = $request->getContext();
        $deiaQuestion = Repo::deiaQuestion()->get($deiaQuestionId, $context->getId());

        if (!$this->canModifyQuestion($deiaQuestion)) {
            return new \JSONMessage(false);
        }

        $form = new DeiaQuestionForm($this->plugin, $this->deiaQuestionBlockId, $deiaQuestionId);
        $form->initData();

        return new \JSONMessage(true, $form->fetch($request));
    }

    public function updateDeiaQuestion($args, $request)
    {
        $deiaQuestionId = $request->getUserVar('deiaQuestionId')
            ? (int) $request->getUserVar('deiaQuestionId')
            : null;

        if ($deiaQuestionId) {
            $context = $request->getContext();
            $deiaQuestion = Repo::deiaQuestion()->get($deiaQuestionId, $context->getId());

            if (!$this->canModifyQuestion($deiaQuestion)) {
                return new \JSONMessage(false);
            }
        } elseif (!$this->canModifyQuestionBlock()) {
            return new \JSONMessage(false);
        }

        $form = new DeiaQuestionForm($this->plugin, $this->deiaQuestionBlockId, $deiaQuestionId);
        $form->readInputData();

        if ($form->validate()) {
            $deiaQuestionId = $form->execute();

            $notificationMgr = new \NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return \DAO::getDataChangedEvent($deiaQuestionId);
        }

        return new \JSONMessage(false);
    }

    public function deleteDeiaQuestion($args, $request)
    {
        $deiaQuestionId = (int) $request->getUserVar('rowId');
        $context = $request->getContext();
        $deiaQuestion = Repo::deiaQuestion()->get($deiaQuestionId, $context->getId());

        if (!$request->checkCSRF() || !$deiaQuestion || $deiaQuestion->getQuestionBlockId() !== $this->deiaQuestionBlockId) {
            return new \JSONMessage(false);
        }

        if (!$this->canModifyQuestion($deiaQuestion)) {
            return new \JSONMessage(false);
        }

        foreach ($deiaQuestion->getResponseOptions() as $responseOption) {
            Repo::deiaResponseOption()->delete($responseOption);
        }
        Repo::deiaQuestion()->delete($deiaQuestion);

        return \DAO::getDataChangedEvent($deiaQuestionId);
    }

    private function canModifyQuestionBlock(): bool
    {
        return !$this->deiaQuestionBlock->getActive();
    }

    private function canModifyQuestion($deiaQuestion): bool
    {
        if (!$deiaQuestion || $deiaQuestion->getQuestionBlockId() !== $this->deiaQuestionBlockId) {
            return false;
        }

        if (!$this->canModifyQuestionBlock()) {
            return false;
        }

        return !$this->questionHasResponses($deiaQuestion->getId());
    }

    private function questionHasResponses(int $deiaQuestionId): bool
    {
        return Repo::deiaResponse()->getCollector()
            ->filterByQuestionIds([$deiaQuestionId])
            ->getCount() > 0;
    }
}
