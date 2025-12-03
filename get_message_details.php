
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Accès non autorisé');
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
    http_response_code(500);
    exit('Erreur de connexion');
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT m.*, cl.nom, cl.prenom, cl.email 
        FROM messages m 
        JOIN clients cl ON m.client_id = cl.id 
        WHERE m.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($message) {
        header('Content-Type: application/json');
        echo json_encode($message);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Message non trouvé']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
}
