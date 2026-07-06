<?php

namespace APP\plugins\generic\deiaSurvey\tests\stubs;

class RegisteredFiltersTemplateManagerStub
{
    public $registeredFilters = [];

    public function registerFilter(string $type, array $callback): void
    {
        $this->registeredFilters[] = [$type, $callback[1]];
    }
}
