<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponse;

class DemographicResponse extends \PKP\core\DataObject
{
    public function getUserId(): int
    {
        return $this->getData('userId');
    }

    public function setUserId($userId)
    {
        $this->setData('userId', $userId);
    }

    public function getDemographicQuestionId(): int
    {
        return $this->getData('demographicQuestionId');
    }

    public function setDemographicQuestionId($demographicQuestionId)
    {
        $this->setData('demographicQuestionId', $demographicQuestionId);
    }

    public function getLocalizedText(): string
    {
        return $this->getLocalizedData('responseText');
    }

    public function getText(string $locale = null): string|array|null
    {
        return $this->getData('responseText', $locale);
    }

    public function setText($responseText, $locale)
    {
        $this->setData('responseText', $responseText, $locale);
    }
}
