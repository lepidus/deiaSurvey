<?php

namespace APP\plugins\generic\deiaSurvey\classes\traits;

trait DemographicModelsTrait
{
    private function getLocalizedTextualData($dataName)
    {
        $isTranslated = $this->isTranslated();

        if (is_null($isTranslated)) {
            return (gettype($this->getData($dataName)) === 'array')
                ? $this->getLocalizedData($dataName)
                : __($this->getData($dataName));
        }

        return $isTranslated
            ? $this->getLocalizedData($dataName)
            : __($this->getData($dataName));
    }

    private function setTextualData($dataName, $dataValue, $locale = null)
    {
        if ($this->isTranslated()) {
            $this->setData($dataName, $dataValue, $locale);
            return;
        }

        $this->setData($dataName, $dataValue);
    }
}
