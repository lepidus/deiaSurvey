<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicResponseOption;

use PKP\core\EntityDAO;
use Illuminate\Support\LazyCollection;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'demographicResponseOption';
    public $table = 'demographic_response_options';
    public $primaryKeyColumn = 'demographic_response_option_id';
    public $settingsTable = 'demographic_response_option_settings';
    public $primaryTableColumns = [
        'id' => 'demographic_response_option_id',
        'demographicQuestionId' => 'demographic_question_id',
    ];

    public function getParentColumn(): string
    {
        return 'demographic_question_id';
    }

    public function newDataObject(): DemographicResponseOption
    {
        return app(DemographicResponseOption::class);
    }

    public function insert(DemographicResponseOption $demographicResponseOption): int
    {
        return parent::_insert($demographicResponseOption);
    }

    public function delete(DemographicResponseOption $demographicResponseOption)
    {
        return parent::_delete($demographicResponseOption);
    }

    public function update(DemographicResponseOption $demographicResponseOption)
    {
        return parent::_update($demographicResponseOption);
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
                yield $row->demographic_response_option_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): DemographicResponseOption
    {
        return parent::fromRow($row);
    }
}
