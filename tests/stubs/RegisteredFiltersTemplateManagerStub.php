<?php

namespace APP\plugins\generic\deiaSurvey\tests\stubs;

class RegisteredFiltersTemplateManagerStub
{
    public array $registeredFilters = [];

    public function registerFilter(string $type, array $callback): void
    {
        $this->registeredFilters[] = [$type, $callback[1]];
    }
}
