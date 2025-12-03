<?php
// config/constants.php - Constantes globales

// Mode de développement
define('DEVELOPMENT_MODE', getenv('ENVIRONMENT') === 'development' || !getenv('ENVIRONMENT'));

// URLs de l'application
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost');
define('SITE_NAME', 'OVD BOUTIQUE');

// Chemins des fichiers
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// Configuration Supabase
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://votre-id.supabase.co');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: '');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');

// Configuration JWT pour l'authentification
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'votre-secret-jwt-tres-securise');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 heures en secondes

// Configuration du site
define('ITEMS_PER_PAGE', 12);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Activer le reporting d'erreurs selon l'environnement
if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => !DEVELOPMENT_MODE,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}
?>