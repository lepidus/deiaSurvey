<?php

namespace APP\plugins\generic\deiaSurvey\classes\traits;

trait DeiaModelsTrait
{
    private function getLocalizedTextualData($dataName)
    {
        $isTranslated = $this->isTranslated() ?? (gettype($this->getData($dataName)) === 'array');

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
