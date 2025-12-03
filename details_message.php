<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
    exit();
}

// Vérifier si l'ID du message est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard_client.php');
    exit();
}

$message_id = (int)$_GET['id'];

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

// Récupérer les détails du message
$stmt = $pdo->prepare("SELECT m.*, c.prenom, c.nom, c.email FROM messages m 
                      JOIN clients c ON m.client_id = c.id 
                      WHERE m.id = ? AND m.client_id = ?");
$stmt->execute([$message_id, $_SESSION['user_id']]);
$message = $stmt->fetch();

if (!$message) {
    header('Location: dashboard_client.php');
    exit();
}

// Marquer le message comme lu s'il ne l'est pas déjà
if ($message['statut'] === 'non_lu') {
    $stmt = $pdo->prepare("UPDATE messages SET statut = 'lu' WHERE id = ?");
    $stmt->execute([$message_id]);
    $message['statut'] = 'lu';
}

// Traitement de la réponse (si admin a répondu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_reponse'])) {
    $reponse = $_POST['reponse'];
    
    if (!empty($reponse)) {
        // Pour les clients, on peut permettre d'ajouter un commentaire ou une réponse supplémentaire
        // Dans cet exemple, on met à jour la réponse existante
        $stmt = $pdo->prepare("UPDATE messages SET reponse = ?, date_reponse = NOW(), statut = 'repondu' WHERE id = ?");
        $stmt->execute([$reponse, $message_id]);
        
        header('Location: details_message.php?id=' . $message_id . '&updated=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Message | OVD Boutique</title>
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .message-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 992px) {
            .message-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        .message-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .message-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .message-header h2 {
            color: #764ba2;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .message-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-question { background: #d1ecf1; color: #0c5460; }
        .badge-probleme { background: #f8d7da; color: #721c24; }
        .badge-satisfaction { background: #d4edda; color: #155724; }
        .badge-reclamation { background: #fff3cd; color: #856404; }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-non_lu { background: #f8d7da; color: #721c24; }
        .status-lu { background: #d4edda; color: #155724; }
        .status-repondu { background: #d1ecf1; color: #0c5460; }

        .message-content {
            padding: 1.5rem;
        }

        .message-body {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
            line-height: 1.8;
        }

        .message-reply {
            background: #e8f4fd;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .message-reply h3 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .reply-body {
            white-space: pre-wrap;
            line-height: 1.8;
        }

        .reply-date {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 1rem;
        }

        .no-reply {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }

        /* Formulaire de réponse */
        .reply-form {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .reply-form h3 {
            color: #764ba2;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        textarea.form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: 'Arial', sans-serif;
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        /* Informations client */
        .client-info {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .client-info h3 {
            color: #764ba2;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 576px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #333;
            font-size: 1rem;
        }

        .info-value.email {
            color: #667eea;
            text-decoration: none;
        }

        .info-value.email:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .message-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <h1>📨 Détails du Message</h1>
            <a href="dashboard_client.php" class="back-btn">← Retour au tableau de bord</a>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                ✅ Votre réponse a été enregistrée avec succès !
            </div>
        <?php endif; ?>

        <div class="message-container">
            <!-- Message principal -->
            <div class="message-card">
                <div class="message-header">
                    <h2><?php echo htmlspecialchars($message['sujet']); ?></h2>
                    <div class="message-meta">
                        <span class="badge badge-<?php echo $message['type']; ?>">
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
                        <span class="status-badge status-<?php echo $message['statut']; ?>">
                            <?php 
                            $statuts = [
                                'non_lu' => 'Non lu',
                                'lu' => 'Lu',
                                'repondu' => 'Répondu'
                            ];
                            echo $statuts[$message['statut']];
                            ?>
                        </span>
                        <span>Envoyé le : <?php echo date('d/m/Y à H:i', strtotime($message['date_envoi'])); ?></span>
                    </div>
                </div>
                
                <div class="message-content">
                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>

                    <!-- Réponse de l'administration -->
                    <div class="message-reply">
                        <h3>📝 Réponse de l'administration :</h3>
                        <?php if (!empty($message['reponse'])): ?>
                            <div class="reply-body">
                                <?php echo nl2br(htmlspecialchars($message['reponse'])); ?>
                            </div>
                            <?php if (!empty($message['date_reponse'])): ?>
                                <div class="reply-date">
                                    Répondu le : <?php echo date('d/m/Y à H:i', strtotime($message['date_reponse'])); ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-reply">
                                <p>⏳ Aucune réponse pour le moment. Nous traiterons votre demande dès que possible.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Informations du client et formulaire -->
            <div>
                <!-- Informations client -->
                <div class="client-info">
                    <h3>👤 Informations de l'expéditeur</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nom complet :</span>
                            <span class="info-value"><?php echo htmlspecialchars($message['prenom'] . ' ' . $message['nom']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email :</span>
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="info-value email">
                                <?php echo htmlspecialchars($message['email']); ?>
                            </a>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID Client :</span>
                            <span class="info-value">#<?php echo htmlspecialchars($message['client_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Statut du compte :</span>
                            <span class="info-value">
                                <?php echo $client['statut'] === 'actif' ? '✅ Actif' : '❌ Inactif'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Formulaire pour ajouter un commentaire (si nécessaire) -->
                <?php if ($message['statut'] === 'repondu'): ?>
                    <div class="reply-form" style="margin-top: 2rem;">
                        <h3>💬 Ajouter un commentaire supplémentaire</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="reponse">Votre commentaire :</label>
                                <textarea name="reponse" id="reponse" class="form-control" 
                                          placeholder="Si vous avez besoin d'apporter des précisions ou poser une question complémentaire..."><?php 
                                          echo !empty($message['reponse']) ? htmlspecialchars($message['reponse']) : ''; 
                                          ?></textarea>
                            </div>
                            <div class="message-actions">
                                <button type="submit" name="envoyer_reponse" class="btn btn-primary">
                                    💾 Enregistrer le commentaire
                                </button>
                                <a href="messagerie.php?type=repondu" class="btn btn-secondary">
                                    📨 Voir tous les messages
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Actions supplémentaires -->
                <div class="message-actions" style="margin-top: 2rem;">
                    <?php if ($message['statut'] !== 'repondu'): ?>
                        <a href="messagerie.php?type=non_lu" class="btn btn-secondary">
                            📋 Voir les messages non lus
                        </a>
                    <?php endif; ?>
                    <a href="dashboard_client.php#messages" class="btn btn-secondary">
                        📊 Retour au tableau de bord
                    </a>
                </div>

                <!-- Note informative -->
                <div class="alert alert-info" style="margin-top: 1.5rem;">
                    <strong>ℹ️ Information :</strong> 
                    <?php if ($message['statut'] === 'repondu'): ?>
                        Votre message a reçu une réponse. Vous pouvez ajouter un commentaire supplémentaire si nécessaire.
                    <?php elseif ($message['statut'] === 'lu'): ?>
                        Votre message a été lu par l'administration. Vous recevrez une réponse prochainement.
                    <?php else: ?>
                        Votre message est en attente de traitement par l'équipe d'administration.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>