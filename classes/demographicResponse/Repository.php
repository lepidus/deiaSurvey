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

    public function get(int $id, int $demographicQuestionId = null): ?DemographicResponse
    {
        return $this->dao->get($id, $demographicQuestionId);
    }

    public function add(DemographicResponse $demographicResponse): int
    {
        $id = $this->dao->insert($demographicResponse);
        return $id;
    }

    public function edit(DemographicResponse $demographicResponse, array $params)
    {
        $newDemographicResponse = clone $demographicResponse;
        $newDemographicResponse->setAllData(array_merge($newDemographicResponse->_data, $params));

        $this->dao->update($newDemographicResponse);
    }

    public function delete(DemographicResponse $demographicResponse)
    {
        $this->dao->delete($demographicResponse);
    }

    public function exists(int $id, int $demographicQuestionId = null): bool
    {
        return $this->dao->exists($id, $demographicQuestionId);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
