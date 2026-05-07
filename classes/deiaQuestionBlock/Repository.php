<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock;

use APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock\DAO;

class Repository
{
    public $dao;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function newDataObject(array $params = []): DeiaQuestionBlock
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function get(int $id, int $contextId = null): ?DeiaQuestionBlock
    {
        return $this->dao->get($id, $contextId);
    }

    public function add(DeiaQuestionBlock $deiaQuestionBlock): int
    {
        $id = $this->dao->insert($deiaQuestionBlock);
        return $id;
    }

    public function edit(DeiaQuestionBlock $deiaQuestionBlock, array $params)
    {
        $newDeiaQuestionBlock = clone $deiaQuestionBlock;
        $newDeiaQuestionBlock->setAllData(array_merge($newDeiaQuestionBlock->_data, $params));

        $this->dao->update($newDeiaQuestionBlock);
    }

    public function delete(DeiaQuestionBlock $deiaQuestionBlock)
    {
        $this->dao->delete($deiaQuestionBlock);
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
