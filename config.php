<?php
// config/database.php - Configuration spécifique à la base de données

// Paramètres de connexion à la base de données
$host = 'localhost';
$dbname = 'boutique_pro';
$username = 'root';
$password = '';

// Options PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // En mode production, vous pouvez logger l'erreur et afficher un message générique
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    } else {
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }
}
?>