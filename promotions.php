<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// Vérifier si la table promotions existe, sinon la créer
$pdo->exec("
    CREATE TABLE IF NOT EXISTS promotions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        produit_id INT NOT NULL,
        pourcentage_remise DECIMAL(5,2) NOT NULL,
        prix_promotion DECIMAL(10,2) NOT NULL,
        date_debut DATETIME NOT NULL,
        date_fin DATETIME NOT NULL,
        statut ENUM('active', 'inactive', 'expiree') DEFAULT 'active',
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
    )
");

// Récupération des promotions avec informations des produits
$promotions = $pdo->query("
    SELECT p.*, pr.nom as produit_nom, pr.prix as prix_original, pr.image as produit_image,
           pr.stock, cat.nom as categorie_nom
    FROM promotions p
    JOIN produits pr ON p.produit_id = pr.id
    LEFT JOIN categories cat ON pr.categorie_id = cat.id
    ORDER BY p.date_creation DESC
")->fetchAll();

// Récupération des produits disponibles pour les promotions
$produits = $pdo->query("
    SELECT id, nom, prix, stock 
    FROM produits 
    WHERE statut = 'disponible' AND stock > 0
    ORDER BY nom
")->fetchAll();

// Traitement de l'ajout d'une promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_promotion'])) {
    $produit_id = $_POST['produit_id'];
    $pourcentage_remise = $_POST['pourcentage_remise'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    
    // Calcul du prix promotionnel
    $produit = $pdo->query("SELECT prix FROM produits WHERE id = $produit_id")->fetch();
    $prix_original = $produit['prix'];
    $prix_promotion = $prix_original * (1 - ($pourcentage_remise / 100));
    
    $stmt = $pdo->prepare("
        INSERT INTO promotions (produit_id, pourcentage_remise, prix_promotion, date_debut, date_fin)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$produit_id, $pourcentage_remise, $prix_promotion, $date_debut, $date_fin]);
    
    header('Location: promotions.php?success=1');
    exit();
}

// Traitement de la modification d'une promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_promotion'])) {
    $promotion_id = $_POST['promotion_id'];
    $pourcentage_remise = $_POST['pourcentage_remise'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    
    // Recalcul du prix promotionnel
    $promotion = $pdo->query("SELECT produit_id FROM promotions WHERE id = $promotion_id")->fetch();
    $produit = $pdo->query("SELECT prix FROM produits WHERE id = {$promotion['produit_id']}")->fetch();
    $prix_promotion = $produit['prix'] * (1 - ($pourcentage_remise / 100));
    
    $stmt = $pdo->prepare("
        UPDATE promotions 
        SET pourcentage_remise = ?, prix_promotion = ?, date_debut = ?, date_fin = ?
        WHERE id = ?
    ");
    $stmt->execute([$pourcentage_remise, $prix_promotion, $date_debut, $date_fin, $promotion_id]);
    
    header('Location: promotions.php?success=2');
    exit();
}

// Traitement de la suppression d'une promotion
if (isset($_GET['supprimer'])) {
    $promotion_id = $_GET['supprimer'];
    
    $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
    $stmt->execute([$promotion_id]);
    
    header('Location: promotions.php?success=3');
    exit();
}

// Traitement du changement de statut
if (isset($_GET['changer_statut'])) {
    $promotion_id = $_GET['changer_statut'];
    $nouveau_statut = $_GET['statut'];
    
    $stmt = $pdo->prepare("UPDATE promotions SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $promotion_id]);
    
    header('Location: promotions.php');
    exit();
}

// Mise à jour automatique du statut des promotions expirées
$pdo->exec("UPDATE promotions SET statut = 'expiree' WHERE date_fin < NOW() AND statut = 'active'");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Promotions | Admin OVD Boutique</title>
    <style>
        .promo-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .promo-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .promo-body {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .produit-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }

        .promo-badge {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .prix-container {
            text-align: right;
        }

        .prix-original {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }

        .prix-promo {
            color: #e74c3c;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .date-promo {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .form-promotion {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .stats-promotions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card-promo {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid;
        }

        .stat-active { border-color: #27ae60; }
        .stat-inactive { border-color: #95a5a6; }
        .stat-expired { border-color: #e74c3c; }

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

        /* Sidebar Admin */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #3498db;
        }

        .admin-badge {
            background: #e74c3c;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
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
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }

        .menu-section {
            margin: 1.5rem 0;
            font-size: 0.9rem;
            color: #bdc3c7;
            padding-left: 15px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
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

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Stats Grid Admin */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid;
        }

        .stat-card.clients { border-color: #3498db; }
        .stat-card.produits { border-color: #27ae60; }
        .stat-card.commandes { border-color: #f39c12; }
        .stat-card.ca { border-color: #9b59b6; }
        .stat-card.messages { border-color: #e74c3c; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-card.clients .stat-number { color: #3498db; }
        .stat-card.produits .stat-number { color: #27ae60; }
        .stat-card.commandes .stat-number { color: #f39c12; }
        .stat-card.ca .stat-number { color: #9b59b6; }
        .stat-card.messages .stat-number { color: #e74c3c; }

        /* Tableaux */
        .dashboard-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-success { background: #d1ecf1; color: #0c5460; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #cce7ff; color: #004085; }

        .message-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .type-question { background: #cce7ff; color: #004085; }
        .type-probleme { background: #f8d7da; color: #721c24; }
        .type-satisfaction { background: #d4edda; color: #155724; }
        .type-reclamation { background: #fff3cd; color: #856404; }

        .message-status {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .status-non_lu { background: #f8d7da; color: #721c24; }
        .status-lu { background: #d1ecf1; color: #0c5460; }
        .status-repondu { background: #d4edda; color: #155724; }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .btn-view { background: #3498db; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-success { background: #27ae60; color: white; }

        /* Modal pour répondre */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .message-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Admin -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Dashboard</h2>
                <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom']); ?></p>
                <span class="admin-badge">Administrateur</span>
            </div>
            
            <ul class="sidebar-menu">
                <div class="menu-section">PRINCIPAL</div>
                <li><a href="dashboard_admin.php">📊 Tableau de bord</a></li>
                <li><a href="statistiques.php">📈 Statistiques</a></li>
                
                <div class="menu-section">GESTION</div>
                <li><a href="gestion_produits.php">📦 Produits</a></li>
                <li><a href="gestion_categories.php">🗂️ Catégories</a></li>
                <li><a href="gestion_commandes.php">📋 Commandes</a></li>
                <li><a href="gestion_clients.php">👥 Clients</a></li>
                <li><a href="gestion_messages.php">📨 Messages clients</a></li>
                
                <div class="menu-section">CONTENU</div>
                <li><a href="promotions.php" class="active">🎯 Promotions</a></li>
                <li><a href="avis.php">⭐ Avis clients</a></li>
                <li><a href="parametres_site.php">⚙️ Paramètres</a></li>
                
                <div class="menu-section">COMPTE</div>
                <li><a href="profil_admin.php">👤 Mon profil</a></li>
                <li><a href="logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Gestion des Promotions</h1>
                    <p>Créez et gérez les promotions de vos produits</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard_admin.php" class="btn btn-primary">← Retour au dashboard</a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch($_GET['success']) {
                        case 1: echo '✅ Promotion ajoutée avec succès !'; break;
                        case 2: echo '✅ Promotion modifiée avec succès !'; break;
                        case 3: echo '✅ Promotion supprimée avec succès !'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques des promotions -->
            <?php
            $stats_promotions = $pdo->query("
                SELECT statut, COUNT(*) as count 
                FROM promotions 
                GROUP BY statut
            ")->fetchAll(PDO::FETCH_KEY_PAIR);
            ?>
            <div class="stats-promotions">
                <div class="stat-card-promo stat-active">
                    <div class="stat-number"><?php echo $stats_promotions['active'] ?? 0; ?></div>
                    <div class="stat-label">Promotions actives</div>
                </div>
                <div class="stat-card-promo stat-inactive">
                    <div class="stat-number"><?php echo $stats_promotions['inactive'] ?? 0; ?></div>
                    <div class="stat-label">Promotions inactives</div>
                </div>
                <div class="stat-card-promo stat-expired">
                    <div class="stat-number"><?php echo $stats_promotions['expiree'] ?? 0; ?></div>
                    <div class="stat-label">Promotions expirées</div>
                </div>
            </div>

            <!-- Formulaire d'ajout de promotion -->
            <div class="form-promotion">
                <h3>➕ Ajouter une nouvelle promotion</h3>
                <form method="POST" class="form-grid">
                    <div class="form-group">
                        <label for="produit_id">Produit :</label>
                        <select name="produit_id" id="produit_id" class="form-control" required>
                            <option value="">Sélectionnez un produit</option>
                            <?php foreach($produits as $produit): ?>
                                <option value="<?php echo $produit['id']; ?>">
                                    <?php echo htmlspecialchars($produit['nom']); ?> - <?php echo number_format($produit['prix'], 2, ',', ' '); ?> FCFA
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pourcentage_remise">Remise (%) :</label>
                        <input type="number" name="pourcentage_remise" id="pourcentage_remise" 
                               class="form-control" min="1" max="90" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="date_debut">Date de début :</label>
                        <input type="datetime-local" name="date_debut" id="date_debut" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date de fin :</label>
                        <input type="datetime-local" name="date_fin" id="date_fin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="ajouter_promotion" class="btn btn-success">➕ Créer la promotion</button>
                    </div>
                </form>
            </div>

            <!-- Liste des promotions -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>🎯 Promotions en cours</h2>
                </div>

                <?php if (count($promotions) > 0): ?>
                    <?php foreach($promotions as $promotion): ?>
                    <div class="promo-card">
                        <div class="promo-header">
                            <div>
                                <h4><?php echo htmlspecialchars($promotion['produit_nom']); ?></h4>
                                <small>Catégorie : <?php echo htmlspecialchars($promotion['categorie_nom']); ?></small>
                            </div>
                            <span class="promo-badge">-<?php echo $promotion['pourcentage_remise']; ?>%</span>
                        </div>
                        <div class="promo-body">
                            <?php if ($promotion['produit_image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($promotion['produit_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($promotion['produit_nom']); ?>" class="produit-image">
                            <?php else: ?>
                                <div class="produit-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                    <span>📷</span>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <h4><?php echo htmlspecialchars($promotion['produit_nom']); ?></h4>
                                <p>Stock disponible : <?php echo $promotion['stock']; ?> unités</p>
                                <div class="date-promo">
                                    <strong>Période :</strong> 
                                    <?php echo date('d/m/Y H:i', strtotime($promotion['date_debut'])); ?> - 
                                    <?php echo date('d/m/Y H:i', strtotime($promotion['date_fin'])); ?>
                                </div>
                                <span class="promotion-status status-<?php echo $promotion['statut']; ?>">
                                    <?php 
                                    $statuts_promo = [
                                        'active' => '🟢 Active',
                                        'inactive' => '⚪ Inactive',
                                        'expiree' => '🔴 Expirée'
                                    ];
                                    echo $statuts_promo[$promotion['statut']];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="prix-container">
                                <div class="prix-original">
                                    <?php echo number_format($promotion['prix_original'], 2, ',', ' '); ?> FCFA
                                </div>
                                <div class="prix-promo">
                                    <?php echo number_format($promotion['prix_promotion'], 2, ',', ' '); ?> FCFA
                                </div>
                                <div class="actions-promo" style="margin-top: 1rem;">
                                    <?php if ($promotion['statut'] == 'active'): ?>
                                        <a href="?changer_statut=<?php echo $promotion['id']; ?>&statut=inactive" 
                                           class="action-btn btn-warning btn-sm">⏸️ Désactiver</a>
                                    <?php elseif ($promotion['statut'] == 'inactive'): ?>
                                        <a href="?changer_statut=<?php echo $promotion['id']; ?>&statut=active" 
                                           class="action-btn btn-success btn-sm">▶️ Activer</a>
                                    <?php endif; ?>
                                    
                                    <button class="action-btn btn-primary btn-sm" 
                                            onclick="modifierPromotion(<?php echo $promotion['id']; ?>)">✏️ Modifier</button>
                                    
                                    <a href="?supprimer=<?php echo $promotion['id']; ?>" 
                                       class="action-btn btn-delete btn-sm"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette promotion ?')">🗑️ Supprimer</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Aucune promotion créée pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de modification de promotion -->
    <div id="modifierPromotionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fermerModifierModal()">&times;</span>
            <h3>Modifier la promotion</h3>
            <form id="modifierPromotionForm" method="POST">
                <input type="hidden" name="promotion_id" id="modifier_promotion_id">
                <div class="form-group">
                    <label for="modifier_pourcentage_remise">Remise (%) :</label>
                    <input type="number" name="pourcentage_remise" id="modifier_pourcentage_remise" 
                           class="form-control" min="1" max="90" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="modifier_date_debut">Date de début :</label>
                    <input type="datetime-local" name="date_debut" id="modifier_date_debut" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="modifier_date_fin">Date de fin :</label>
                    <input type="datetime-local" name="date_fin" id="modifier_date_fin" class="form-control" required>
                </div>
                <button type="submit" name="modifier_promotion" class="btn btn-success">💾 Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <script>
        function modifierPromotion(promotionId) {
            fetch('get_promotion_details.php?id=' + promotionId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modifier_promotion_id').value = promotionId;
                    document.getElementById('modifier_pourcentage_remise').value = data.pourcentage_remise;
                    document.getElementById('modifier_date_debut').value = data.date_debut.replace(' ', 'T');
                    document.getElementById('modifier_date_fin').value = data.date_fin.replace(' ', 'T');
                    document.getElementById('modifierPromotionModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement de la promotion');
                });
        }

        function fermerModifierModal() {
            document.getElementById('modifierPromotionModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modifierPromotionModal');
            if (event.target == modal) {
                fermerModifierModal();
            }
        }

        // Calcul automatique de la date de fin (1 semaine par défaut)
        document.getElementById('date_debut').addEventListener('change', function() {
            const dateDebut = new Date(this.value);
            const dateFin = new Date(dateDebut.getTime() + 7 * 24 * 60 * 60 * 1000); // +7 jours
            document.getElementById('date_fin').value = dateFin.toISOString().slice(0, 16);
        });
    </script>
</body>
</html>