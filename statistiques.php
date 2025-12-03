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

// Statistiques générales
$stats = [
    'total_clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'total_produits' => $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn(),
    'total_commandes' => $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
    'chiffre_affaires' => $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee'")->fetchColumn(),
    'messages_non_lus' => $pdo->query("SELECT COUNT(*) FROM messages WHERE statut = 'non_lu'")->fetchColumn(),
];

// Commandes par statut
$commandes_par_statut = $pdo->query("
    SELECT statut, COUNT(*) as nombre 
    FROM commandes 
    GROUP BY statut
")->fetchAll();

// Produits par catégorie
$produits_par_categorie = $pdo->query("
    SELECT c.nom, COUNT(p.id) as nombre_produits
    FROM categories c
    LEFT JOIN produits p ON c.id = p.categorie_id
    GROUP BY c.id, c.nom
")->fetchAll();

// Chiffre d'affaires mensuel (6 derniers mois)
$ca_mensuel = $pdo->query("
    SELECT 
        DATE_FORMAT(date_commande, '%Y-%m') as mois,
        SUM(total) as chiffre_affaires
    FROM commandes 
    WHERE statut != 'annulee' 
    AND date_commande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
    ORDER BY mois DESC
")->fetchAll();

// Top 5 produits les plus vendus
$top_produits = $pdo->query("
    SELECT 
        p.nom,
        SUM(dc.quantite) as quantite_vendue,
        SUM(dc.quantite * dc.prix_unitaire) as chiffre_affaires
    FROM details_commande dc
    JOIN produits p ON dc.produit_id = p.id
    JOIN commandes c ON dc.commande_id = c.id
    WHERE c.statut != 'annulee'
    GROUP BY p.id, p.nom
    ORDER BY quantite_vendue DESC
    LIMIT 5
")->fetchAll();

// Clients les plus actifs
$clients_actifs = $pdo->query("
    SELECT 
        c.nom,
        c.prenom,
        COUNT(co.id) as nombre_commandes,
        SUM(co.total) as total_depense
    FROM clients c
    LEFT JOIN commandes co ON c.id = co.client_id
    WHERE co.statut != 'annulee'
    GROUP BY c.id, c.nom, c.prenom
    ORDER BY total_depense DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques | OVD Boutique</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Stats Grid */
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

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-title {
            margin-bottom: 1rem;
            color: #2c3e50;
            font-size: 1.2rem;
        }

        /* Tables */
        .dashboard-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-header {
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

        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

        @media (max-width: 1024px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .charts-section {
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
                <li><a href="statistiques.php" class="active">📈 Statistiques</a></li>
                
                <div class="menu-section">GESTION</div>
                <li><a href="gestion_produits.php">📦 Produits</a></li>
                <li><a href="gestion_categories.php">🗂️ Catégories</a></li>
                <li><a href="gestion_commandes.php">📋 Commandes</a></li>
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
                    <h1>📈 Statistiques Détaillées</h1>
                    <p>Analyse des performances de votre boutique</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard_admin.php" class="btn btn-primary">← Retour au dashboard</a>
                </div>
            </div>

            <!-- Statistiques générales -->
            <div class="stats-grid">
                <div class="stat-card clients">
                    <div class="stat-number"><?php echo $stats['total_clients']; ?></div>
                    <div class="stat-label">Clients inscrits</div>
                </div>
                <div class="stat-card produits">
                    <div class="stat-number"><?php echo $stats['total_produits']; ?></div>
                    <div class="stat-label">Produits en stock</div>
                </div>
                <div class="stat-card commandes">
                    <div class="stat-number"><?php echo $stats['total_commandes']; ?></div>
                    <div class="stat-label">Commandes totales</div>
                </div>
                <div class="stat-card ca">
                    <div class="stat-number"><?php echo number_format($stats['chiffre_affaires'], 0, ',', ' '); ?> FCFA</div>
                    <div class="stat-label">Chiffre d'affaires</div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="charts-section">
                <div class="chart-container">
                    <h3 class="chart-title">Commandes par statut</h3>
                    <canvas id="commandesChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">Produits par catégorie</h3>
                    <canvas id="categoriesChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">Chiffre d'affaires mensuel</h3>
                    <canvas id="caChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title">Top 5 produits vendus</h3>
                    <canvas id="topProduitsChart"></canvas>
                </div>
            </div>

            <!-- Top produits -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>🏆 Top 5 produits les plus vendus</h2>
                </div>
                <?php if (count($top_produits) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Quantité vendue</th>
                                <th>Chiffre d'affaires</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_produits as $produit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                <td>
                                    <span class="badge badge-success"><?php echo $produit['quantite_vendue']; ?> unités</span>
                                </td>
                                <td><?php echo number_format($produit['chiffre_affaires'], 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune vente enregistrée.</p>
                <?php endif; ?>
            </div>

            <!-- Clients actifs -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>👥 Top 10 clients les plus actifs</h2>
                </div>
                <?php if (count($clients_actifs) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Nombre de commandes</th>
                                <th>Total dépensé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clients_actifs as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $client['nombre_commandes']; ?> commandes</span>
                                </td>
                                <td><?php echo number_format($client['total_depense'], 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun client actif.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Chart.js configurations
        document.addEventListener('DOMContentLoaded', function() {
            // Commandes par statut
            const commandesCtx = document.getElementById('commandesChart').getContext('2d');
            new Chart(commandesCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . ucfirst($item['statut']) . "'"; }, $commandes_par_statut)); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_column($commandes_par_statut, 'nombre')); ?>],
                        backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#6f42c1', '#dc3545']
                    }]
                }
            });

            // Produits par catégorie
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['nom'] . "'"; }, $produits_par_categorie)); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_column($produits_par_categorie, 'nombre_produits')); ?>],
                        backgroundColor: ['#3498db', '#27ae60', '#f39c12', '#e74c3c', '#9b59b6']
                    }]
                }
            });

            // Chiffre d'affaires mensuel
            const caCtx = document.getElementById('caChart').getContext('2d');
            new Chart(caCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['mois'] . "'"; }, $ca_mensuel)); ?>],
                    datasets: [{
                        label: 'Chiffre d\'affaires (FCFA)',
                        data: [<?php echo implode(',', array_column($ca_mensuel, 'chiffre_affaires')); ?>],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Top produits
            const topProduitsCtx = document.getElementById('topProduitsChart').getContext('2d');
            new Chart(topProduitsCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['nom'] . "'"; }, $top_produits)); ?>],
                    datasets: [{
                        label: 'Quantité vendue',
                        data: [<?php echo implode(',', array_column($top_produits, 'quantite_vendue')); ?>],
                        backgroundColor: '#27ae60'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>