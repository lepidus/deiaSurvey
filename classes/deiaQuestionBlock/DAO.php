<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'deiaQuestionBlock';
    public $table = 'deia_question_blocks';
    public $settingsTable = 'deia_question_block_settings';
    public $primaryKeyColumn = 'deia_question_block_id';
    public $primaryTableColumns = [
        'id' => 'deia_question_block_id',
        'contextId' => 'context_id',
        'sequence' => 'seq',
        'active' => 'is_active',
    ];

    public function getParentColumn(): string
    {
        return 'context_id';
    }

    public function newDataObject(): DeiaQuestionBlock
    {
        return app(DeiaQuestionBlock::class);
    }

    public function insert(DeiaQuestionBlock $deiaQuestionBlock): int
    {
        return parent::_insert($deiaQuestionBlock);
    }

    public function delete(DeiaQuestionBlock $deiaQuestionBlock)
    {
        return parent::_delete($deiaQuestionBlock);
    }

    public function update(DeiaQuestionBlock $deiaQuestionBlock)
    {
        return parent::_update($deiaQuestionBlock);
    }

    public function getCount(Collector $query): int
    {
        return $query->getQueryBuilder()->count();
    }

    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query->getQueryBuilder()->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->deia_question_block_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): DeiaQuestionBlock
    {
        return parent::fromRow($row);
    }

    public function resequence(int $contextId): void
    {
        $deiaQuestionBlockIds = DB::table($this->table)
            ->where('context_id', '=', $contextId)
            ->orderBy('seq')
            ->orderBy($this->primaryKeyColumn)
            ->pluck($this->primaryKeyColumn);

        $sequence = 0;
        foreach ($deiaQuestionBlockIds as $deiaQuestionBlockId) {
            DB::table($this->table)
                ->where($this->primaryKeyColumn, '=', $deiaQuestionBlockId)
                ->update(['seq' => ++$sequence]);
        }
    }
}
