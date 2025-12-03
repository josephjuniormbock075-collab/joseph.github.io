<?php
require_once "config.php";
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $panier = $input["panier"] ?? [];
    
    $errors = [];
    
    foreach ($panier as $item) {
        $stmt = $pdo->prepare("SELECT nom, stock FROM produits WHERE id = ?");
        $stmt->execute([$item["id"]]);
        $produit = $stmt->fetch();
        
        if ($produit && $item["quantite"] > $produit["stock"]) {
            $errors[] = "Stock insuffisant pour " . $produit["nom"] . " (${item["quantite"]} demandés, " . $produit["stock"] . " disponibles)";
        }
    }
    
    if (empty($errors)) {
        echo json_encode(["success" => true, "message" => "Stock disponible"]);
    } else {
        echo json_encode(["success" => false, "message" => implode(". ", $errors)]);
    }
}
?>