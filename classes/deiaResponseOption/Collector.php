<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaResponseOption;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use PKP\core\interfaces\CollectorInterface;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $questionIds = null;

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
        $queryBuilder = DB::table($this->dao->table . ' AS dro')
            ->select(['dro.*']);

        if (isset($this->questionIds)) {
            $queryBuilder->whereIn('dro.deia_question_id', $this->questionIds);
        }

        if (Schema::hasColumn($this->dao->table, 'seq')) {
            $queryBuilder->orderBy('dro.seq', 'asc');
        }

        return $queryBuilder->orderBy('dro.deia_response_option_id', 'asc');
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
