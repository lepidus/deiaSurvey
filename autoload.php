<?php

spl_autoload_register(function ($class) {
    $baseSysDir = defined('BASE_SYS_DIR') ? BASE_SYS_DIR : dirname(__DIR__, 3);

    $namespaceMap = [
        'APP\plugins\generic\deiaSurvey' => $baseSysDir . '/plugins/generic/deiaSurvey',
        'APP' => $baseSysDir . '/classes',
        'PKP' => $baseSysDir . '/lib/pkp/classes',
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
