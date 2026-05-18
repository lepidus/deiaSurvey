<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaResponse;

use APP\plugins\generic\deiaSurvey\classes\deiaResponse\DAO;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DeiaResponse
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $deiaQuestionId = null): ?DeiaResponse
    {
        return $this->dao->get($id, $deiaQuestionId);
    }

    public function add(DeiaResponse $deiaResponse): int
    {
        $id = $this->dao->insert($deiaResponse);
        return $id;
    }

    public function edit(DeiaResponse $deiaResponse, array $params)
    {
        $newDeiaResponse = clone $deiaResponse;
        $newDeiaResponse->setAllData(array_merge($newDeiaResponse->_data, $params));

        $this->dao->update($newDeiaResponse);
    }

    public function delete(DeiaResponse $deiaResponse)
    {
        $this->dao->delete($deiaResponse);
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
