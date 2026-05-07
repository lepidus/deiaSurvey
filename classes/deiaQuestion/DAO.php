<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

use APP\plugins\generic\deiaSurvey\classes\core\EntityDAO;
use APP\plugins\generic\deiaSurvey\classes\core\traits\EntityWithParent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\LazyCollection;

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

    public function fromRow(object $row): \DataObject
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

    public function resequence(int $questionBlockId): void
    {
        $deiaQuestionIds = Capsule::table($this->table)
            ->where('deia_question_block_id', '=', $questionBlockId)
            ->orderBy('seq', 'asc')
            ->orderBy($this->primaryKeyColumn, 'asc')
            ->pluck($this->primaryKeyColumn);

        $i = 0;
        foreach ($deiaQuestionIds as $deiaQuestionId) {
            Capsule::table($this->table)
                ->where($this->primaryKeyColumn, '=', $deiaQuestionId)
                ->update(['seq' => ++$i]);
        }
    }
}
