<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestion;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DeiaQuestion
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $contextId = null): ?DeiaQuestion
    {
        return $this->dao->get($id, $contextId);
    }

    public function add(DeiaQuestion $deiaQuestion): int
    {
        $id = $this->dao->insert($deiaQuestion);
        return $id;
    }

    public function edit(DeiaQuestion $deiaQuestion, array $params)
    {
        $newDeiaQuestion = clone $deiaQuestion;
        $newDeiaQuestion->setAllData(array_merge($newDeiaQuestion->_data, $params));

        $this->dao->update($newDeiaQuestion);
    }

    public function delete(DeiaQuestion $deiaQuestion)
    {
        $this->dao->delete($deiaQuestion);
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
