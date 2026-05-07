<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

use APP\plugins\generic\deiaSurvey\classes\core\interfaces\CollectorInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;

class Collector implements CollectorInterface
{
    public $dao;
    public $contextIds = null;
    public $questionBlockIds = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function filterByContextIds(?array $contextIds): Collector
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    public function filterByQuestionBlockIds(?array $questionBlockIds): Collector
    {
        $this->questionBlockIds = $questionBlockIds;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $queryBuilder = Capsule::table($this->dao->table . ' as deia_questions')
            ->select(['deia_questions.*']);

        if (isset($this->contextIds)) {
            $queryBuilder->whereIn('deia_questions.context_id', $this->contextIds);
        }

        if (isset($this->questionBlockIds)) {
            $queryBuilder->whereIn('deia_questions.deia_question_block_id', $this->questionBlockIds);
        }

        $queryBuilder->orderBy('deia_questions.seq', 'asc')
            ->orderBy('deia_questions.deia_question_id', 'asc');

        return $queryBuilder;
    }

    public function getCount(): int
    {
        return $this->dao->getCount($this);
    }

    public function getIds(): Collection
    {
        return $this->dao->getIds($this);
    }

    public function getMany(): LazyCollection
    {
        return $this->dao->getMany($this);
    }
}
