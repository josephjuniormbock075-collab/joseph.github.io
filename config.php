<?php
// config/database.php - Configuration Supabase PostgreSQL

// Récupérer les variables d'environnement (pour Netlify ou local)
$supabaseUrl = getenv('SUPABASE_URL') ?: 'https://votre-id.supabase.co';
$supabaseKey = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: 'votre-service-role-key';
$supabaseDb = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$supabaseUser = getenv('SUPABASE_DB_USER') ?: 'postgres';
$supabasePass = getenv('SUPABASE_DB_PASSWORD') ?: 'votre-mot-de-passe';
$supabaseHost = getenv('SUPABASE_DB_HOST') ?: 'db.votre-id.supabase.co';
$supabasePort = getenv('SUPABASE_DB_PORT') ?: 5432;

// Extraire l'host de l'URL Supabase si nécessaire
if (empty($supabaseHost) && !empty($supabaseUrl)) {
    $parsedUrl = parse_url($supabaseUrl);
    if (isset($parsedUrl['host'])) {
        // Supabase utilise db.[project-ref].supabase.co pour la DB directe
        $supabaseHost = 'db.' . explode('.', $parsedUrl['host'])[0] . '.supabase.co';
    }
}

// Construction du DSN PostgreSQL
$dsn = "pgsql:host=$supabaseHost;port=$supabasePort;dbname=$supabaseDb";

// Options PDO pour PostgreSQL
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_TIMEOUT => 30,
];

try {
    // Connexion à la base de données PostgreSQL
    $pdo = new PDO($dsn, $supabaseUser, $supabasePass, $options);
    
    // Définir le schéma de recherche (optionnel)
    $pdo->exec("SET search_path TO public");
    
} catch (PDOException $e) {
    // Journalisation de l'erreur
    error_log("Erreur de connexion Supabase: " . $e->getMessage());
    
    // Gestion d'erreur selon l'environnement
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        die("Erreur de connexion Supabase : " . $e->getMessage() . 
            "<br>DSN: $dsn<br>User: $supabaseUser");
    } else {
        // En production, afficher un message générique
        header('HTTP/1.1 500 Internal Server Error');
        die(json_encode([
            'error' => true,
            'message' => 'Une erreur de connexion est survenue. Veuillez réessayer plus tard.'
        ]));
    }
}

// Fonction pour fermer la connexion (optionnel)
function closeDatabaseConnection() {
    global $pdo;
    $pdo = null;
}

// Enregistrer la fonction de fermeture
register_shutdown_function('closeDatabaseConnection');
?>
