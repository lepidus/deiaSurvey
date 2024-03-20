<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DAO;
use PKP\tests\DatabaseTestCase;
use PKP\db\DAORegistry;
use APP\journal\Journal;
use PKP\plugins\Hook;

class DAOTest extends DatabaseTestCase
{
    private $context;
    private $demographicQuestionDAO;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->demographicQuestionDAO = app(DAO::class);
        $this->context = $this->createJournalMock();
        $this->addSchemaFile('demographicQuestion');
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

        return $journal;
    }

    public function testCreateNewDataObject(): void
    {
        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);
    }

    public function testCreateDemographicQuestion(): void
    {
        $locale = 'en';

        $demographicQuestion = $this->demographicQuestionDAO->newDataObject();
        $demographicQuestion->setContextId($this->context->getId());
        $demographicQuestion->setQuestionText('Test title', $locale);
        $demographicQuestion->setQuestionDescription('Test description', $locale);
        $insertedDemographicQuestionId = $this->demographicQuestionDAO->insert($demographicQuestion);

        $fetchedDemographicQuestion = $this->demographicQuestionDAO->get($insertedDemographicQuestionId, $this->context->getId());
        self::assertEquals([
            'id' => $insertedDemographicQuestionId,
            'contextId' => $this->context->getId(),
            'questionText' => ['en' => 'Test title'],
            'questionDescription' => ['en' => 'Test description'],
        ], $fetchedDemographicQuestion->_data);
    }

    private function addSchemaFile(string $schemaName): void
    {
        Hook::add(
            'Schema::get::' . $schemaName,
            function (string $hookName, array $args) use ($schemaName) {
                $schema = &$args[0];

                $schemaFile = sprintf(
                    '%s/plugins/generic/demographicData/schemas/%s.json',
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
