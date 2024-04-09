<?php

namespace APP\plugins\generic\demographicData\classes\form;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\user\User;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;
use APP\plugins\generic\demographicData\classes\facades\Repo;
use APP\plugins\generic\demographicData\classes\demographicQuestion\Collector;

class QuestionsForm extends Form
{
    public function __construct()
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        parent::__construct($plugin->getTemplateResource('questions.tpl'));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);

        return parent::fetch($request, $template, $display);
    }

    public function initData()
    {
        $questions = $this->retrieveQuestions();
        $this->setData('questions', $questions);
        parent::initData();
    }

    private function retrieveQuestions()
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
        $questions = array();
        $demographicQuestions = Repo::demographicQuestion()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany();

        foreach ($demographicQuestions as $demographicQuestion) {
            $user = $request->getUser();
            $demographicResponse = Repo::demographicResponse()
                ->getCollector()
                ->filterByQuestionIds([$demographicQuestion->getId()])
                ->filterByUserIds([$user->getId()])
                ->getMany();
            $responseResultInArray = $demographicResponse->toArray();
            $firstResponse = array_shift($responseResultInArray);
            $reponse = empty($demographicResponse->toArray()) ? null : $firstResponse->getText();
            $questions[] = [
                'title' => $demographicQuestion->getLocalizedQuestionText(),
                'description' => $demographicQuestion->getLocalizedQuestionDescription(),
                'response' => $reponse
            ];
        }

        return $questions;
    }

    public function readInputData()
    {
        parent::readInputData();
    }

    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\classes\form\QuestionsForm', '\QuestionsForm');
}
