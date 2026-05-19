<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use PKP\core\interfaces\CollectorInterface;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $contextIds = null;
    public ?array $questionBlockIds = null;

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
        $queryBuilder = DB::table($this->dao->table . ' as deia_questions')
            ->select(['deia_questions.*']);

        if (isset($this->contextIds)) {
            $queryBuilder->whereIn('deia_questions.context_id', $this->contextIds);
        }

        if (isset($this->questionBlockIds) && Schema::hasColumn($this->dao->table, 'deia_question_block_id')) {
            $queryBuilder->whereIn('deia_questions.deia_question_block_id', $this->questionBlockIds);
        }

        if (Schema::hasColumn($this->dao->table, 'seq')) {
            $queryBuilder->orderBy('deia_questions.seq', 'asc');
        }

        return $queryBuilder->orderBy('deia_questions.deia_question_id', 'asc');
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
