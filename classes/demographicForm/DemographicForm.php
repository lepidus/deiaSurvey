<?php

namespace APP\plugins\generic\deiaSurvey\classes\demographicForm;

class DemographicForm extends \DataObject
{
    public function getLocalizedTitle(): ?string
    {
        return $this->getLocalizedData('title');
    }

    public function getLocalizedDescription(): ?string
    {
        return $this->getLocalizedData('description');
    }

    public function getContextId(): ?int
    {
        return $this->getData('contextId');
    }

    public function setContextId(?int $contextId): void
    {
        $this->setData('contextId', $contextId);
    }

    public function getCompleteCount()
    {
        return $this->getData('completeCount');
    }

    public function setCompleteCount($completeCount)
    {
        $this->setData('completeCount', $completeCount);
    }

    public function getSequence(): ?int
    {
        return $this->getData('sequence');
    }

    public function setSequence(?int $sequence): void
    {
        $this->setData('sequence', $sequence);
    }

    public function getActive(): ?int
    {
        return $this->getData('active');
    }

    public function setActive(?int $active): void
    {
        $this->setData('active', $active);
    }

    public function getTitle(?string $locale): ?string
    {
        return $this->getData('title', $locale);
    }

    public function setTitle(?string $title, ?string $locale): void
    {
        $this->setData('title', $title, $locale);
    }

    public function getDescription(?string $locale): ?string
    {
        return $this->getData('description', $locale);
    }

    public function setDescription(?string $description, ?string $locale): void
    {
        $this->setData('description', $description, $locale);
    }
}
