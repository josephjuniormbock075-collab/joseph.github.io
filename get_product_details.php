<?php
// get_product_details.php

session_start();
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'boutique_pro';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (isset($_GET['id'])) {
        $productId = (int)$_GET['id'];
        
        $stmt = $pdo->prepare("SELECT p.*, c.nom as categorie_nom 
                              FROM produits p 
                              LEFT JOIN categories c ON p.categorie_id = c.id 
                              WHERE p.id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Produit non trouvé'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID produit non spécifié'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>