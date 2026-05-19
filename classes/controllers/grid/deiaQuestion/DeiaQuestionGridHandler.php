<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion;

use APP\notification\NotificationManager;
use APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form\DeiaQuestionForm;
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

class DeiaQuestionGridHandler extends GridHandler
{
    public $plugin;
    public int $deiaQuestionBlockId;
    public $deiaQuestionBlock;

    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            [
                'fetchGrid',
                'fetchRow',
                'saveSequence',
                'createDeiaQuestion',
                'editDeiaQuestion',
                'updateDeiaQuestion',
                'deleteDeiaQuestion',
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

        $this->plugin = PluginRegistry::getPlugin('generic', 'deiasurveyplugin');
        $this->setTitle('plugins.generic.deiaSurvey.questionBlocks.deiaQuestions');
        $this->setEmptyRowText('plugins.generic.deiaSurvey.questionBlocks.questions.noneCreated');

        if ($this->canModifyQuestionBlock()) {
            $router = $request->getRouter();
            $this->addAction(
                new LinkAction(
                    'createDeiaQuestion',
                    new AjaxModal(
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
            new GridColumn(
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

        return [new OrderGridItemsFeature()];
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
        if ($this->canModifyQuestionBlock()) {
            Repo::deiaQuestion()->edit($gridDataElement, ['sequence' => $newSequence]);
        }
    }

    public function createDeiaQuestion($args, $request)
    {
        if (!$this->canModifyQuestionBlock()) {
            return new JSONMessage(false);
        }

        $form = new DeiaQuestionForm($this->plugin, $this->deiaQuestionBlockId);
        $form->initData();

        return new JSONMessage(true, $form->fetch($request));
    }

    public function editDeiaQuestion($args, $request)
    {
        $deiaQuestion = $this->getRequestQuestion($request, (int) $request->getUserVar('rowId'));

        if (!$this->canModifyQuestion($deiaQuestion)) {
            return new JSONMessage(false);
        }

        $form = new DeiaQuestionForm($this->plugin, $this->deiaQuestionBlockId, $deiaQuestion->getId());
        $form->initData();

        return new JSONMessage(true, $form->fetch($request));
    }

    public function updateDeiaQuestion($args, $request)
    {
        $deiaQuestionId = $request->getUserVar('deiaQuestionId')
            ? (int) $request->getUserVar('deiaQuestionId')
            : null;

        if ($deiaQuestionId) {
            $deiaQuestion = $this->getRequestQuestion($request, $deiaQuestionId);

            if (!$this->canModifyQuestion($deiaQuestion)) {
                return new JSONMessage(false);
            }
        } elseif (!$this->canModifyQuestionBlock()) {
            return new JSONMessage(false);
        }

        $form = new DeiaQuestionForm($this->plugin, $this->deiaQuestionBlockId, $deiaQuestionId);
        $form->readInputData();

        if ($form->validate()) {
            $deiaQuestionId = $form->execute();

            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification($request->getUser()->getId());

            return DAO::getDataChangedEvent($deiaQuestionId);
        }

        return new JSONMessage(false);
    }

    public function deleteDeiaQuestion($args, $request)
    {
        $deiaQuestionId = (int) $request->getUserVar('rowId');
        $deiaQuestion = $this->getRequestQuestion($request, $deiaQuestionId);

        if (!$request->checkCSRF() || !$this->canModifyQuestion($deiaQuestion)) {
            return new JSONMessage(false);
        }

        foreach ($deiaQuestion->getResponseOptions() as $responseOption) {
            Repo::deiaResponseOption()->delete($responseOption);
        }
        Repo::deiaQuestion()->delete($deiaQuestion);

        return DAO::getDataChangedEvent($deiaQuestionId);
    }

    private function getRequestQuestion($request, int $deiaQuestionId)
    {
        return Repo::deiaQuestion()->get($deiaQuestionId, $request->getContext()->getId());
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

if (!PKP_STRICT_MODE) {
    class_alias(
        '\APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestion\DeiaQuestionGridHandler',
        '\DeiaQuestionGridHandler'
    );
}
