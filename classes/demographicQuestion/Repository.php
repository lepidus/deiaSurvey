<?php

namespace APP\plugins\generic\demographicData\classes\demographicQuestion;

use APP\plugins\generic\demographicData\classes\demographicQuestion\DAO;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DemographicQuestion
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $contextId = null): ?DemographicQuestion
    {
        return $this->dao->get($id, $contextId);
    }

    public function getCount(Collector $collector): ?DemographicQuestion
    {
        return $this->dao->getCount($collector);
    }

    public function getIds(Collector $collector): ?DemographicQuestion
    {
        return $this->dao->getIds($collector);
    }

    public function add(DemographicQuestion $demographicQuestion): int
    {
        $id = $this->dao->insert($demographicQuestion);
        return $id;
    }

    public function getMany(Collector $collector): ?DemographicQuestion
    {
        return $this->dao->getMany($collector);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
