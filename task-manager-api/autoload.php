<?php

declare(strict_types=1);

/**
 * PRS-4 Autoloader
 * 
 * Carrega automaticamente as classes seguindo o padrão PRS-4
 * Namespace App\ mapeia para o diretório src/
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

if (file_exists(__DIR__ . '/src/Helpers/functions.php')) {
    require __DIR__ . '/src/Helpers/functions.php';
}
