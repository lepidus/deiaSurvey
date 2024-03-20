<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\demographicQuestion\DAO;
use PKP\tests\DatabaseTestCase;

class DAOTest extends DatabaseTestCase
{
    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings'
        ];
    }

    public function testCreateNewDataObject(): void
    {
        $demographicQuestionDAO = app(DAO::class);
        $demographicQuestion = $demographicQuestionDAO->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);
    }
}
