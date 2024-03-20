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

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
