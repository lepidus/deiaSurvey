<?php

namespace APP\plugins\generic\deiaSurvey\classes\traits;

trait DemographicModelsTrait
{
    private function getLocalizedTextualData($dataName)
    {
        if ($this->isTranslated()) {
            return $this->getLocalizedData($dataName);
        }

        return __($this->getData($dataName));
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
