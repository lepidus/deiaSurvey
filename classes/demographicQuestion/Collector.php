<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicQuestion;

use APP\plugins\generic\deiaSurvey\classes\core\interfaces\CollectorInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;

class Collector implements CollectorInterface
{
    public $dao;
    public $contextIds = null;

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
        $queryBuilder = Capsule::table($this->dao->table . ' as demographic_questions')
            ->select(['demographic_questions.*']);

        if (isset($this->contextIds)) {
            $queryBuilder->whereIn('demographic_questions.context_id', $this->contextIds);
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
