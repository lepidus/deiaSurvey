<?php

namespace APP\plugins\generic\deiaSurvey\classes\deiaQuestionBlock;

class Repository
{
    public DAO $dao;

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
        return $this->dao->insert($deiaQuestionBlock);
    }

    public function edit(DeiaQuestionBlock $deiaQuestionBlock, array $params): void
    {
        $newDeiaQuestionBlock = clone $deiaQuestionBlock;
        $newDeiaQuestionBlock->setAllData(array_merge($newDeiaQuestionBlock->_data, $params));

        $this->dao->update($newDeiaQuestionBlock);
    }

    public function delete(DeiaQuestionBlock $deiaQuestionBlock): void
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
