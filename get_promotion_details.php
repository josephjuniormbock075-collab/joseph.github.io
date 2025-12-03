<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// Connexion à la base de données
$host = 'localhost';
$dbname = 'boutique_pro';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Erreur de connexion']));
}

if (isset($_GET['id'])) {
    $promotion_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$promotion_id]);
    $promotion = $stmt->fetch();
    
    if ($promotion) {
        header('Content-Type: application/json');
        echo json_encode($promotion);
    } else {
        echo json_encode(['error' => 'Promotion non trouvée']);
    }
}
?>