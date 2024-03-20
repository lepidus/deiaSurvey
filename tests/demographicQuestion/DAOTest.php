<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DAO;
use PKP\tests\DatabaseTestCase;
use PKP\db\DAORegistry;
use APP\journal\Journal;

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
    }

    private function createJournalMock()
    {
        $journal = $this->getMockBuilder(Journal::class)
            ->getMock();
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
}
