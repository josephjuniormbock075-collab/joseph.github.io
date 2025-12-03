<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
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
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupérer les informations du client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$client = $stmt->fetch();

// Vérifier si la table wishlist existe, sinon la créer
$pdo->exec("
    CREATE TABLE IF NOT EXISTS wishlist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        produit_id INT NOT NULL,
        date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (client_id, produit_id)
    )
");

// Ajouter un produit à la wishlist
if (isset($_GET['ajouter']) && is_numeric($_GET['ajouter'])) {
    $produit_id = $_GET['ajouter'];
    
    // Vérifier si le produit existe
    $stmt = $pdo->prepare("SELECT id FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    
    if ($stmt->rowCount() > 0) {
        // Vérifier si le produit n'est pas déjà dans la wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE client_id = ? AND produit_id = ?");
        $stmt->execute([$_SESSION['user_id'], $produit_id]);
        
        if ($stmt->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO wishlist (client_id, produit_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $produit_id]);
            $message = "Produit ajouté à votre liste de souhaits!";
            $message_type = 'success';
        } else {
            $message = "Ce produit est déjà dans votre liste de souhaits.";
            $message_type = 'info';
        }
    } else {
        $message = "Produit non trouvé.";
        $message_type = 'error';
    }
}

// Retirer un produit de la wishlist
if (isset($_GET['retirer']) && is_numeric($_GET['retirer'])) {
    $produit_id = $_GET['retirer'];
    
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE client_id = ? AND produit_id = ?");
    $stmt->execute([$_SESSION['user_id'], $produit_id]);
    
    $message = "Produit retiré de votre liste de souhaits.";
    $message_type = 'success';
}

// Vider toute la wishlist
if (isset($_POST['vider_wishlist'])) {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE client_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    $message = "Votre liste de souhaits a été vidée.";
    $message_type = 'success';
}

// Récupérer les produits de la wishlist
$stmt = $pdo->prepare("
    SELECT p.*, w.date_ajout 
    FROM wishlist w 
    JOIN produits p ON w.produit_id = p.id 
    WHERE w.client_id = ? 
    ORDER BY w.date_ajout DESC
");
$stmt->execute([$_SESSION['user_id']]);
$wishlist = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Liste de Souhaits | OVD Boutique</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.3);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-message h1 {
            color: #764ba2;
            margin-bottom: 0.5rem;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        /* Wishlist Grid */
        .wishlist-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .wishlist-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .wishlist-item {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: #666;
            font-size: 3rem;
        }

        .product-info h3 {
            color: #764ba2;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #667eea;
            margin: 0.5rem 0;
        }

        .product-stock {
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .stock-disponible {
            color: #28a745;
        }

        .stock-indisponible {
            color: #dc3545;
        }

        .wishlist-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-small {
            padding: 8px 12px;
            font-size: 0.8rem;
            flex: 1;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .clear-form {
            display: inline;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }

            .wishlist-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Tableau de Bord</h2>
                <p>Bienvenue, <?php echo htmlspecialchars($client['prenom']); ?>!</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard_client.php">📊 Tableau de bord</a></li>
                <li><a href="profil.php">👤 Mon profil</a></li>
                <li><a href="mes_commandes.php">📦 Mes commandes</a></li>
                <li><a href="wishlist.php" class="active">❤️ Ma liste de souhaits</a></li>
                <li><a href="adresses.php">🏠 Mes adresses</a></li>
                <li><a href="parametres.php">⚙️ Paramètres</a></li>
                <li><a href="logout.php">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Ma Liste de Souhaits</h1>
                    <p>Vos produits favoris</p>
                </div>
                <a href="commande.php" class="btn">🛒 Voir les produits</a>
            </div>

            <?php if (isset($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="wishlist-section">
                <div class="wishlist-header">
                    <h2>Vos produits favoris (<?php echo count($wishlist); ?>)</h2>
                    <?php if (count($wishlist) > 0): ?>
                        <form method="POST" class="clear-form">
                            <button type="submit" name="vider_wishlist" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir vider toute votre liste de souhaits ?')">
                                🗑️ Vider la liste
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (count($wishlist) > 0): ?>
                    <div class="wishlist-grid">
                        <?php foreach($wishlist as $produit): ?>
                        <div class="wishlist-item">
                            <div class="product-image">
                                <?php 
                                $initial = strtoupper(substr($produit['nom'], 0, 1));
                                echo $initial; 
                                ?>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($produit['nom']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(substr($produit['description'], 0, 100)); ?>...</p>
                                <p class="product-price"><?php echo number_format($produit['prix'], 2, ',', ' '); ?> FCFA</p>
                                <p class="product-stock <?php echo $produit['stock'] > 0 ? 'stock-disponible' : 'stock-indisponible'; ?>">
                                    <?php echo $produit['stock'] > 0 ? '✓ En stock' : '✗ Rupture de stock'; ?>
                                </p>
                                <p class="date-ajout">Ajouté le <?php echo date('d/m/Y', strtotime($produit['date_ajout'])); ?></p>
                            </div>
                            <div class="wishlist-actions">
                                <?php if ($produit['stock'] > 0): ?>
                                    <a href="commande.php?produit=<?php echo $produit['id']; ?>" class="btn btn-success btn-small">🛒 Acheter</a>
                                <?php endif; ?>
                                <a href="wishlist.php?retirer=<?php echo $produit['id']; ?>" class="btn btn-danger btn-small">🗑️ Retirer</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div>❤️</div>
                        <h3>Votre liste de souhaits est vide</h3>
                        <p>Ajoutez des produits que vous aimez pour les retrouver facilement plus tard.</p>
                        <a href="commande.php" class="btn">Découvrir nos produits</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>