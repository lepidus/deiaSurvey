<?php

namespace APP\plugins\generic\deiaSurvey\classes\observers\listeners\defaultQuestions;

use Illuminate\Events\Dispatcher;
use PKP\observers\events\PluginSettingChanged;
use PKP\plugins\Hook;
use APP\plugins\generic\deiaSurvey\classes\observers\listeners\defaultQuestions\DefaultQuestionsCreator;

class CreateDefaultQuestions
{
    private const PLUGIN_NAME = 'deiasurveyplugin';

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            PluginSettingChanged::class,
            CreateDefaultQuestions::class
        );
    }

    public function handle(PluginSettingChanged $event): void
    {
        $plugin = $event->plugin;
        $settingName = $event->settingName;
        $newValue = $event->newValue;

        if (
            $plugin->getName() !== self::PLUGIN_NAME
            || $settingName !== 'enabled'
            || !$newValue
        ) {
            return;
        }

        $contextId = $event->contextId;
        error_log('Creating default questions for context ID: ' . $contextId);
        $defaultQuestionsCreator = new DefaultQuestionsCreator();

        $this->registerHooksForCustomSchemas($plugin);
        $defaultQuestionsCreator->createDefaultQuestions($contextId);
    }

    private function registerHooksForCustomSchemas($plugin)
    {
        Hook::add('Schema::get::demographicQuestion', [$plugin, 'addCustomSchema']);
        Hook::add('Schema::get::demographicResponse', [$plugin, 'addCustomSchema']);
        Hook::add('Schema::get::demographicResponseOption', [$plugin, 'addCustomSchema']);
    }
}
