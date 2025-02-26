<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponseOption;

use APP\plugins\generic\demographicData\classes\core\interfaces\CollectorInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;

class Collector implements CollectorInterface
{
    public $dao;
    public $questionIds = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function filterByQuestionIds(?array $questionIds): Collector
    {
        $this->questionIds = $questionIds;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $queryBuilder = Capsule::table($this->dao->table . ' AS dro')
            ->select(['dro.*']);

        if (isset($this->questionIds)) {
            $queryBuilder->whereIn('dro.demographic_question_id', $this->questionIds);
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
