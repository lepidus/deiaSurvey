<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

use PKP\core\EntityDAO;
use Illuminate\Support\LazyCollection;
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
}
