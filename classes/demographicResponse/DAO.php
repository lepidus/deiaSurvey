<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponse;

use PKP\core\EntityDAO;
use Illuminate\Support\LazyCollection;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'demographicResponse';
    public $table = 'demographic_question_responses';
    public $primaryKeyColumn = 'demographic_question_response_id';
    public $primaryTableColumns = [
        'id' => 'demographic_question_response_id',
        'demographicQuestionId' => 'demographic_question_id',
    ];

    public function getParentColumn(): string
    {
        return 'demographic_question_id';
    }

    public function newDataObject(): DemographicResponse
    {
        return app(DemographicResponse::class);
    }
}
