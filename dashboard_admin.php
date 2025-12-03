
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

// Statistiques pour le dashboard admin
$stats = [
    'total_clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'total_produits' => $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn(),
    'total_commandes' => $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
    'chiffre_affaires' => $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee'")->fetchColumn(),
    'messages_non_lus' => $pdo->query("SELECT COUNT(*) FROM messages WHERE statut = 'non_lu'")->fetchColumn(),
];

// Commandes récentes
$commandes_recentes = $pdo->query("
    SELECT c.*, cl.nom, cl.prenom 
    FROM commandes c 
    JOIN clients cl ON c.client_id = cl.id 
    ORDER BY c.date_commande DESC 
    LIMIT 5
")->fetchAll();

// Produits faible stock
$produits_faible_stock = $pdo->query("
    SELECT * FROM produits 
    WHERE stock < 10 AND statut = 'disponible' 
    ORDER BY stock ASC 
    LIMIT 5
")->fetchAll();

// Messages récents des clients
$messages_recents = $pdo->query("
    SELECT m.*, cl.nom, cl.prenom, cl.email 
    FROM messages m 
    JOIN clients cl ON m.client_id = cl.id 
    ORDER BY m.date_envoi DESC 
    LIMIT 5
")->fetchAll();

// Traitement de la réponse aux messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repondre_message'])) {
    $message_id = $_POST['message_id'];
    $reponse = $_POST['reponse'];
    
    // Mettre à jour le statut du message
    $stmt = $pdo->prepare("UPDATE messages SET statut = 'repondu', reponse = ?, date_reponse = NOW() WHERE id = ?");
    $stmt->execute([$reponse, $message_id]);
    
    header('Location: dashboard_admin.php?reponse_envoyee=1');
    exit();
}

// Marquer un message comme lu
if (isset($_GET['marquer_lu'])) {
    $stmt = $pdo->prepare("UPDATE messages SET statut = 'lu' WHERE id = ?");
    $stmt->execute([$_GET['marquer_lu']]);
    header('Location: dashboard_admin.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin | OVD Boutique</title>
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
                <li><a href="dashboard_admin.php" class="active">📊 Tableau de bord</a></li>
                <li><a href="statistiques.php">📈 Statistiques</a></li>
                
                <div class="menu-section">GESTION</div>
                <li><a href="gestion_produits.php">📦 Produits</a></li>
                <li><a href="gestion_categories.php">🗂️ Catégories</a></li>
                <li><a href="gestion_commandes.php">📋 Commandes</a></li>
                <li><a href="gestion_clients.php">👥 Clients</a></li>
                <li><a href="messagerie.php">👥 Messagerie</a></li>
                <li><a href="gestion_messages.php">📨 Messages clients</a></li>
                
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
                    <h1>Tableau de Bord Administrateur</h1>
                    <p>Gérez votre boutique en ligne</p>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-primary">🏠 Site public</a>
                    <a href="gestion_produits.php?action=ajouter" class="btn btn-success">➕ Nouveau produit</a>
                </div>
            </div>

            <?php if (isset($_GET['reponse_envoyee'])): ?>
                <div class="alert alert-success">
                    ✅ Votre réponse a été envoyée avec succès !
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
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
                <div class="stat-card messages">
                    <div class="stat-number"><?php echo $stats['messages_non_lus']; ?></div>
                    <div class="stat-label">Messages non lus</div>
                </div>
            </div>

            <!-- Messages récents des clients -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📨 Messages récents des clients</h2>
                    <a href="gestion_messages.php" class="btn btn-primary">Voir tous les messages</a>
                </div>
                <?php if (count($messages_recents) > 0): ?>
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
                            <?php foreach($messages_recents as $message): ?>
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
                                    <button class="action-btn btn-view" onclick="afficherMessage(<?php echo $message['id']; ?>)">👁️ Voir</button>
                                    <?php if ($message['statut'] == 'non_lu'): ?>
                                        <a href="?marquer_lu=<?php echo $message['id']; ?>" class="action-btn btn-success">✓ Lu</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucun message reçu pour le moment.</p>
                <?php endif; ?>
            </div>

            <!-- Commandes récentes -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📦 Commandes récentes</h2>
                    <a href="gestion_commandes.php" class="btn btn-primary">Voir toutes les commandes</a>
                </div>
                <?php if (count($commandes_recentes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($commandes_recentes as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id']; ?></td>
                                <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                                <td><?php echo number_format($commande['total'], 2, ',', ' '); ?> FCFA</td>
                                <td>
                                    <span class="badge badge-<?php echo $commande['statut'] == 'en_attente' ? 'warning' : ($commande['statut'] == 'livree' ? 'success' : 'info'); ?>">
                                        <?php echo ucfirst($commande['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="details_commande.php?id=<?php echo $commande['id']; ?>" class="action-btn btn-view">Voir</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune commande récente.</p>
                <?php endif; ?>
            </div>

            <!-- Produits en faible stock -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>⚠️ Produits en faible stock</h2>
                    <a href="gestion_produits.php" class="btn btn-warning">Gérer le stock</a>
                </div>
                <?php if (count($produits_faible_stock) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Référence</th>
                                <th>Stock actuel</th>
                                <th>Prix</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produits_faible_stock as $produit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                <td><?php echo isset($produit['reference']) ? htmlspecialchars($produit['reference']) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge badge-danger"><?php echo $produit['stock']; ?> unités</span>
                                </td>
                                <td><?php echo number_format($produit['prix'], 2, ',', ' '); ?> FCFA</td>
                                <td>
                                    <a href="gestion_produits.php?action=modifier&id=<?php echo $produit['id']; ?>" class="action-btn btn-edit">Modifier</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Tous les produits ont un stock suffisant.</p>
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
            // Ici, vous devriez faire un appel AJAX pour récupérer les détails du message
            // Pour l'exemple, nous allons simuler avec des données statiques
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

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target == modal) {
                fermerModal();
            }
        }
    </script>
</body>
</html>
