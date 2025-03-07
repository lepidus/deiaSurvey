<?php

spl_autoload_register(function ($class) {
    $namespaceMap = [
        'APP' => dirname(__DIR__, 3) . '/classes',
        'PKP' => dirname(__DIR__, 3) . '/lib/pkp/classes',
        'APP\plugins\generic\demographicData' => dirname(__DIR__, 3) . '/plugins/generic/demographicData',
    ];

    $classPath = str_replace('\\', '/', $class);

    foreach ($namespaceMap as $namespace => $baseDir) {
        $namespace = str_replace('\\', '/', $namespace);
        if (strpos($classPath, $namespace) === 0) {
            $relativePath = substr($classPath, strlen($namespace));
            $file = $baseDir . $relativePath . '.php';

            if (!file_exists($file)) {
                $file = $baseDir . $relativePath . '.inc.php';
            }

            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});
