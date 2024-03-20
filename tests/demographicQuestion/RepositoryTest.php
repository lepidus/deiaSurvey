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
    private $locale;
    private array $params;

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
        $this->locale = "en";
        $this->params = [
            'contextId' => $this->contextId,
            'title' => [
                $this->locale => 'Test title'
            ],
            'description' => [
                $this->locale => 'Test description'
            ]
        ];
        $this->addSchemaFile('demographicQuestion');
    }

    public function testGetNewCustomQuestionObject(): void
    {
        $repository = app(Repository::class);
        $demographicQuestion = $repository->newDataObject();
        self::assertInstanceOf(DemographicQuestion::class, $demographicQuestion);
        $demographicQuestion = $repository->newDataObject($this->params);
        self::assertEquals($this->params, $demographicQuestion->_data);
    }
}
