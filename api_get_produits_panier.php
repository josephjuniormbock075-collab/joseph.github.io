<?php
require_once "config.php";
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $ids = $input["ids"] ?? [];
    
    if (!empty($ids)) {
        $placeholders = str_repeat("?,", count($ids) - 1) . "?";
        $stmt = $pdo->prepare("SELECT id, nom, prix, image, stock FROM produits WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $produits = $stmt->fetchAll();
        
        echo json_encode($produits);
    } else {
        echo json_encode([]);
    }
}
?>