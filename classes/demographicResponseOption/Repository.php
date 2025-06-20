<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicResponseOption;

use APP\plugins\generic\deiaSurvey\classes\demographicResponseOption\DAO;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DemographicResponseOption
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $demographicQuestionId = null): ?DemographicResponseOption
    {
        return $this->dao->get($id, $demographicQuestionId);
    }

    public function add(DemographicResponseOption $demographicResponseOption): int
    {
        $id = $this->dao->insert($demographicResponseOption);
        return $id;
    }

    public function edit(DemographicResponseOption $demographicResponseOption, array $params)
    {
        $newDemographicResponseOption = clone $demographicResponseOption;
        $newDemographicResponseOption->setAllData(array_merge($newDemographicResponseOption->_data, $params));

        $this->dao->update($newDemographicResponseOption);
    }

    public function delete(DemographicResponseOption $demographicResponseOption)
    {
        $this->dao->delete($demographicResponseOption);
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
