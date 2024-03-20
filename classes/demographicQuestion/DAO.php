<?php

namespace APP\plugins\generic\demographicData\classes\demographicQuestion;

use PKP\core\EntityDAO;
use PKP\services\PKPSchemaService;
use Illuminate\Support\LazyCollection;

class DAO extends EntityDAO
{
    public $schema = 'demographicQuestion';
    public $table = 'demographic_questions';
    public $settingsTable = 'demographic_question_settings';
    public $primaryKeyColumn = 'demographic_question_id';
    public $primaryTableColumns = [
        'id' => 'demographic_question_id',
        'contextId' => 'context_id',
    ];

    public function newDataObject(): DemographicQuestion
    {
        return app(DemographicQuestion::class);
    }
}
