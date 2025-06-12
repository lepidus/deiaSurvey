<?php

namespace APP\plugins\generic\demographicData\classes\dispatchers;

abstract class DemographicDataDispatcher
{
    protected $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->registerHooks();
    }

    abstract protected function registerHooks(): void;
}
