<?php

namespace APP\plugins\generic\demographicData\tests\demographicQuestion;

use APP\plugins\generic\demographicData\classes\demographicQuestion\DemographicQuestion;
use APP\plugins\generic\demographicData\classes\demographicQuestion\Repository;
use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\demographicData\tests\helpers\TestHelperTrait;

class RepositoryTest extends DatabaseTestCase
{
    use TestHelperTrait;

    private $contextId;

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
        $this->contextId = $this->createJournalMock();
        $this->addSchemaFile('demographicQuestion');
    }

    public function testGetNewCustomQuestionObject(): void
    {
        $repository = app(Repository::class);
        $demographicQuestion = $repository->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);

        $params = [
            'id' => 123,
            'contextId' => $this->contextId,
            'title' => [
                'en' => 'Test title'
            ],
            'description' => [
                'en' => 'Test description'
            ]
        ];
        $demographicQuestion = $repository->newDataObject($params);
        self::assertEquals($params, $demographicQuestion->_data);
    }
}
