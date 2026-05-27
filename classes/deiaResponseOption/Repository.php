<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaResponseOption;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DeiaResponseOption
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $deiaQuestionId = null): ?DeiaResponseOption
    {
        return $this->dao->get($id, $deiaQuestionId);
    }

    public function add(DeiaResponseOption $deiaResponseOption): int
    {
        $id = $this->dao->insert($deiaResponseOption);
        return $id;
    }

    public function edit(DeiaResponseOption $deiaResponseOption, array $params)
    {
        $newDeiaResponseOption = clone $deiaResponseOption;
        $newDeiaResponseOption->setAllData(array_merge($newDeiaResponseOption->_data, $params));

        $this->dao->update($newDeiaResponseOption);
    }

    public function delete(DeiaResponseOption $deiaResponseOption)
    {
        $this->dao->delete($deiaResponseOption);
    }

    public function exists(int $id, int $deiaQuestionId = null): bool
    {
        return $this->dao->exists($id, $deiaQuestionId);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
