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

// Changement de statut client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_statut'])) {
    $client_id = $_POST['client_id'];
    $nouveau_statut = $_POST['statut'];
    
    $stmt = $pdo->prepare("UPDATE clients SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $client_id]);
    
    header('Location: gestion_clients.php?success=1');
    exit();
}

// Suppression d'un client
if (isset($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$_GET['supprimer']]);
    
    header('Location: gestion_clients.php?success=2');
    exit();
}

// Récupération des clients avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$filtre_recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';

// Construction de la requête
$sql = "SELECT * FROM clients WHERE 1=1";
$params = [];

if ($filtre_statut) {
    $sql .= " AND statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_recherche) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $search_term = "%$filtre_recherche%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY date_inscription DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Total pour la pagination
$sql_count = "SELECT COUNT(*) FROM clients WHERE 1=1";
$params_count = [];

if ($filtre_statut) {
    $sql_count .= " AND statut = ?";
    $params_count[] = $filtre_statut;
}

if ($filtre_recherche) {
    $sql_count .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $search_term = "%$filtre_recherche%";
    $params_count[] = $search_term;
    $params_count[] = $search_term;
    $params_count[] = $search_term;
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_clients = $stmt_count->fetchColumn();
$total_pages = ceil($total_clients / $limit);

// Statistiques clients
$stats_clients = [
    'total' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'actifs' => $pdo->query("SELECT COUNT(*) FROM clients WHERE statut = 'actif'")->fetchColumn(),
    'inactifs' => $pdo->query("SELECT COUNT(*) FROM clients WHERE statut = 'inactif'")->fetchColumn(),
    'nouveaux_mois' => $pdo->query("SELECT COUNT(*) FROM clients WHERE MONTH(date_inscription) = MONTH(CURRENT_DATE()) AND YEAR(date_inscription) = YEAR(CURRENT_DATE())")->fetchColumn(),
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients | Admin OVD Boutique</title>
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

        .stat-card.total { border-color: #3498db; }
        .stat-card.actifs { border-color: #27ae60; }
        .stat-card.inactifs { border-color: #e74c3c; }
        .stat-card.nouveaux { border-color: #f39c12; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-card.total .stat-number { color: #3498db; }
        .stat-card.actifs .stat-number { color: #27ae60; }
        .stat-card.inactifs .stat-number { color: #e74c3c; }
        .stat-card.nouveaux .stat-number { color: #f39c12; }

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

        .badge-success { background: #d1ecf1; color: #0c5460; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
                <li><a href="gestion_clients.php" class="active">👥 Clients</a></li>
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
                    <h1>Gestion des Clients</h1>
                    <p>Gérez tous les clients de votre boutique</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard_admin.php" class="btn btn-primary">← Retour au dashboard</a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    if ($_GET['success'] == 1) {
                        echo "✅ Statut du client mis à jour avec succès !";
                    } elseif ($_GET['success'] == 2) {
                        echo "✅ Client supprimé avec succès !";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques clients -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $stats_clients['total']; ?></div>
                    <div class="stat-label">Clients total</div>
                </div>
                <div class="stat-card actifs">
                    <div class="stat-number"><?php echo $stats_clients['actifs']; ?></div>
                    <div class="stat-label">Clients actifs</div>
                </div>
                <div class="stat-card inactifs">
                    <div class="stat-number"><?php echo $stats_clients['inactifs']; ?></div>
                    <div class="stat-label">Clients inactifs</div>
                </div>
                <div class="stat-card nouveaux">
                    <div class="stat-number"><?php echo $stats_clients['nouveaux_mois']; ?></div>
                    <div class="stat-label">Nouveaux ce mois</div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="filtres">
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="statut">Statut :</label>
                            <select name="statut" id="statut" class="form-control">
                                <option value="">Tous les statuts</option>
                                <option value="actif" <?php echo $filtre_statut == 'actif' ? 'selected' : ''; ?>>Actif</option>
                                <option value="inactif" <?php echo $filtre_statut == 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="recherche">Recherche :</label>
                            <input type="text" name="recherche" id="recherche" class="form-control" 
                                   placeholder="Nom, prénom ou email..." value="<?php echo htmlspecialchars($filtre_recherche); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Appliquer les filtres</button>
                    <a href="gestion_clients.php" class="btn btn-warning">🔄 Réinitialiser</a>
                </form>
            </div>

            <!-- Liste des clients -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>👥 Liste des Clients (<?php echo $total_clients; ?>)</h2>
                </div>

                <?php if (count($clients) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Date d'inscription</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clients as $client): ?>
                            <tr>
                                <td>#<?php echo $client['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['telephone'] ?? 'Non renseigné'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($client['date_inscription'])); ?></td>
                                <td>
                                    <form method="POST" class="statut-form">
                                        <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                        <select name="statut" class="statut-select" onchange="this.form.submit()">
                                            <option value="actif" <?php echo $client['statut'] == 'actif' ? 'selected' : ''; ?>>Actif</option>
                                            <option value="inactif" <?php echo $client['statut'] == 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                        </select>
                                        <input type="hidden" name="changer_statut" value="1">
                                    </form>
                                </td>
                                <td>
                                    <a href="details_client.php?id=<?php echo $client['id']; ?>" class="action-btn btn-view">👁️ Voir</a>
                                    <a href="?supprimer=<?php echo $client['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.')">🗑️ Suppr</a>
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
                                <a href="?page=<?php echo $i; ?>&statut=<?php echo $filtre_statut; ?>&recherche=<?php echo urlencode($filtre_recherche); ?>" 
                                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Aucun client trouvé.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>