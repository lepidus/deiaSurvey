<?php

namespace APP\plugins\generic\demographicData\tests\demographicResponse;

use APP\plugins\generic\demographicData\classes\demographicResponse\DemographicResponse;
use APP\plugins\generic\demographicData\classes\demographicResponse\DAO;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

class DAOTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $demographicResponseDAO;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'demographic_questions',
            'demographic_question_settings',
            'demographic_question_responses'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->demographicResponseDAO = app(DAO::class);
        $this->addSchemaFile('demographicResponse');
    }

    public function testNewDataObjectIsInstanceOfDemographicResponse(): void
    {
        $demographicResponse = $this->demographicResponseDAO->newDataObject();
        self::assertInstanceOf(DemographicResponse::class, $demographicResponse);
    }
}
