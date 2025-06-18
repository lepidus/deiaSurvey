<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicResponse;

use APP\plugins\generic\deiaSurvey\classes\core\EntityDAO;
use APP\plugins\generic\deiaSurvey\classes\core\traits\EntityWithParent;
use Illuminate\Support\LazyCollection;

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
        'externalId' => 'external_id',
        'externalType' => 'external_type'
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

    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->count();
    }

    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->demographic_response_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): \DataObject
    {
        $demographicResponse = parent::fromRow($row);

        if (@unserialize($demographicResponse->getValue())) {
            $serializedValue = $demographicResponse->getValue();
            $demographicResponse->setValue(unserialize($serializedValue));
        }

        if (@unserialize($demographicResponse->getOptionsInputValue())) {
            $serializedValue = $demographicResponse->getOptionsInputValue();
            $demographicResponse->setOptionsInputValue(unserialize($serializedValue));
        }

        return $demographicResponse;
    }
}
