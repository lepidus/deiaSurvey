<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

use APP\core\Application;
use APP\core\PageRouter;
use APP\journal\Journal;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\Repository as DeiaQuestionRepository;
use APP\plugins\generic\deiaSurvey\DeiaSurveyPlugin;
use PKP\core\Dispatcher;
use PKP\core\Registry;
use PKP\plugins\Hook;
use PKP\user\User;

trait TestHelperTrait
{
    private const DEFAULT_LOCALE = 'en';
    private const TEST_QUESTION_TEXT = 'plugins.generic.deiaSurvey.defaultQuestion.gender.title';
    private const TEST_QUESTION_DESCRIPTION = 'plugins.generic.deiaSurvey.defaultQuestion.gender.description';
    private const TEST_UPDATED_QUESTION_TEXT = 'plugins.generic.deiaSurvey.defaultQuestion.race.title';
    private const TEST_UPDATED_QUESTION_DESCRIPTION = 'plugins.generic.deiaSurvey.defaultQuestion.race.description';
    private const TEST_OPTION_TEXT = 'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.woman';
    private const TEST_UPDATED_OPTION_TEXT = 'plugins.generic.deiaSurvey.defaultQuestion.gender.responseOption.man';

    private function createDeiaQuestion()
    {
        $questionData = [
            'contextId' => $this->createJournalMock(),
            'questionText' => self::TEST_QUESTION_TEXT,
            'questionDescription' => self::TEST_QUESTION_DESCRIPTION,
            'questionType' => DeiaQuestion::TYPE_TEXTAREA,
            'questionBlockId' => null,
            'sequence' => null,
            'isTranslated' => false
        ];

        $repository = app(DeiaQuestionRepository::class);
        $deiaQuestion = $repository->newDataObject($questionData);
        return $repository->add($deiaQuestion);
    }

    private function createDeiaResponseOptionObject()
    {
        $responseOptionData = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'optionText' => self::TEST_OPTION_TEXT,
            'isTranslated' => false,
            'hasInputField' => true,
            'sequence' => null,
        ];

        $deiaResponseOption = $this->deiaResponseOptionDAO->newDataObject();
        $deiaResponseOption->setAllData($responseOptionData);

        return $deiaResponseOption;
    }

    private function createDeiaResponseObject($externalAuthor = false)
    {
        $deiaResponse = $this->deiaResponseDAO->newDataObject();
        $deiaResponse->setDeiaQuestionId($this->deiaQuestionId);
        $deiaResponse->setValue([self::DEFAULT_LOCALE => 'Test text']);
        $deiaResponse->setOptionsInputValue([45 => 'Aditional information for response option']);

        if ($externalAuthor) {
            $deiaResponse->setExternalId('external.author@lepidus.com.br');
            $deiaResponse->setExternalType('email');
        } else {
            $deiaResponse->setUserId($this->createUserMock());
        }

        return $deiaResponse;
    }

    private function createJournalMock()
    {
        $journal = $this->getMockBuilder(Journal::class)
            ->onlyMethods(['getId'])
            ->getMock();

        $journal->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $journal->setName('journal-title', 'en');
        $journal->setData('publisherInstitution', 'journal-publisher');
        $journal->setPrimaryLocale('en');
        $journal->setPath('journal-path');
        $journal->setId(1);

        return $journal->getId();
    }
    private function createUserMock()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        return $user->getId();
    }

    private function addSchemaFile(string $schemaName): void
    {
        Hook::add(
            'Schema::get::' . $schemaName,
            function (string $hookName, array $args) use ($schemaName) {
                $schema = &$args[0];

                $schemaFile = sprintf(
                    '%s/plugins/generic/deiaSurvey/schemas/%s.json',
                    BASE_SYS_DIR,
                    $schemaName
                );
                if (file_exists($schemaFile)) {
                    $schema = json_decode(file_get_contents($schemaFile));
                    if (!$schema) {
                        throw new \Exception(
                            'Schema failed to decode. This usually means it is invalid JSON. Requested: '
                            . $schemaFile
                            . '. Last JSON error: '
                            . json_last_error()
                        );
                    }
                }
                return true;
            }
        );
    }

    private function initializePluginLocaleData(): void
    {
        $plugin = new DeiaSurveyPlugin();
        $plugin->pluginPath = 'plugins/generic/deiaSurvey';
        $plugin->addLocaleData();
    }

    private function initializeRequestRouter(): void
    {
        Registry::delete('request');
        $application = Application::get();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['PATH_INFO'] = 'index/test-page/test-op';
        $request = $application->getRequest();

        $router = new PageRouter();
        $router->setApplication($application);
        $dispatcher = new Dispatcher();
        $dispatcher->setApplication($application);
        $router->setDispatcher($dispatcher);
        $request->setRouter($router);
    }
}
