<?php
// config/autoload.php - Chargement des dépendances

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Inclure les fichiers de configuration
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/supabase.php';

// Autoloader pour les classes
spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/',
        __DIR__ . '/../middlewares/'
    ];
    
    $className = str_replace('\\', '/', $className);
    $filename = $className . '.php';
    
    foreach ($paths as $path) {
        $fullPath = $path . $filename;
        if (file_exists($fullPath)) {
            require_once $fullPath;
            return;
        }
    }
});

// Fonctions utilitaires globales
require_once __DIR__ . '/../helpers/functions.php';
?>