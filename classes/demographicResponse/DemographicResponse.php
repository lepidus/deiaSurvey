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

    public function getValue()
    {
        return $this->getData('responseValue');
    }

    public function setValue($responseValue)
    {
        $this->setData('responseValue', $responseValue);
    }

    public function getOptionsInputValue()
    {
        return $this->getData('optionsInputValue');
    }

    public function setOptionsInputValue($optionsInputValue)
    {
        $this->setData('optionsInputValue', $optionsInputValue);
    }
}
