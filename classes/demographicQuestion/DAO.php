<?php

namespace APP\plugins\generic\demographicData\classes\demographicQuestion;

use PKP\core\EntityDAO;
use PKP\services\PKPSchemaService;
use Illuminate\Support\LazyCollection;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'demographicQuestion';
    public $table = 'demographic_questions';
    public $settingsTable = 'demographic_question_settings';
    public $primaryKeyColumn = 'demographic_question_id';
    public $primaryTableColumns = [
        'id' => 'demographic_question_id',
        'contextId' => 'context_id',
    ];

    public function getParentColumn(): string
    {
        return 'context_id';
    }

    public function newDataObject(): DemographicQuestion
    {
        return app(DemographicQuestion::class);
    }

    public function insert(DemographicQuestion $demographicQuestion): int
    {
        return parent::_insert($demographicQuestion);
    }

    public function delete(DemographicQuestion $demographicQuestion)
    {
        return parent::_delete($demographicQuestion);
    }
}
