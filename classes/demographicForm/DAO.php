<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicForm;

use APP\plugins\generic\deiaSurvey\classes\core\EntityDAO;
use APP\plugins\generic\deiaSurvey\classes\core\traits\EntityWithParent;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Capsule\Manager as Capsule;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'demographicForm';
    public $table = 'demographic_forms';
    public $settingsTable = 'demographic_form_settings';
    public $primaryKeyColumn = 'demographic_form_id';
    public $primaryTableColumns = [
        'id' => 'demographic_form_id',
        'contextId' => 'context_id',
        'sequence' => 'seq',
        'active' => 'is_active'
    ];

    public function getParentColumn(): string
    {
        return 'context_id';
    }

    public function newDataObject(): DemographicForm
    {
        return app(DemographicForm::class);
    }

    public function insert(DemographicForm $demographicForm): int
    {
        return parent::_insert($demographicForm);
    }

    public function delete(DemographicForm $demographicForm)
    {
        return parent::_delete($demographicForm);
    }

    public function update(DemographicForm $demographicForm)
    {
        return parent::_update($demographicForm);
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
                yield $row->demographic_form_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): \DataObject
    {
        return parent::fromRow($row);
    }

    public function resequence(int $contextId): void
    {
        $demographicFormIds = Capsule::table($this->table)
            ->where('context_id', '=', $contextId)
            ->pluck($this->primaryKeyColumn);

        $i = 0;
        foreach ($demographicFormIds as $demographicFormId) {
            Capsule::table($this->table)
                ->where($this->primaryKeyColumn, '=', $demographicFormId)
                ->update(['seq' => ++$i]);
        }
    }
}
