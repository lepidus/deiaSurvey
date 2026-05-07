<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock;

use APP\plugins\generic\deiaSurvey\classes\core\interfaces\CollectorInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\LazyCollection;

class Collector implements CollectorInterface
{
    public $dao;
    public $contextIds = null;
    public $active = null;

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
        $queryBuilder = Capsule::table($this->dao->table . ' as df')
            ->select(['df.*']);

        if (isset($this->contextIds)) {
            $queryBuilder->whereIn('df.context_id', $this->contextIds);
        }

        if (isset($this->active)) {
            $queryBuilder->where('df.is_active', '=', $this->active ? 1 : 0);
        }

        $queryBuilder->orderBy('df.seq', 'asc')
            ->orderBy('df.deia_question_block_id', 'asc');

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
