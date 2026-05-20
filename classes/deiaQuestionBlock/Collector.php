<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\interfaces\CollectorInterface;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $contextIds = null;
    public ?bool $active = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function filterByContextIds(?array $contextIds): Collector
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    public function filterByActive(?bool $active): Collector
    {
        $this->active = $active;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $queryBuilder = DB::table($this->dao->table . ' as dqb')
            ->select(['dqb.*']);

        if (isset($this->contextIds)) {
            $queryBuilder->whereIn('dqb.context_id', $this->contextIds);
        }

        if (isset($this->active)) {
            $queryBuilder->where('dqb.is_active', '=', $this->active ? 1 : 0);
        }

        return $queryBuilder
            ->orderBy('dqb.seq', 'asc')
            ->orderBy('dqb.deia_question_block_id', 'asc');
    }

    public function getCount(): int
    {
        return $this->dao->getCount($this);
    }

    public function getMany(): LazyCollection
    {
        return $this->dao->getMany($this);
    }
}
