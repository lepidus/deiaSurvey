<?php

namespace APP\plugins\generic\deiaSurvey\classes\controllers\grid\deiaQuestionBlock\form;

use APP\core\Application;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\facades\Repo;
use APP\template\TemplateManager;
use PKP\controllers\listbuilder\ListbuilderHandler;
use PKP\facades\Locale;
use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorLocale;
use PKP\form\validation\FormValidatorPost;

class DeiaQuestionForm extends Form
{
    public $plugin;
    public int $deiaQuestionBlockId;
    public ?int $deiaQuestionId;

    public function __construct($plugin, int $deiaQuestionBlockId, ?int $deiaQuestionId = null)
    {
        parent::__construct($plugin->getTemplateResource('deiaQuestionBlocks/deiaQuestionForm.tpl'));

        $this->plugin = $plugin;
        $this->deiaQuestionBlockId = $deiaQuestionBlockId;
        $this->deiaQuestionId = $deiaQuestionId;

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        $this->addCheck(new FormValidatorLocale(
            $this,
            'questionText',
            'required',
            'plugins.generic.deiaSurvey.questionBlocks.questions.form.questionTextRequired'
        ));
        $this->addCheck(new FormValidator(
            $this,
            'questionType',
            'required',
            'plugins.generic.deiaSurvey.questionBlocks.questions.form.questionTypeRequired'
        ));
    }

    public function readInputData()
    {
        $this->readUserVars([
            'deiaQuestionBlockId',
            'deiaQuestionId',
            'questionText',
            'questionDescription',
            'questionType',
            'responseOptions',
            'questionVisibility',
            'showWhenQuestion',
            'showWhenOption',
        ]);
    }

    public function initData()
    {
        if (!$this->deiaQuestionId) {
            $this->setData('questionType', DeiaQuestion::TYPE_TEXT_FIELD);
            return;
        }

        $request = Application::get()->getRequest();
        $deiaQuestion = Repo::deiaQuestion()->get($this->deiaQuestionId, $request->getContext()->getId());

        [$sourceQuestion, $sourceOption] = $deiaQuestion->getData('showWhen') ?: [null, null];
        $this->setData('showWhenQuestion', $sourceQuestion);
        $this->setData('showWhenOption', $sourceOption);
        $this->setData('questionText', $deiaQuestion->getData('questionText'));
        $this->setData('questionDescription', $deiaQuestion->getData('questionDescription'));
        $this->setData('questionType', $deiaQuestion->getQuestionType());
        $this->setData('responseOptions', $this->getResponseOptionsForListbuilder());
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $responseOptionQuestionTypes = DeiaQuestion::TYPES_WITH_RESPONSE_OPTIONS;
        $questionType = (int) $this->getData('questionType');
        $templateMgr->assign([
            'deiaQuestionBlockId' => $this->deiaQuestionBlockId,
            'deiaQuestionId' => $this->deiaQuestionId,
            'conditionSources' => $this->getConditionSources($request->getContext()->getId()),
            'questionTypeOptions' => [
                DeiaQuestion::TYPE_SMALL_TEXT_FIELD
                    => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.smallText',
                DeiaQuestion::TYPE_TEXT_FIELD
                    => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.text',
                DeiaQuestion::TYPE_TEXTAREA
                    => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.textarea',
                DeiaQuestion::TYPE_CHECKBOXES
                    => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.checkboxes',
                DeiaQuestion::TYPE_RADIO_BUTTONS
                    => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.radioButtons',
                DeiaQuestion::TYPE_DROP_DOWN_BOX
                    => 'plugins.generic.deiaSurvey.questionBlocks.questions.type.select',
            ],
            'responseOptionQuestionTypesString' => ';' . implode(';', $responseOptionQuestionTypes) . ';',
            'questionTypeAllowsResponseOptions' => in_array($questionType, $responseOptionQuestionTypes, true),
        ]);

        return parent::fetch($request, $template, $display);
    }

    public function validate($callHooks = true)
    {
        if (!parent::validate($callHooks)) {
            return false;
        }

        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
        $visibility = $this->getData('questionVisibility');
        $sourceQuestion = $this->getData('showWhenQuestion');
        $sourceOption = $this->getData('showWhenOption');
        $showWhen = [];
        if (
            $visibility !== 'always' && (
                $visibility === 'conditional'
                || ($sourceQuestion !== null && $sourceQuestion !== '')
                || ($sourceOption !== null && $sourceOption !== '')
            )
        ) {
            $sources = $this->getConditionSources($contextId);
            if (
                !is_scalar($sourceQuestion) || !is_scalar($sourceOption)
                || !isset($sources[(string) $sourceQuestion]['options'][(string) $sourceOption])
            ) {
                $this->addError('showWhenOption', __('plugins.generic.deiaSurvey.questionBlocks.questions.invalidCondition'));
                return false;
            }
            $showWhen = [(int) $sourceQuestion, (int) $sourceOption];
        }
        $this->setData('showWhen', $showWhen);

        $questionType = (int) $this->getData('questionType');
        $this->setData('responseOptionsProcessed', []);
        if (in_array($questionType, DeiaQuestion::TYPES_WITH_RESPONSE_OPTIONS, true)) {
            $this->setData('responseOptionsProcessed', $this->getResponseOptionsForListbuilder());
            if (!ListbuilderHandler::unpack(
                $request,
                $this->getData('responseOptions') ?? '',
                [$this, 'deleteEntry'],
                [$this, 'insertEntry'],
                [$this, 'updateEntry']
            )) {
                return false;
            }
        }

        if ($this->deiaQuestionId) {
            $keptOptions = [];
            foreach ($this->getData('responseOptionsProcessed') as $option) {
                if (isset($option['id']) && array_filter((array) $option['optionText'])) {
                    $keptOptions[$option['id']] = true;
                }
            }
            foreach (Repo::deiaQuestion()->getBlockQuestions($contextId, $this->deiaQuestionBlockId) as $question) {
                $condition = $question->getData('showWhen');
                if (
                    $condition && $condition[0] === $this->deiaQuestionId
                    && !isset($keptOptions[$condition[1]])
                ) {
                    $this->addError('responseOptions', __('plugins.generic.deiaSurvey.questionBlocks.questions.conditionInUse'));
                    return false;
                }
            }
        }

        return true;
    }

    private function getConditionSources(int $contextId): array
    {
        $sources = [];
        foreach (Repo::deiaQuestion()->getBlockQuestions($contextId, $this->deiaQuestionBlockId) as $question) {
            if ($question->getId() === $this->deiaQuestionId) {
                break;
            }
            if (!in_array((int) $question->getQuestionType(), DeiaQuestion::TYPES_WITH_RESPONSE_OPTIONS, true)) {
                continue;
            }

            $options = [];
            foreach ($question->getResponseOptions() as $option) {
                $options[$option->getId()] = $option->getLocalizedOptionText();
            }
            if ($options) {
                $sources[$question->getId()] = ['text' => $question->getLocalizedQuestionText(), 'options' => $options];
            }
        }

        return $sources;
    }

    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if ($this->deiaQuestionId) {
            $deiaQuestion = Repo::deiaQuestion()->get($this->deiaQuestionId, $context->getId());
        } else {
            $deiaQuestion = Repo::deiaQuestion()->newDataObject();
            $deiaQuestion->setContextId($context->getId());
            $deiaQuestion->setQuestionBlockId($this->deiaQuestionBlockId);
            $deiaQuestion->setSequence(REALLY_BIG_NUMBER);
        }

        $questionParams = [
            'contextId' => $context->getId(),
            'questionBlockId' => $this->deiaQuestionBlockId,
            'questionType' => (int) $this->getData('questionType'),
            'questionText' => $this->getData('questionText'),
            'questionDescription' => $this->getData('questionDescription'),
            'isTranslated' => true,
            'isDefaultQuestion' => false,
            'showWhen' => $this->getData('showWhen') ?? [],
        ];

        if ($this->deiaQuestionId) {
            Repo::deiaQuestion()->edit($deiaQuestion, $questionParams);
        } else {
            $deiaQuestion->setAllData(array_merge($deiaQuestion->_data, $questionParams));
            $this->deiaQuestionId = Repo::deiaQuestion()->add($deiaQuestion);
            Repo::deiaQuestion()->dao->resequence($this->deiaQuestionBlockId);
        }

        $this->replaceResponseOptions($this->getData('responseOptionsProcessed') ?? []);

        parent::execute(...$functionArgs);

        return $this->deiaQuestionId;
    }

    public function insertEntry($request, $newRowId): bool
    {
        $responseOptionsProcessed = (array) $this->getData('responseOptionsProcessed');
        $responseOptionsProcessed['new-' . count($responseOptionsProcessed)] = [
            'optionText' => $newRowId['responseOption'] ?? [],
            'hasInputField' => !empty($newRowId['hasInputField']),
        ];
        $this->setData('responseOptionsProcessed', $responseOptionsProcessed);
        return true;
    }

    public function deleteEntry($request, $rowId): bool
    {
        $responseOptionsProcessed = (array) $this->getData('responseOptionsProcessed');
        if (!isset($responseOptionsProcessed[$rowId])) {
            return false;
        }
        unset($responseOptionsProcessed[$rowId]);
        $this->setData('responseOptionsProcessed', $responseOptionsProcessed);
        return true;
    }

    public function updateEntry($request, $rowId, $newRowId): bool
    {
        $responseOptionsProcessed = (array) $this->getData('responseOptionsProcessed');
        if ((!is_string($rowId) && !is_int($rowId)) || !isset($responseOptionsProcessed[$rowId]['id'])) {
            return false;
        }
        $responseOptionsProcessed[$rowId] = [
            'id' => $responseOptionsProcessed[$rowId]['id'],
            'optionText' => $newRowId['responseOption'] ?? [],
            'hasInputField' => !empty($newRowId['hasInputField']),
        ];
        $this->setData('responseOptionsProcessed', $responseOptionsProcessed);
        return true;
    }

    public function getLocaleFieldNames(): array
    {
        return ['questionText', 'questionDescription'];
    }

    private function replaceResponseOptions(array $responseOptions): void
    {
        $responsesCount = Repo::deiaResponse()->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->getCount();

        if ($responsesCount > 0) {
            return;
        }

        $existingOptions = $this->getExistingResponseOptions()->toArray();
        $sequence = 0;
        foreach ($responseOptions as $responseOptionData) {
            if (empty(array_filter((array) $responseOptionData['optionText']))) {
                continue;
            }

            $params = [
                'deiaQuestionId' => $this->deiaQuestionId,
                'sequence' => ++$sequence,
                'optionText' => $responseOptionData['optionText'],
                'isTranslated' => true,
                'hasInputField' => !empty($responseOptionData['hasInputField']),
            ];
            $optionId = $responseOptionData['id'] ?? null;
            if ($optionId !== null) {
                Repo::deiaResponseOption()->edit($existingOptions[$optionId], $params);
                unset($existingOptions[$optionId]);
            } else {
                Repo::deiaResponseOption()->add(Repo::deiaResponseOption()->newDataObject($params));
            }
        }

        foreach ($existingOptions as $existingOption) {
            Repo::deiaResponseOption()->delete($existingOption);
        }
    }

    private function getResponseOptionsForListbuilder(): array
    {
        if (!$this->deiaQuestionId) {
            return [];
        }

        $locale = Locale::getLocale();
        $responseOptions = [];

        foreach ($this->getExistingResponseOptions() as $responseOption) {
            $optionText = $responseOption->getData('optionText');
            $responseOptions[$responseOption->getId()] = [
                'id' => $responseOption->getId(),
                'optionText' => is_array($optionText)
                    ? $optionText
                    : [$locale => $responseOption->getLocalizedOptionText()],
                'hasInputField' => $responseOption->hasInputField(),
            ];
        }

        return $responseOptions;
    }

    private function getExistingResponseOptions()
    {
        return Repo::deiaResponseOption()->getCollector()
            ->filterByQuestionIds([$this->deiaQuestionId])
            ->getMany();
    }
}
