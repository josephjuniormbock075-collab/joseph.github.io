
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

// Récupérer les commandes du client
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_commande DESC");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();

// Récupérer les messages du client
$stmt = $pdo->prepare("SELECT * FROM messages WHERE client_id = ? ORDER BY date_envoi DESC");
$stmt->execute([$_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_message'])) {
    $sujet = $_POST['sujet'];
    $message = $_POST['message'];
    $type = $_POST['type'];
    
    $stmt = $pdo->prepare("INSERT INTO messages (client_id, sujet, message, type, date_envoi) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $sujet, $message, $type]);
    
    header('Location: dashboard_client.php?message_sent=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Client | OVD Boutique</title>
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

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.2);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
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

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Sections */
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
            color: #764ba2;
        }

        /* Tableaux */
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

        /* Formulaire message */
        .message-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
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
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .message-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-question { background: #d1ecf1; color: #0c5460; }
        .badge-probleme { background: #f8d7da; color: #721c24; }
        .badge-satisfaction { background: #d4edda; color: #155724; }
        .badge-reclamation { background: #fff3cd; color: #856404; }

        .message-status {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .status-non_lu { background: #f8d7da; color: #721c24; }
        .status-lu { background: #d4edda; color: #155724; }
        .status-repondu { background: #d1ecf1; color: #0c5460; }

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
                <li><a href="dashboard_client.php" class="active">📊 Tableau de bord</a></li>
                <li><a href="profil.php">👤 Mon profil</a></li>
                <li><a href="mes_commandes.php">📦 Mes commandes</a></li>
                <li><a href="wishlist.php">❤️ Ma liste de souhaits</a></li>
                <li><a href="adresses.php">🏠 Mes adresses</a></li>
                <li><a href="messagerie.php">📨 Messagerie</a></li>
                <li><a href="parametres.php">⚙️ Paramètres</a></li>
                <li><a href="logout.php" class="logout-btn">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Bonjour, <?php echo htmlspecialchars($client['prenom']); ?>!</h1>
                    <p>Voici votre tableau de bord personnel</p>
                </div>
                <a href="index.php" class="logout-btn">🏠 Retour au site</a>
            </div>

            <?php if (isset($_GET['message_sent'])): ?>
                <div class="alert alert-success">
                    ✅ Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($commandes); ?></div>
                    <div class="stat-label">Commandes totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $commandes_en_attente = array_filter($commandes, function($cmd) {
                            return $cmd['statut'] === 'en_attente';
                        });
                        echo count($commandes_en_attente);
                        ?>
                    </div>
                    <div class="stat-label">Commandes en attente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $commandes_livrees = array_filter($commandes, function($cmd) {
                            return $cmd['statut'] === 'livree';
                        });
                        echo count($commandes_livrees);
                        ?>
                    </div>
                    <div class="stat-label">Commandes livrées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($messages); ?></div>
                    <div class="stat-label">Messages envoyés</div>
                </div>
            </div>

            <!-- Formulaire d'envoi de message -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📨 Contacter l'administration</h2>
                </div>
                <form method="POST" class="message-form">
                    <div class="form-group">
                        <label for="type">Type de message</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="question">Question générale</option>
                            <option value="probleme">Problème technique</option>
                            <option value="satisfaction">Retour satisfaction</option>
                            <option value="reclamation">Réclamation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <input type="text" name="sujet" id="sujet" class="form-control" required placeholder="Objet de votre message">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea name="message" id="message" class="form-control" required placeholder="Décrivez votre demande en détail..."></textarea>
                    </div>
                    <button type="submit" name="envoyer_message" class="btn btn-primary">📤 Envoyer le message</button>
                </form>
            </div>

            <!-- Dernières commandes -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Vos dernières commandes</h2>
                    <a href="mes_commandes.php" class="btn btn-primary">Voir toutes</a>
                </div>
                <?php if (count($commandes) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_slice($commandes, 0, 5) as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
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
                                <td>
                                    <a href="details_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary">Voir détails</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Vous n'avez pas encore passé de commande.</p>
                    <a href="commande.php" class="btn btn-success">Passer votre première commande</a>
                <?php endif; ?>
            </div>

            <!-- Messages récents -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Vos messages récents</h2>
                    <a href="messagerie.php" class="btn btn-primary">Voir tous</a>
                </div>
                <?php if (count($messages) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Sujet</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_slice($messages, 0, 5) as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['sujet']); ?></td>
                                <td>
                                    <span class="message-badge badge-<?php echo $msg['type']; ?>">
                                        <?php 
                                        $types = [
                                            'question' => 'Question',
                                            'probleme' => 'Problème',
                                            'satisfaction' => 'Satisfaction',
                                            'reclamation' => 'Réclamation'
                                        ];
                                        echo $types[$msg['type']];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></td>
                                <td>
                                    <span class="message-status status-<?php echo $msg['statut']; ?>">
                                        <?php 
                                        $statuts_msg = [
                                            'non_lu' => 'Non lu',
                                            'lu' => 'Lu',
                                            'repondu' => 'Répondu'
                                        ];
                                        echo $statuts_msg[$msg['statut']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="details_message.php?id=<?php echo $msg['id']; ?>" class="btn btn-primary">Voir</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Vous n'avez pas encore envoyé de message.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
