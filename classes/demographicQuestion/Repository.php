<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicQuestion;

use APP\plugins\generic\deiaSurvey\classes\demographicQuestion\DAO;

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

    public function add(DemographicQuestion $demographicQuestion): int
    {
        $id = $this->dao->insert($demographicQuestion);
        return $id;
    }

    public function edit(DemographicQuestion $demographicQuestion, array $params)
    {
        $newDemographicQuestion = clone $demographicQuestion;
        $newDemographicQuestion->setAllData(array_merge($newDemographicQuestion->_data, $params));

        $this->dao->update($newDemographicQuestion);
    }

    public function delete(DemographicQuestion $demographicQuestion)
    {
        $this->dao->delete($demographicQuestion);
    }

    public function exists(int $id, int $contextId = null): bool
    {
        return $this->dao->exists($id, $contextId);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
