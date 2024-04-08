<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponse;

use APP\plugins\generic\demographicData\classes\demographicResponse\DAO;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DemographicResponse
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }
}
