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

// Récupérer toutes les commandes du client avec les détails
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(dc.id) as nb_produits,
           GROUP_CONCAT(p.nom SEPARATOR ', ') as produits
    FROM commandes c
    LEFT JOIN details_commande dc ON c.id = dc.commande_id
    LEFT JOIN produits p ON dc.produit_id = p.id
    WHERE c.client_id = ?
    GROUP BY c.id
    ORDER BY c.date_commande DESC
");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes | OVD Boutique</title>
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

        /* Sidebar (identique au dashboard) */
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
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Commandes Table */
        .commandes-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-confirmee { background: #d1ecf1; color: #0c5460; }
        .status-expediee { background: #d4edda; color: #155724; }
        .status-livree { background: #e2e3e5; color: #383d41; }
        .status-annulee { background: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
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

            table {
                display: block;
                overflow-x: auto;
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
                <li><a href="mes_commandes.php" class="active">📦 Mes commandes</a></li>
                <li><a href="wishlist.php">❤️ Ma liste de souhaits</a></li>
                <li><a href="adresses.php">🏠 Mes adresses</a></li>
                <li><a href="parametres.php">⚙️ Paramètres</a></li>
                <li><a href="logout.php">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Mes Commandes</h1>
                    <p>Consultez l'historique de vos commandes</p>
                </div>
                <a href="commande.php" class="btn">Nouvelle commande</a>
            </div>

            <div class="commandes-section">
                <?php if (count($commandes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Produits</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($commandes as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                <td><?php echo $commande['nb_produits']; ?> produit(s)</td>
                                <td><?php echo number_format($commande['total'], 2, ',', ' '); ?> FCFA</td>
                                <td>
                                    <span class="status-badge status-<?php echo $commande['statut']; ?>">
                                        <?php 
                                        $statuts = [
                                            'en_attente' => 'En attente',
                                            'confirmee' => 'Confirmée',
                                            'expediee' => 'Expédiée',
                                            'livree' => 'Livrée',
                                            'annulee' => 'Annulée'
                                        ];
                                        echo $statuts[$commande['statut']];
                                        ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="details_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-small">Détails</a>
                                    <?php if ($commande['statut'] === 'en_attente'): ?>
                                        <a href="annuler_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-small btn-secondary" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')">Annuler</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div>📦</div>
                        <h3>Aucune commande</h3>
                        <p>Vous n'avez pas encore passé de commande.</p>
                        <a href="commande.php" class="btn">Passer votre première commande</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>