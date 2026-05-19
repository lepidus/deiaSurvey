<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'deiaQuestion';
    public $table = 'deia_questions';
    public $settingsTable = 'deia_question_settings';
    public $primaryKeyColumn = 'deia_question_id';
    public $primaryTableColumns = [
        'id' => 'deia_question_id',
        'contextId' => 'context_id',
        'questionBlockId' => 'deia_question_block_id',
        'sequence' => 'seq',
        'questionType' => 'question_type'
    ];

    public function getParentColumn(): string
    {
        return 'context_id';
    }

    public function newDataObject(): DeiaQuestion
    {
        return app(DeiaQuestion::class);
    }

    public function insert(DeiaQuestion $deiaQuestion): int
    {
        return parent::_insert($deiaQuestion);
    }

    public function delete(DeiaQuestion $deiaQuestion)
    {
        return parent::_delete($deiaQuestion);
    }

    public function update(DeiaQuestion $deiaQuestion)
    {
        return parent::_update($deiaQuestion);
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
                yield $row->deia_question_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): DeiaQuestion
    {
        $deiaQuestion = parent::fromRow($row);

        if ($deiaQuestion->isTranslated() && @unserialize($deiaQuestion->getData('questionText'))) {
            $serializedQuestionText = $deiaQuestion->getData('questionText');
            $deiaQuestion->setData('questionText', unserialize($serializedQuestionText));
        }

        if ($deiaQuestion->isTranslated() && @unserialize($deiaQuestion->getData('questionDescription'))) {
            $serializedQuestionDescription = $deiaQuestion->getData('questionDescription');
            $deiaQuestion->setData('questionDescription', unserialize($serializedQuestionDescription));
        }

        return $deiaQuestion;
    }

    public function resequence(int $deiaQuestionBlockId): void
    {
        if (!Schema::hasColumn($this->table, 'deia_question_block_id') || !Schema::hasColumn($this->table, 'seq')) {
            return;
        }

        $deiaQuestionIds = DB::table($this->table)
            ->where('deia_question_block_id', '=', $deiaQuestionBlockId)
            ->orderBy('seq')
            ->orderBy($this->primaryKeyColumn)
            ->pluck($this->primaryKeyColumn);

        $sequence = 0;
        foreach ($deiaQuestionIds as $deiaQuestionId) {
            DB::table($this->table)
                ->where($this->primaryKeyColumn, '=', $deiaQuestionId)
                ->update(['seq' => ++$sequence]);
        }
    }
}
