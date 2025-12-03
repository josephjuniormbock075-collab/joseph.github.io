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

// Variables pour les erreurs et succès
$error = '';
$success = '';

// Gérer la sélection d'une conversation
$destinataire_id = isset($_GET['destinataire']) ? intval($_GET['destinataire']) : 0;
$destinataire_info = null;

// Récupérer tous les utilisateurs pour les conversations
$conversations = [];

// Récupérer les messages envoyés
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        CASE 
            WHEN m.destinataire_type = 'client' THEN c.nom 
            WHEN m.destinataire_type = 'admin' THEN a.nom 
        END as destinataire_nom,
        CASE 
            WHEN m.destinataire_type = 'client' THEN c.prenom 
            WHEN m.destinataire_type = 'admin' THEN a.prenom 
        END as destinataire_prenom,
        CASE 
            WHEN m.destinataire_type = 'client' THEN c.email 
            WHEN m.destinataire_type = 'admin' THEN a.email 
        END as destinataire_email
    FROM messages_prives m
    LEFT JOIN clients c ON m.destinataire_id = c.id AND m.destinataire_type = 'client'
    LEFT JOIN administrateurs a ON m.destinataire_id = a.id AND m.destinataire_type = 'admin'
    WHERE m.expediteur_id = ? AND m.expediteur_type = 'client'
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$_SESSION['user_id']]);
$messages_envoyes = $stmt->fetchAll();

// Récupérer les messages reçus
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        CASE 
            WHEN m.expediteur_type = 'client' THEN c.nom 
            WHEN m.expediteur_type = 'admin' THEN a.nom 
        END as expediteur_nom,
        CASE 
            WHEN m.expediteur_type = 'client' THEN c.prenom 
            WHEN m.expediteur_type = 'admin' THEN a.prenom 
        END as expediteur_prenom,
        CASE 
            WHEN m.expediteur_type = 'client' THEN c.email 
            WHEN m.expediteur_type = 'admin' THEN a.email 
        END as expediteur_email
    FROM messages_prives m
    LEFT JOIN clients c ON m.expediteur_id = c.id AND m.expediteur_type = 'client'
    LEFT JOIN administrateurs a ON m.expediteur_id = a.id AND m.expediteur_type = 'admin'
    WHERE m.destinataire_id = ? AND m.destinataire_type = 'client'
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$_SESSION['user_id']]);
$messages_recus = $stmt->fetchAll();

// Organiser les conversations
foreach ($messages_envoyes as $msg) {
    $key = $msg['destinataire_id'] . '_' . $msg['destinataire_type'];
    if (!isset($conversations[$key])) {
        $conversations[$key] = [
            'id' => $msg['destinataire_id'],
            'type' => $msg['destinataire_type'],
            'nom' => $msg['destinataire_nom'],
            'prenom' => $msg['destinataire_prenom'],
            'email' => $msg['destinataire_email'],
            'messages' => [],
            'non_lus' => 0,
            'dernier_message' => $msg['date_envoi']
        ];
    }
    $conversations[$key]['messages'][] = $msg;
}

foreach ($messages_recus as $msg) {
    $key = $msg['expediteur_id'] . '_' . $msg['expediteur_type'];
    if (!isset($conversations[$key])) {
        $conversations[$key] = [
            'id' => $msg['expediteur_id'],
            'type' => $msg['expediteur_type'],
            'nom' => $msg['expediteur_nom'],
            'prenom' => $msg['expediteur_prenom'],
            'email' => $msg['expediteur_email'],
            'messages' => [],
            'non_lus' => 0,
            'dernier_message' => $msg['date_envoi']
        ];
    }
    $conversations[$key]['messages'][] = $msg;
    
    // Compter les messages non lus
    if ($msg['statut'] == 'non_lu') {
        $conversations[$key]['non_lus']++;
    }
}

// Trier les conversations par date du dernier message
usort($conversations, function($a, $b) {
    return strtotime($b['dernier_message']) - strtotime($a['dernier_message']);
});

// Si un destinataire est sélectionné
if ($destinataire_id > 0) {
    // Chercher les infos du destinataire
    foreach ($conversations as $conv) {
        if ($conv['id'] == $destinataire_id) {
            $destinataire_info = $conv;
            break;
        }
    }
    
    // Si trouvé, récupérer les messages de cette conversation
    if ($destinataire_info) {
        // Marquer les messages comme lus
        $stmt = $pdo->prepare("
            UPDATE messages_prives 
            SET statut = 'lu', date_lecture = NOW() 
            WHERE destinataire_id = ? 
            AND destinataire_type = 'client' 
            AND expediteur_id = ? 
            AND expediteur_type = ?
            AND statut = 'non_lu'
        ");
        $stmt->execute([$_SESSION['user_id'], $destinataire_id, $destinataire_info['type']]);
        
        // Récupérer les messages de la conversation
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                CASE 
                    WHEN m.expediteur_type = 'client' THEN c.nom 
                    WHEN m.expediteur_type = 'admin' THEN a.nom 
                END as expediteur_nom,
                CASE 
                    WHEN m.expediteur_type = 'client' THEN c.prenom 
                    WHEN m.expediteur_type = 'admin' THEN a.prenom 
                END as expediteur_prenom,
                CASE 
                    WHEN m.expediteur_type = 'client' THEN c.email 
                    WHEN m.expediteur_type = 'admin' THEN a.email 
                END as expediteur_email,
                CASE 
                    WHEN m.expediteur_id = ? AND m.expediteur_type = 'client' THEN 1 
                    ELSE 0 
                END as est_expediteur
            FROM messages_prives m
            LEFT JOIN clients c ON m.expediteur_id = c.id AND m.expediteur_type = 'client'
            LEFT JOIN administrateurs a ON m.expediteur_id = a.id AND m.expediteur_type = 'admin'
            WHERE (
                (m.expediteur_id = ? AND m.expediteur_type = 'client' AND m.destinataire_id = ? AND m.destinataire_type = ?)
                OR
                (m.expediteur_id = ? AND m.expediteur_type = ? AND m.destinataire_id = ? AND m.destinataire_type = 'client')
            )
            ORDER BY m.date_envoi ASC
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $destinataire_id,
            $destinataire_info['type'],
            $destinataire_id,
            $destinataire_info['type'],
            $_SESSION['user_id']
        ]);
        
        $messages_conversation = $stmt->fetchAll();
    }
}

// Traitement de l'envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_message'])) {
    $destinataire_id = intval($_POST['destinataire_id']);
    $destinataire_type = $_POST['destinataire_type'];
    $sujet = trim($_POST['sujet']);
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $error = "Le message ne peut pas être vide";
    } elseif (empty($destinataire_id) || empty($destinataire_type)) {
        $error = "Destinataire invalide";
    } else {
        // Vérifier si le destinataire existe
        $destinataire_existe = false;
        
        if ($destinataire_type === 'client') {
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND statut = 'actif'");
            $stmt->execute([$destinataire_id]);
            $destinataire_existe = (bool)$stmt->fetch();
        } else {
            $stmt = $pdo->prepare("SELECT id FROM administrateurs WHERE id = ? AND statut = 'actif'");
            $stmt->execute([$destinataire_id]);
            $destinataire_existe = (bool)$stmt->fetch();
        }
        
        if ($destinataire_existe) {
            try {
                // Insérer le message
                $stmt = $pdo->prepare("
                    INSERT INTO messages_prives 
                    (expediteur_id, expediteur_type, destinataire_id, destinataire_type, sujet, message, date_envoi, statut) 
                    VALUES (:expediteur_id, 'client', :destinataire_id, :destinataire_type, :sujet, :message, NOW(), 'non_lu')
                ");
                
                $stmt->execute([
                    ':expediteur_id' => $_SESSION['user_id'],
                    ':destinataire_id' => $destinataire_id,
                    ':destinataire_type' => $destinataire_type,
                    ':sujet' => $sujet,
                    ':message' => $message
                ]);
                
                $success = "Message envoyé avec succès!";
                
                // Recharger la page pour voir le nouveau message
                header("Location: messagerie.php?destinataire=$destinataire_id&success=1");
                exit();
                
            } catch(PDOException $e) {
                $error = "Erreur lors de l'envoi du message : " . $e->getMessage();
            }
        } else {
            $error = "Destinataire non trouvé";
        }
    }
}

// Récupérer tous les clients et admins pour le modal
$stmt = $pdo->prepare("SELECT id, nom, prenom, email, 'client' as type FROM clients WHERE id != ? AND statut = 'actif' ORDER BY nom, prenom");
$stmt->execute([$_SESSION['user_id']]);
$tous_clients = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, nom, prenom, email, 'admin' as type FROM administrateurs WHERE statut = 'actif' ORDER BY nom, prenom");
$stmt->execute();
$tous_admins = $stmt->fetchAll();

$tous_utilisateurs = array_merge($tous_clients, $tous_admins);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | OVD Boutique</title>
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

        .messagerie-container {
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
            display: inline-block;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Alertes */
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Layout messagerie */
        .messagerie-layout {
            display: flex;
            gap: 2rem;
            height: calc(100vh - 200px);
        }

        /* Liste des conversations */
        .conversations-list {
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversations-header h3 {
            color: #764ba2;
        }

        .nouveau-message-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .conversations-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #eee;
            text-decoration: none;
            color: inherit;
        }

        .conversation-item:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }

        .conversation-item.active {
            background: #e6f7ff;
            border-color: #1890ff;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
        }

        .conversation-info {
            flex: 1;
        }

        .conversation-nom {
            font-weight: bold;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .conversation-dernier-msg {
            font-size: 0.85rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .conversation-meta {
            text-align: right;
        }

        .conversation-date {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 5px;
        }

        .badge-non-lus {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Zone de conversation */
        .conversation-zone {
            flex: 1;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .conversation-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .conversation-header h3 {
            color: #764ba2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .role-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .badge-client { background: #d1ecf1; color: #0c5460; }
        .badge-admin { background: #d4edda; color: #155724; }

        .messages-container {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-item {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 15px;
            position: relative;
        }

        .message-expediteur {
            background: #e6f7ff;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .message-destinataire {
            background: #f0f2f5;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }

        .message-expediteur-nom {
            font-weight: bold;
            color: #1890ff;
        }

        .message-date {
            color: #999;
        }

        .message-content {
            line-height: 1.4;
        }

        .message-statut {
            font-size: 0.7rem;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }

        /* Formulaire d'envoi */
        .message-form-container {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            background: #f8f9fa;
        }

        .message-form {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .form-group {
            flex: 1;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            resize: vertical;
            min-height: 60px;
            max-height: 120px;
        }

        .btn-send {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-send:hover {
            background: #5a6fd8;
        }

        /* Modal nouveau message */
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
            width: 500px;
            max-width: 90%;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            color: #764ba2;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }

        .select-destinataire {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        /* Indicateur de chargement */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        /* Styles responsifs */
        @media (max-width: 1024px) {
            .messagerie-layout {
                flex-direction: column;
                height: auto;
            }
            
            .conversations-list {
                width: 100%;
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .messagerie-container {
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
    <div class="messagerie-container">
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
                <li><a href="wishlist.php">❤️ Ma liste de souhaits</a></li>
                <li><a href="adresses.php">🏠 Mes adresses</a></li>
                <li><a href="messagerie.php" class="active">📨 Messagerie</a></li>
                <li><a href="parametres.php">⚙️ Paramètres</a></li>
                <li><a href="logout.php" class="logout-btn">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Messagerie</h1>
                    <p>Communiquez avec les autres clients et l'administration</p>
                </div>
                <a href="index.php" class="logout-btn">🏠 Retour au site</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    ✅ Votre message a été envoyé avec succès !
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="messagerie-layout">
                <!-- Liste des conversations -->
                <div class="conversations-list">
                    <div class="conversations-header">
                        <h3>Conversations</h3>
                        <button class="nouveau-message-btn" onclick="ouvrirModalNouveauMessage()">+ Nouveau</button>
                    </div>
                    <div class="conversations-body">
                        <?php if (count($conversations) > 0): ?>
                            <?php foreach ($conversations as $conv): ?>
                                <a href="messagerie.php?destinataire=<?php echo $conv['id']; ?>"
                                   class="conversation-item <?php echo ($destinataire_id == $conv['id']) ? 'active' : ''; ?>">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($conv['prenom'], 0, 1)); ?>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-nom">
                                            <?php echo htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']); ?>
                                            <span class="role-badge badge-<?php echo $conv['type']; ?>">
                                                <?php echo $conv['type'] === 'admin' ? 'Admin' : 'Client'; ?>
                                            </span>
                                        </div>
                                        <div class="conversation-dernier-msg">
                                            <?php 
                                            if (!empty($conv['messages'])) {
                                                $last_msg = end($conv['messages']);
                                                echo htmlspecialchars(substr($last_msg['message'], 0, 50));
                                                if (strlen($last_msg['message']) > 50) echo '...';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="conversation-meta">
                                        <div class="conversation-date">
                                            <?php echo date('d/m H:i', strtotime($conv['dernier_message'])); ?>
                                        </div>
                                        <?php if ($conv['non_lus'] > 0): ?>
                                            <div class="badge-non-lus"><?php echo $conv['non_lus']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="loading">
                                Aucune conversation pour le moment
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Zone de conversation -->
                <div class="conversation-zone">
                    <?php if ($destinataire_info): ?>
                        <!-- En-tête de conversation -->
                        <div class="conversation-header">
                            <h3>
                                <div class="avatar" style="display: inline-flex; vertical-align: middle; margin-right: 10px;">
                                    <?php echo strtoupper(substr($destinataire_info['prenom'], 0, 1)); ?>
                                </div>
                                Conversation avec 
                                <?php echo htmlspecialchars($destinataire_info['prenom'] . ' ' . $destinataire_info['nom']); ?>
                                <span class="role-badge badge-<?php echo $destinataire_info['type']; ?>">
                                    <?php echo $destinataire_info['type'] === 'admin' ? 'Administrateur' : 'Client'; ?>
                                </span>
                            </h3>
                        </div>

                        <!-- Messages -->
                        <div class="messages-container" id="messages-container">
                            <?php if (isset($messages_conversation) && count($messages_conversation) > 0): ?>
                                <?php foreach ($messages_conversation as $msg): ?>
                                    <div class="message-item <?php echo $msg['est_expediteur'] ? 'message-destinataire' : 'message-expediteur'; ?>">
                                        <div class="message-header">
                                            <span class="message-expediteur-nom">
                                                <?php echo $msg['est_expediteur'] ? 'Moi' : htmlspecialchars($msg['expediteur_prenom'] . ' ' . $msg['expediteur_nom']); ?>
                                            </span>
                                            <span class="message-date">
                                                <?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?>
                                            </span>
                                        </div>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                        <?php if ($msg['est_expediteur']): ?>
                                            <div class="message-statut">
                                                <?php 
                                                if ($msg['statut'] === 'non_lu') echo '🕒 Non lu';
                                                elseif ($msg['statut'] === 'lu') echo '✓ Lu';
                                                elseif ($msg['date_lecture']) echo '✓ Lu à ' . date('H:i', strtotime($msg['date_lecture']));
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="loading">
                                    Aucun message dans cette conversation. Envoyez le premier message !
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire d'envoi -->
                        <div class="message-form-container">
                            <form method="POST" class="message-form" id="messageForm">
                                <input type="hidden" name="destinataire_id" value="<?php echo $destinataire_id; ?>">
                                <input type="hidden" name="destinataire_type" value="<?php echo $destinataire_info['type']; ?>">
                                <div class="form-group">
                                    <input type="text" name="sujet" class="form-control" placeholder="Sujet (facultatif)" 
                                           value="<?php echo isset($_POST['sujet']) ? htmlspecialchars($_POST['sujet']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <textarea name="message" class="form-control" placeholder="Tapez votre message ici..." required id="messageInput"></textarea>
                                </div>
                                <button type="submit" name="envoyer_message" class="btn-send" id="sendButton">
                                    📤 Envoyer
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <!-- Aucune conversation sélectionnée -->
                        <div class="conversation-header">
                            <h3>Sélectionnez une conversation</h3>
                        </div>
                        <div class="loading" style="flex: 1; display: flex; align-items: center; justify-content: center;">
                            <div style="text-align: center;">
                                <p style="font-size: 1.2rem; color: #666; margin-bottom: 1rem;">
                                    👈 Sélectionnez une conversation à gauche<br>ou commencez une nouvelle conversation
                                </p>
                                <button class="nouveau-message-btn" onclick="ouvrirModalNouveauMessage()" 
                                        style="padding: 12px 24px; font-size: 1rem;">
                                    + Nouvelle conversation
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal nouveau message -->
    <div id="modalNouveauMessage" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nouveau message</h3>
                <button class="close-modal" onclick="fermerModalNouveauMessage()">×</button>
            </div>
            <form method="POST" id="formNouveauMessage" action="messagerie.php">
                <div class="form-group">
                    <label for="nouveau_destinataire">Destinataire</label>
                    <select name="destinataire_id" id="nouveau_destinataire" class="select-destinataire" required>
                        <option value="">Sélectionnez un destinataire</option>
                        <optgroup label="Administrateurs">
                            <?php foreach ($tous_admins as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>" data-type="admin">
                                    👑 <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?> (Admin)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Clients">
                            <?php foreach ($tous_clients as $client_user): ?>
                                <option value="<?php echo $client_user['id']; ?>" data-type="client">
                                    👤 <?php echo htmlspecialchars($client_user['prenom'] . ' ' . $client_user['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <input type="hidden" name="destinataire_type" id="destinataire_type">
                </div>
                <div class="form-group">
                    <label for="nouveau_sujet">Sujet (facultatif)</label>
                    <input type="text" name="sujet" id="nouveau_sujet" class="form-control" placeholder="Sujet du message">
                </div>
                <div class="form-group">
                    <label for="nouveau_message">Message</label>
                    <textarea name="message" id="nouveau_message" class="form-control" required 
                              placeholder="Tapez votre message ici..." rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="logout-btn" onclick="fermerModalNouveauMessage()" 
                            style="background: #6c757d;">Annuler</button>
                    <button type="submit" name="envoyer_message" class="btn-send">📤 Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion du modal
        function ouvrirModalNouveauMessage() {
            document.getElementById('modalNouveauMessage').style.display = 'block';
        }

        function fermerModalNouveauMessage() {
            document.getElementById('modalNouveauMessage').style.display = 'none';
        }

        // Mettre à jour le type de destinataire
        document.getElementById('nouveau_destinataire').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const type = selectedOption.getAttribute('data-type');
            document.getElementById('destinataire_type').value = type;
        });

        // Gestion du formulaire modal
        document.getElementById('formNouveauMessage').addEventListener('submit', function(e) {
            const destinataire = document.getElementById('nouveau_destinataire').value;
            const message = document.getElementById('nouveau_message').value.trim();
            
            if (!destinataire) {
                e.preventDefault();
                alert('Veuillez sélectionner un destinataire');
                return;
            }
            
            if (!message) {
                e.preventDefault();
                alert('Veuillez écrire un message');
                return;
            }
        });

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('modalNouveauMessage');
            if (event.target === modal) {
                fermerModalNouveauMessage();
            }
        }

        // Faire défiler vers le bas dans la zone de messages
        window.onload = function() {
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Focus sur le champ de message si une conversation est ouverte
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.focus();
            }
        }

        // Auto-refresh des messages toutes les 30 secondes si une conversation est active
        setInterval(function() {
            if (<?php echo $destinataire_id ? 'true' : 'false'; ?>) {
                // Recharger seulement si l'utilisateur n'est pas en train d'écrire
                const messageInput = document.getElementById('messageInput');
                if (!messageInput || messageInput.value.trim() === '') {
                    window.location.reload();
                }
            }
        }, 30000);

        // Empêcher l'envoi du formulaire avec Enter dans le textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.querySelector('textarea[name="message"]');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && e.ctrlKey) {
                        // Ctrl+Enter pour envoyer
                        const form = this.closest('form');
                        if (form) {
                            form.submit();
                        }
                    } else if (e.key === 'Enter' && !e.shiftKey) {
                        // Empêcher l'envoi avec Enter seul
                        e.preventDefault();
                        // Insérer un saut de ligne à la place
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        this.value = this.value.substring(0, start) + '\n' + this.value.substring(end);
                        this.selectionStart = this.selectionEnd = start + 1;
                    }
                });
            }
        });
    </script>
</body>
</html>