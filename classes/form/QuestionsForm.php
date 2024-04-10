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
    private $request;
    private $args;

    public function __construct($request = null, $args = null)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'demographicdataplugin');
        if ($request) {
            $this->request = $request;
        }

        if ($args) {
            $this->args = $this->getQuestionResponsesByForm($args);
        }

        parent::__construct($plugin->getTemplateResource('questions.tpl'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    private function getQuestionResponsesByForm($args)
    {
        $responses = array();
        foreach ($args as $key => $value) {
            if (strpos($key, 'question-') === 0) {
                $responses[$key] = $value;
            }
        }
        return $responses;
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
                'response' => $reponse,
                'questionId' => $demographicQuestion->getId()
            ];
        }
        return $questions;
    }

    public function execute(...$functionArgs)
    {
        $userId = $this->request->getUser()->getId();
        foreach ($this->args as $question => $response) {
            $questionId = explode("-", $question)[1];
            $demographicResponseCollector = Repo::demographicResponse()
                    ->getCollector()
                    ->filterByQuestionIds([$questionId])
                    ->filterByUserIds([$userId])
                    ->getMany();
            $responseResultInArray = $demographicResponseCollector->toArray();
            $demographicResponse = array_shift($responseResultInArray);
            if (!empty($response)) {
                $params = [
                    'demographicQuestionId',
                    'userId',
                    'responseText' => $response
                ];
                Repo::demographicResponse()->edit($demographicResponse, $params);
            } else {
                foreach ($response as $locale => $responseText) {
                    if (!is_null($responseText)) {
                        $response = Repo::demographicResponse()->newDataObject();
                        $response->setUserId($userId);
                        $response->setDemographicQuestionId($questionId);
                        $response->setText($responseText, $locale);
                        Repo::demographicResponse()->add($response);
                    }
                }
            }
        }
        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\demographicData\classes\form\QuestionsForm', '\QuestionsForm');
}
