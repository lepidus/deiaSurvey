<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\core\interfaces\CollectorInterface;
use Illuminate\Support\LazyCollection;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $contextIds = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function filterByContextIds(?array $contextIds): Collector
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $queryBuilder = DB::table($this->dao->table . ' as deia_questions')
            ->select(['deia_questions.*']);

        if (isset($this->contextIds)) {
            $queryBuilder->whereIn('deia_questions.context_id', $this->contextIds);
        }

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
