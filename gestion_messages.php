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

// Pagination
$messages_par_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $messages_par_page;

// Filtres
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : 'tous';
$filtre_type = isset($_GET['type']) ? $_GET['type'] : 'tous';
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if ($filtre_statut != 'tous') {
    $where_conditions[] = "m.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_type != 'tous') {
    $where_conditions[] = "m.type = ?";
    $params[] = $filtre_type;
}

if (!empty($recherche)) {
    $where_conditions[] = "(cl.nom LIKE ? OR cl.prenom LIKE ? OR cl.email LIKE ? OR m.sujet LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Récupération des messages avec pagination
$sql = "
    SELECT m.*, cl.nom, cl.prenom, cl.email 
    FROM messages m 
    JOIN clients cl ON m.client_id = cl.id 
    $where_sql
    ORDER BY m.date_envoi DESC 
    LIMIT $offset, $messages_par_page
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Comptage total pour la pagination
$sql_count = "SELECT COUNT(*) FROM messages m JOIN clients cl ON m.client_id = cl.id $where_sql";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_messages = $stmt_count->fetchColumn();
$total_pages = ceil($total_messages / $messages_par_page);

// Statistiques par statut
$stats_statut = $pdo->query("
    SELECT statut, COUNT(*) as count 
    FROM messages 
    GROUP BY statut
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Statistiques par type
$stats_type = $pdo->query("
    SELECT type, COUNT(*) as count 
    FROM messages 
    GROUP BY type
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Traitement de la réponse aux messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repondre_message'])) {
    $message_id = $_POST['message_id'];
    $reponse = $_POST['reponse'];
    
    // Mettre à jour le statut du message
    $stmt = $pdo->prepare("UPDATE messages SET statut = 'repondu', reponse = ?, date_reponse = NOW() WHERE id = ?");
    $stmt->execute([$reponse, $message_id]);
    
    header('Location: gestion_messages.php?reponse_envoyee=1');
    exit();
}

// Marquer comme lu/non lu
if (isset($_GET['changer_statut'])) {
    $message_id = $_GET['changer_statut'];
    $nouveau_statut = $_GET['statut'];
    
    $stmt = $pdo->prepare("UPDATE messages SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $message_id]);
    
    header('Location: gestion_messages.php');
    exit();
}

// Supprimer un message
if (isset($_GET['supprimer'])) {
    $message_id = $_GET['supprimer'];
    
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    
    header('Location: gestion_messages.php?supprime=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Messages | Admin OVD Boutique</title>
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

        /* Reprendre le style du dashboard_admin.php et ajouter les styles spécifiques */
        .filtres-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filtres-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .stats-grid-messages {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card-message {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid;
        }

        .stat-card-non_lu { border-color: #e74c3c; }
        .stat-card-lu { border-color: #3498db; }
        .stat-card-repondu { border-color: #27ae60; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }

        .page-link.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .actions-messages {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Admin (identique au dashboard_admin.php) -->
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
                <li><a href="gestion_messages.php" class="active">📨 Messages clients</a></li>
                
                <div class="menu-section">CONTENU</div>
                <li><a href="promotions.php">🎯 Promotions</a></li>
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
                    <h1>Gestion des Messages Clients</h1>
                    <p>Consultez et répondez aux messages de vos clients</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard_admin.php" class="btn btn-primary">← Retour au dashboard</a>
                </div>
            </div>

            <?php if (isset($_GET['reponse_envoyee'])): ?>
                <div class="alert alert-success">
                    ✅ Votre réponse a été envoyée avec succès !
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['supprime'])): ?>
                <div class="alert alert-success">
                    ✅ Message supprimé avec succès !
                </div>
            <?php endif; ?>

            <!-- Statistiques des messages -->
            <div class="stats-grid-messages">
                <div class="stat-card-message stat-card-non_lu">
                    <div class="stat-number"><?php echo $stats_statut['non_lu'] ?? 0; ?></div>
                    <div class="stat-label">Non lus</div>
                </div>
                <div class="stat-card-message stat-card-lu">
                    <div class="stat-number"><?php echo $stats_statut['lu'] ?? 0; ?></div>
                    <div class="stat-label">Lus</div>
                </div>
                <div class="stat-card-message stat-card-repondu">
                    <div class="stat-number"><?php echo $stats_statut['repondu'] ?? 0; ?></div>
                    <div class="stat-label">Répondus</div>
                </div>
                <div class="stat-card-message">
                    <div class="stat-number"><?php echo $total_messages; ?></div>
                    <div class="stat-label">Total messages</div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="filtres-container">
                <form method="GET" class="filtres-form">
                    <div class="form-group">
                        <label for="statut">Statut :</label>
                        <select name="statut" id="statut" class="form-control">
                            <option value="tous" <?php echo $filtre_statut == 'tous' ? 'selected' : ''; ?>>Tous les statuts</option>
                            <option value="non_lu" <?php echo $filtre_statut == 'non_lu' ? 'selected' : ''; ?>>Non lus</option>
                            <option value="lu" <?php echo $filtre_statut == 'lu' ? 'selected' : ''; ?>>Lus</option>
                            <option value="repondu" <?php echo $filtre_statut == 'repondu' ? 'selected' : ''; ?>>Répondus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type">Type :</label>
                        <select name="type" id="type" class="form-control">
                            <option value="tous" <?php echo $filtre_type == 'tous' ? 'selected' : ''; ?>>Tous les types</option>
                            <option value="question" <?php echo $filtre_type == 'question' ? 'selected' : ''; ?>>Question</option>
                            <option value="probleme" <?php echo $filtre_type == 'probleme' ? 'selected' : ''; ?>>Problème</option>
                            <option value="satisfaction" <?php echo $filtre_type == 'satisfaction' ? 'selected' : ''; ?>>Satisfaction</option>
                            <option value="reclamation" <?php echo $filtre_type == 'reclamation' ? 'selected' : ''; ?>>Réclamation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="recherche">Recherche :</label>
                        <input type="text" name="recherche" id="recherche" class="form-control" 
                               placeholder="Nom, email, sujet..." value="<?php echo htmlspecialchars($recherche); ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                        <a href="gestion_messages.php" class="btn btn-warning">🔄 Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Liste des messages -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📨 Liste des messages (<?php echo $total_messages; ?>)</h2>
                </div>

                <?php if (count($messages) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Sujet</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($messages as $message): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($message['prenom'] . ' ' . $message['nom']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($message['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($message['sujet']); ?></td>
                                <td>
                                    <span class="message-type type-<?php echo $message['type']; ?>">
                                        <?php 
                                        $types = [
                                            'question' => 'Question',
                                            'probleme' => 'Problème',
                                            'satisfaction' => 'Satisfaction',
                                            'reclamation' => 'Réclamation'
                                        ];
                                        echo $types[$message['type']];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></td>
                                <td>
                                    <span class="message-status status-<?php echo $message['statut']; ?>">
                                        <?php 
                                        $statuts_msg = [
                                            'non_lu' => 'Non lu',
                                            'lu' => 'Lu',
                                            'repondu' => 'Répondu'
                                        ];
                                        echo $statuts_msg[$message['statut']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-messages">
                                        <button class="action-btn btn-view btn-sm" onclick="afficherMessage(<?php echo $message['id']; ?>)">👁️ Voir</button>
                                        
                                        <?php if ($message['statut'] == 'non_lu'): ?>
                                            <a href="?changer_statut=<?php echo $message['id']; ?>&statut=lu" class="action-btn btn-success btn-sm">✓ Lu</a>
                                        <?php else: ?>
                                            <a href="?changer_statut=<?php echo $message['id']; ?>&statut=non_lu" class="action-btn btn-warning btn-sm">✗ Non lu</a>
                                        <?php endif; ?>
                                        
                                        <a href="?supprimer=<?php echo $message['id']; ?>" class="action-btn btn-delete btn-sm" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">🗑️ Suppr</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&statut=<?php echo $filtre_statut; ?>&type=<?php echo $filtre_type; ?>&recherche=<?php echo urlencode($recherche); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Aucun message trouvé avec les critères sélectionnés.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher et répondre aux messages -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fermerModal()">&times;</span>
            <h3>Détails du message</h3>
            <div id="messageContent"></div>
            <form id="reponseForm" method="POST">
                <input type="hidden" name="message_id" id="message_id">
                <div class="form-group">
                    <label for="reponse">Votre réponse :</label>
                    <textarea name="reponse" id="reponse" class="form-control" placeholder="Tapez votre réponse ici..."></textarea>
                </div>
                <button type="submit" name="repondre_message" class="btn btn-success">📤 Envoyer la réponse</button>
            </form>
        </div>
    </div>

    <script>
        function afficherMessage(messageId) {
            fetch('get_message_details.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('messageContent').innerHTML = `
                        <div class="message-content">
                            <p><strong>De :</strong> ${data.prenom} ${data.nom}</p>
                            <p><strong>Email :</strong> ${data.email}</p>
                            <p><strong>Sujet :</strong> ${data.sujet}</p>
                            <p><strong>Type :</strong> ${data.type}</p>
                            <p><strong>Date :</strong> ${data.date_envoi}</p>
                            <p><strong>Message :</strong></p>
                            <p>${data.message}</p>
                            ${data.reponse ? `<p><strong>Réponse précédente :</strong></p><p>${data.reponse}</p>` : ''}
                        </div>
                    `;
                    document.getElementById('message_id').value = messageId;
                    document.getElementById('reponse').value = data.reponse || '';
                    document.getElementById('messageModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement du message');
                });
        }

        function fermerModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target == modal) {
                fermerModal();
            }
        }
    </script>
</body>
</html>