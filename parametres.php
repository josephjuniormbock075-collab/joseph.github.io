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

$message = '';
$message_type = '';

// Traitement du formulaire de modification des informations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['modifier_infos'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone']);
        $adresse = trim($_POST['adresse']);
        
        // Validation des données
        if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($adresse)) {
            $message = "Tous les champs sont obligatoires.";
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "L'adresse email n'est pas valide.";
            $message_type = 'error';
        } else {
            // Vérifier si l'email existe déjà pour un autre client
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $message = "Cette adresse email est déjà utilisée par un autre client.";
                $message_type = 'error';
            } else {
                // Mettre à jour les informations
                $stmt = $pdo->prepare("UPDATE clients SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?");
                if ($stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $_SESSION['user_id']])) {
                    $message = "Vos informations ont été mises à jour avec succès.";
                    $message_type = 'success';
                    // Recharger les données du client
                    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $client = $stmt->fetch();
                } else {
                    $message = "Une erreur est survenue lors de la mise à jour.";
                    $message_type = 'error';
                }
            }
        }
    }
    
    // Traitement du changement de mot de passe
    if (isset($_POST['changer_mdp'])) {
        $ancien_mdp = $_POST['ancien_mdp'];
        $nouveau_mdp = $_POST['nouveau_mdp'];
        $confirmer_mdp = $_POST['confirmer_mdp'];
        
        // Vérifier l'ancien mot de passe
        if (!password_verify($ancien_mdp, $client['mot_de_passe'])) {
            $message = "L'ancien mot de passe est incorrect.";
            $message_type = 'error';
        } elseif (strlen($nouveau_mdp) < 6) {
            $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
            $message_type = 'error';
        } elseif ($nouveau_mdp !== $confirmer_mdp) {
            $message = "Les nouveaux mots de passe ne correspondent pas.";
            $message_type = 'error';
        } else {
            // Mettre à jour le mot de passe
            $nouveau_mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE clients SET mot_de_passe = ? WHERE id = ?");
            if ($stmt->execute([$nouveau_mdp_hash, $_SESSION['user_id']])) {
                $message = "Votre mot de passe a été changé avec succès.";
                $message_type = 'success';
            } else {
                $message = "Une erreur est survenue lors du changement de mot de passe.";
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres | OVD Boutique</title>
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

        /* Sections Paramètres */
        .settings-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .settings-section h2 {
            color: #764ba2;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .message {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
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
                <li><a href="dashboard_client.php">📊 Tableau de bord</a></li>
                <li><a href="profil.php">👤 Mon profil</a></li>
                <li><a href="mes_commandes.php">📦 Mes commandes</a></li>
                <li><a href="wishlist.php">❤️ Ma liste de souhaits</a></li>
                <li><a href="adresses.php">🏠 Mes adresses</a></li>
                <li><a href="parametres.php" class="active">⚙️ Paramètres</a></li>
                <li><a href="logout.php" class="logout-btn">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Paramètres du compte</h1>
                    <p>Gérez vos informations personnelles et votre sécurité</p>
                </div>
                <a href="index.php" class="logout-btn">🏠 Retour au site</a>
            </div>

            <!-- Messages d'alerte -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Section Informations personnelles -->
            <div class="settings-section">
                <h2>📝 Informations personnelles</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo htmlspecialchars($client['nom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" 
                               value="<?php echo htmlspecialchars($client['prenom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($client['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" 
                               value="<?php echo htmlspecialchars($client['telephone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <textarea id="adresse" name="adresse" class="form-control" rows="4" required><?php echo htmlspecialchars($client['adresse']); ?></textarea>
                    </div>
                    
                    <button type="submit" name="modifier_infos" class="btn">💾 Enregistrer les modifications</button>
                </form>
            </div>

            <!-- Section Sécurité -->
            <div class="settings-section">
                <h2>🔒 Sécurité du compte</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="ancien_mdp">Ancien mot de passe</label>
                        <input type="password" id="ancien_mdp" name="ancien_mdp" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nouveau_mdp">Nouveau mot de passe</label>
                        <input type="password" id="nouveau_mdp" name="nouveau_mdp" class="form-control" required>
                        <div class="password-requirements">Le mot de passe doit contenir au moins 6 caractères.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmer_mdp">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirmer_mdp" name="confirmer_mdp" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="changer_mdp" class="btn">🔑 Changer le mot de passe</button>
                </form>
            </div>

            <!-- Section Actions -->
            <div class="settings-section">
                <h2>⚡ Actions rapides</h2>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="mes_commandes.php" class="btn" style="text-decoration: none;">📦 Voir mes commandes</a>
                    <a href="profil.php" class="btn" style="text-decoration: none;">👤 Voir mon profil</a>
                    <a href="adresses.php" class="btn" style="text-decoration: none;">🏠 Gérer mes adresses</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>