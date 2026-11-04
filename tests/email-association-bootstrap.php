<?php

// Run from the OJS root, including when the plugin is installed through a symlink.
require getcwd() . '/lib/pkp/tests/phpunit-bootstrap.php';
import('classes.core.PageRouter');
$application = Application::get();
$request = $application->getRequest();
$dispatcher = $application->getDispatcher();
$router = new PageRouter();
$router->setApplication($application);
$router->setDispatcher($dispatcher);
$request->setRouter($router);
$request->setDispatcher($dispatcher);
AppLocale::initialize($request);
