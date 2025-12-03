<?php
session_start();

if (!isset($_SESSION['commande_id'])) {
    header('Location: commande.php');
    exit;
}

$commande_id = $_SESSION['commande_id'];
$total_commande = $_SESSION['total_commande'];

// Nettoyage de la session
unset($_SESSION['commande_id']);
unset($_SESSION['total_commande']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
        }
        .confirmation { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            text-align: center; 
            max-width: 500px; 
        }
        .succes { 
            color: #4caf50; 
            font-size: 3rem; 
            margin-bottom: 20px; 
        }
        h1 { 
            color: #764ba2; 
            margin-bottom: 20px; 
        }
        .btn { 
            display: inline-block; 
            padding: 12px 30px; 
            background: linear-gradient(135deg, #66eaa4ff 0%, #764ba2 100%); 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-top: 20px; 
            font-weight: bold; 
        }
    </style>
</head>
<body>
    <div class="confirmation">
        <div class="succes">✓</div>
        <h1>Commande Confirmée !</h1>
        <p><strong>Numéro de commande :</strong> #<?= htmlspecialchars($commande_id) ?></p>
        <p><strong>Montant total :</strong> <?= number_format($total_commande, 2, ',', ' ') ?> €</p>
        <p>Merci pour votre commande ! Vous recevrez un email de confirmation sous peu.</p>
        <a href="index.php" class="btn">Retour à l'accueil</a>
    </div>
</body>
</html>