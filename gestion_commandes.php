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

// Traitement du changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_statut'])) {
    $commande_id = $_POST['commande_id'];
    $nouveau_statut = $_POST['statut'];
    
    $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $commande_id]);
    
    header('Location: gestion_commandes.php?success=1');
    exit();
}

// Suppression d'une commande
if (isset($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
    $stmt->execute([$_GET['supprimer']]);
    
    header('Location: gestion_commandes.php?success=2');
    exit();
}

// Récupération des commandes avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$filtre_date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$filtre_date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Construction de la requête
$sql = "SELECT c.*, cl.nom, cl.prenom, cl.email 
        FROM commandes c 
        JOIN clients cl ON c.client_id = cl.id 
        WHERE 1=1";
$params = [];

if ($filtre_statut) {
    $sql .= " AND c.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_date_debut) {
    $sql .= " AND DATE(c.date_commande) >= ?";
    $params[] = $filtre_date_debut;
}

if ($filtre_date_fin) {
    $sql .= " AND DATE(c.date_commande) <= ?";
    $params[] = $filtre_date_fin;
}

$sql .= " ORDER BY c.date_commande DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Total pour la pagination
$sql_count = "SELECT COUNT(*) FROM commandes c WHERE 1=1";
$params_count = [];

if ($filtre_statut) {
    $sql_count .= " AND c.statut = ?";
    $params_count[] = $filtre_statut;
}

if ($filtre_date_debut) {
    $sql_count .= " AND DATE(c.date_commande) >= ?";
    $params_count[] = $filtre_date_debut;
}

if ($filtre_date_fin) {
    $sql_count .= " AND DATE(c.date_commande) <= ?";
    $params_count[] = $filtre_date_fin;
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_commandes = $stmt_count->fetchColumn();
$total_pages = ceil($total_commandes / $limit);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes | Admin OVD Boutique</title>
    <style>
        /* Le CSS reste identique */
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

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }

        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }

        .menu-section {
            margin: 1.5rem 0;
            font-size: 0.9rem;
            color: #bdc3c7;
            padding-left: 15px;
        }

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

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
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

        .filtres {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

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

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-block;
        }

        .btn-view { background: #3498db; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-delete { background: #e74c3c; color: white; }

        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            margin-top: 2rem;
        }

        .pagination li {
            margin: 0 5px;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
        }

        .pagination a.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
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

        .statut-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .statut-select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
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
                <li><a href="gestion_commandes.php" class="active">📋 Commandes</a></li>
                <li><a href="gestion_clients.php">👥 Clients</a></li>
                <li><a href="gestion_messages.php">📨 Messages clients</a></li>
                
                <div class="menu-section">COMPTE</div>
                <li><a href="profil_admin.php">👤 Mon profil</a></li>
                <li><a href="logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Gestion des Commandes</h1>
                    <p>Gérez toutes les commandes de votre boutique</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard_admin.php" class="btn btn-primary">← Retour au dashboard</a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    if ($_GET['success'] == 1) {
                        echo "✅ Statut de la commande mis à jour avec succès !";
                    } elseif ($_GET['success'] == 2) {
                        echo "✅ Commande supprimée avec succès !";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="filtres">
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="statut">Statut :</label>
                            <select name="statut" id="statut" class="form-control">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?php echo $filtre_statut == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmee" <?php echo $filtre_statut == 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                <option value="expediee" <?php echo $filtre_statut == 'expediee' ? 'selected' : ''; ?>>Expédiée</option>
                                <option value="livree" <?php echo $filtre_statut == 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                <option value="annulee" <?php echo $filtre_statut == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_debut">Date début :</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?php echo $filtre_date_debut; ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_fin">Date fin :</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?php echo $filtre_date_fin; ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Appliquer les filtres</button>
                    <a href="gestion_commandes.php" class="btn btn-warning">🔄 Réinitialiser</a>
                </form>
            </div>

            <!-- Liste des commandes -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📋 Liste des Commandes (<?php echo $total_commandes; ?>)</h2>
                </div>

                <?php if (count($commandes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($commandes as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($commande['email']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                <td><?php echo number_format($commande['total'], 2, ',', ' '); ?> FCFA</td>
                                <td>
                                    <form method="POST" class="statut-form">
                                        <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                        <select name="statut" class="statut-select" onchange="this.form.submit()">
                                            <option value="en_attente" <?php echo $commande['statut'] == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                            <option value="confirmee" <?php echo $commande['statut'] == 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                            <option value="expediee" <?php echo $commande['statut'] == 'expediee' ? 'selected' : ''; ?>>Expédiée</option>
                                            <option value="livree" <?php echo $commande['statut'] == 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                            <option value="annulee" <?php echo $commande['statut'] == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                        </select>
                                        <input type="hidden" name="changer_statut" value="1">
                                    </form>
                                </td>
                                <td>
                                    <a href="details_commande.php?id=<?php echo $commande['id']; ?>" class="action-btn btn-view">👁️ Voir</a>
                                    <a href="?supprimer=<?php echo $commande['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')">🗑️ Suppr</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li>
                                <a href="?page=<?php echo $i; ?>&statut=<?php echo $filtre_statut; ?>&date_debut=<?php echo $filtre_date_debut; ?>&date_fin=<?php echo $filtre_date_fin; ?>" 
                                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Aucune commande trouvée.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>