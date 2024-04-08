<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponse;

use PKP\core\EntityDAO;
use Illuminate\Support\LazyCollection;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'demographicResponse';
    public $table = 'demographic_responses';
    public $primaryKeyColumn = 'demographic_response_id';
    public $settingsTable = 'demographic_response_settings';
    public $primaryTableColumns = [
        'id' => 'demographic_response_id',
        'demographicQuestionId' => 'demographic_question_id',
        'userId' => 'user_id',
    ];

    public function getParentColumn(): string
    {
        return 'demographic_question_id';
    }

    public function newDataObject(): DemographicResponse
    {
        return app(DemographicResponse::class);
    }

    public function insert(DemographicResponse $demographicResponse): int
    {
        return parent::_insert($demographicResponse);
    }

    public function delete(DemographicResponse $demographicResponse)
    {
        return parent::_delete($demographicResponse);
    }

    public function update(DemographicResponse $demographicResponse)
    {
        return parent::_update($demographicResponse);
    }
}
