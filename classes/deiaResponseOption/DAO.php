<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaResponseOption;

use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'deiaResponseOption';
    public $table = 'deia_response_options';
    public $primaryKeyColumn = 'deia_response_option_id';
    public $settingsTable = 'deia_response_option_settings';
    public $primaryTableColumns = [
        'id' => 'deia_response_option_id',
        'deiaQuestionId' => 'deia_question_id',
        'sequence' => 'seq',
    ];

    public function getParentColumn(): string
    {
        return 'deia_question_id';
    }

    public function newDataObject(): DeiaResponseOption
    {
        return app(DeiaResponseOption::class);
    }

    public function insert(DeiaResponseOption $deiaResponseOption): int
    {
        return parent::_insert($deiaResponseOption);
    }

    public function delete(DeiaResponseOption $deiaResponseOption)
    {
        return parent::_delete($deiaResponseOption);
    }

    public function update(DeiaResponseOption $deiaResponseOption)
    {
        return parent::_update($deiaResponseOption);
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
                yield $row->deia_response_option_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): DeiaResponseOption
    {
        $deiaResponseOption = parent::fromRow($row);

        if ($deiaResponseOption->isTranslated() && @unserialize($deiaResponseOption->getData('optionText'))) {
            $serializedOptionText = $deiaResponseOption->getData('optionText');
            $deiaResponseOption->setData('optionText', unserialize($serializedOptionText));
        }

        return $deiaResponseOption;
    }
}
