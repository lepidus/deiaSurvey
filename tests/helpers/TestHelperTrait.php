<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

use APP\journal\Journal;
use PKP\user\User;
use PKP\plugins\Hook;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\Repository as DemographicQuestionRepository;

trait TestHelperTrait
{
    private const DEFAULT_LOCALE = "en";

    private function createDemographicQuestion()
    {
        $questionData = [
            'contextId' => $this->createJournalMock(),
            'questionText' => 'plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.title',
            'questionDescription' => 'plugins.generic.deiaSurvey.demographicQuestion.exampleQuestion.description',
            'questionType' => DemographicQuestion::TYPE_TEXTAREA,
            'isTranslated' => false
        ];

        $repository = app(DemographicQuestionRepository::class);
        $demographicQuestion = $repository->newDataObject($questionData);
        return $repository->add($demographicQuestion);
    }

    private function createDemographicResponseOptionObject()
    {
        $responseOptionData = [
            'demographicQuestionId' => $this->demographicQuestionId,
            'optionText' => 'plugins.generic.deiaSurvey.demographicQuestion.exampleResponseOption.text',
            'isTranslated' => false,
            'hasInputField' => true,
        ];

        $demographicResponseOption = $this->demographicResponseOptionDAO->newDataObject();
        $demographicResponseOption->setAllData($responseOptionData);

        return $demographicResponseOption;
    }

    private function createDemographicResponseObject($externalAuthor = false)
    {
        $demographicResponse = $this->demographicResponseDAO->newDataObject();
        $demographicResponse->setDemographicQuestionId($this->demographicQuestionId);
        $demographicResponse->setValue([self::DEFAULT_LOCALE => 'Test text']);
        $demographicResponse->setOptionsInputValue([45 => 'Aditional information for response option']);

        if ($externalAuthor) {
            $demographicResponse->setExternalId('external.author@lepidus.com.br');
            $demographicResponse->setExternalType('email');
        } else {
            $demographicResponse->setUserId($this->createUserMock());
        }

        return $demographicResponse;
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
}
