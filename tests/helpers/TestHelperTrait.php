<?php

namespace APP\plugins\generic\deiaSurvey\tests\helpers;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\DeiaQuestion;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestion\Repository as DeiaQuestionRepository;
use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\Repository as DeiaQuestionBlockRepository;

import('classes.journal.Journal');
import('lib.pkp.classes.user.User');

trait TestHelperTrait
{
    protected $affectedTables;

    protected function setAffectedTables($affectedTables)
    {
        $this->affectedTables = $affectedTables;
    }

    private function restoreTables($tables)
    {
        $dao = new \DAO();
        foreach ($tables as $table) {
            $sqls = [
                "DELETE FROM {$table}",
                "INSERT INTO {$table} SELECT * FROM backup_{$table}",
                "DROP TABLE backup_{$table}"
            ];
            foreach ($sqls as $sql) {
                $dao->update($sql, [], true, false);
            }
        }
    }

    private function createDeiaQuestion()
    {
        $contextId = $this->createJournalMock();
        $questionBlockId = $this->createDeiaQuestionBlock($contextId);
        $questionData = [
            'contextId' => $contextId,
            'questionBlockId' => $questionBlockId,
            'sequence' => 1,
            'questionType' => DeiaQuestion::TYPE_TEXTAREA,
            'questionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.title',
            'questionDescription' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleQuestion.description',
            'isTranslated' => false
        ];

        $repository = app(DeiaQuestionRepository::class);
        $deiaQuestion = $repository->newDataObject($questionData);
        return $repository->add($deiaQuestion);
    }

    private function createDeiaQuestionBlock($contextId = null)
    {
        $repository = app(DeiaQuestionBlockRepository::class);
        $questionBlock = $repository->newDataObject([
            'contextId' => $contextId ?? $this->createJournalMock(),
            'title' => ['en_US' => 'Question block'],
            'description' => ['en_US' => 'Question block description'],
            'active' => 1,
            'sequence' => 1,
        ]);

        return $repository->add($questionBlock);
    }

    private function createDeiaResponseOptionObject()
    {
        $responseOptionData = [
            'deiaQuestionId' => $this->deiaQuestionId,
            'sequence' => 1,
            'optionText' => 'plugins.generic.deiaSurvey.deiaQuestion.exampleResponseOption.text',
            'isTranslated' => false,
            'hasInputField' => true,
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
        $journal = $this->getMockBuilder(\Journal::class)
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
        $user = $this->getMockBuilder(\User::class)
            ->onlyMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        return $user->getId();
    }

    private function addSchemaFile(string $schemaName): void
    {
        \HookRegistry::register(
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
