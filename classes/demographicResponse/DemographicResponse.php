<?php

namespace APP\plugins\generic\demographicData\classes\demographicResponse;

class DemographicResponse extends \PKP\core\DataObject
{
    public function getUserId(): ?int
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

    public function getExternalId(): ?string
    {
        return $this->getData('externalId');
    }

    public function setExternalId($externalId)
    {
        $this->setData('externalId', $externalId);
    }

    public function getExternalType(): ?string
    {
        return $this->getData('externalType');
    }

    public function setExternalType($externalType)
    {
        $this->setData('externalType', $externalType);
    }

    public function getLocalizedText(): string
    {
        return $this->getLocalizedData('responseText');
    }

    public function getText(string $locale = null)
    {
        return $this->getData('responseText', $locale);
    }

    public function setText($responseText, $locale)
    {
        $this->setData('responseText', $responseText, $locale);
    }
}
