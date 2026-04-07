<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicForm;

use APP\plugins\generic\deiaSurvey\classes\demographicForm\DAO;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DemographicForm
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $contextId = null): ?DemographicForm
    {
        return $this->dao->get($id, $contextId);
    }

    public function add(DemographicForm $demographicForm): int
    {
        $id = $this->dao->insert($demographicForm);
        return $id;
    }

    public function edit(DemographicForm $demographicForm, array $params)
    {
        $newDemographicForm = clone $demographicForm;
        $newDemographicForm->setAllData(array_merge($newDemographicForm->_data, $params));

        $this->dao->update($newDemographicForm);
    }

    public function delete(DemographicForm $demographicForm)
    {
        $this->dao->delete($demographicForm);
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
