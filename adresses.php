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
} catch(PDDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupérer les informations du client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$client = $stmt->fetch();

// Vérifier si la table adresses existe, sinon la créer
$pdo->exec("
    CREATE TABLE IF NOT EXISTS adresses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client_id INT NOT NULL,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        telephone VARCHAR(20) NOT NULL,
        adresse TEXT NOT NULL,
        complement TEXT,
        code_postal VARCHAR(10) NOT NULL,
        ville VARCHAR(100) NOT NULL,
        pays VARCHAR(100) DEFAULT 'France',
        type ENUM('livraison', 'facturation', 'les_deux') DEFAULT 'les_deux',
        par_defaut TINYINT(1) DEFAULT 0,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )
");

// Traitement de l'ajout d'adresse
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter_adresse'])) {
        $nom = htmlspecialchars($_POST['nom']);
        $prenom = htmlspecialchars($_POST['prenom']);
        $telephone = htmlspecialchars($_POST['telephone']);
        $adresse = htmlspecialchars($_POST['adresse']);
        $complement = htmlspecialchars($_POST['complement']);
        $code_postal = htmlspecialchars($_POST['code_postal']);
        $ville = htmlspecialchars($_POST['ville']);
        $pays = htmlspecialchars($_POST['pays']);
        $type = htmlspecialchars($_POST['type']);
        $par_defaut = isset($_POST['par_defaut']) ? 1 : 0;
        
        // Si c'est l'adresse par défaut, retirer le statut par défaut des autres adresses
        if ($par_defaut) {
            $stmt = $pdo->prepare("UPDATE adresses SET par_defaut = 0 WHERE client_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        $stmt = $pdo->prepare("INSERT INTO adresses (client_id, nom, prenom, telephone, adresse, complement, code_postal, ville, pays, type, par_defaut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$_SESSION['user_id'], $nom, $prenom, $telephone, $adresse, $complement, $code_postal, $ville, $pays, $type, $par_defaut])) {
            $message = "Adresse ajoutée avec succès!";
            $message_type = 'success';
        } else {
            $message = "Erreur lors de l'ajout de l'adresse.";
            $message_type = 'error';
        }
    }
    
    // Définir comme adresse par défaut
    if (isset($_POST['definir_defaut'])) {
        $adresse_id = $_POST['adresse_id'];
        
        $stmt = $pdo->prepare("UPDATE adresses SET par_defaut = 0 WHERE client_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $stmt = $pdo->prepare("UPDATE adresses SET par_defaut = 1 WHERE id = ? AND client_id = ?");
        $stmt->execute([$adresse_id, $_SESSION['user_id']]);
        
        $message = "Adresse définie comme adresse par défaut!";
        $message_type = 'success';
    }
    
    // Supprimer une adresse
    if (isset($_POST['supprimer_adresse'])) {
        $adresse_id = $_POST['adresse_id'];
        
        $stmt = $pdo->prepare("DELETE FROM adresses WHERE id = ? AND client_id = ?");
        $stmt->execute([$adresse_id, $_SESSION['user_id']]);
        
        $message = "Adresse supprimée avec succès!";
        $message_type = 'success';
    }
}

// Récupérer les adresses du client
$stmt = $pdo->prepare("SELECT * FROM adresses WHERE client_id = ? ORDER BY par_defaut DESC, date_creation DESC");
$stmt->execute([$_SESSION['user_id']]);
$adresses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Adresses | OVD Boutique</title>
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

        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.3);
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
        }

        .welcome-message h1 {
            color: #764ba2;
            margin-bottom: 0.5rem;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        /* Adresses Section */
        .adresses-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .adresses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .adresse-card {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 1.5rem;
            position: relative;
            transition: border-color 0.3s ease;
        }

        .adresse-card.defaut {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .adresse-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .adresse-type {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .defaut-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .adresse-body p {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .adresse-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Formulaire d'ajout */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input {
            width: auto;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

            .adresses-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
                <li><a href="adresses.php" class="active">🏠 Mes adresses</a></li>
                <li><a href="parametres.php">⚙️ Paramètres</a></li>
                <li><a href="logout.php">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h1>Mes Adresses</h1>
                    <p>Gérez vos adresses de livraison et de facturation</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Liste des adresses -->
            <div class="adresses-section">
                <div class="section-header">
                    <h2>Vos adresses (<?php echo count($adresses); ?>)</h2>
                </div>

                <?php if (count($adresses) > 0): ?>
                    <div class="adresses-grid">
                        <?php foreach($adresses as $adresse): ?>
                        <div class="adresse-card <?php echo $adresse['par_defaut'] ? 'defaut' : ''; ?>">
                            <div class="adresse-header">
                                <span class="adresse-type">
                                    <?php 
                                    $types = [
                                        'livraison' => '🚚 Livraison',
                                        'facturation' => '📄 Facturation',
                                        'les_deux' => '🏠 Les deux'
                                    ];
                                    echo $types[$adresse['type']];
                                    ?>
                                </span>
                                <?php if ($adresse['par_defaut']): ?>
                                    <span class="defaut-badge">⭐ Par défaut</span>
                                <?php endif; ?>
                            </div>
                            <div class="adresse-body">
                                <p><strong><?php echo htmlspecialchars($adresse['prenom'] . ' ' . $adresse['nom']); ?></strong></p>
                                <p><?php echo htmlspecialchars($adresse['telephone']); ?></p>
                                <p><?php echo htmlspecialchars($adresse['adresse']); ?></p>
                                <?php if (!empty($adresse['complement'])): ?>
                                    <p><?php echo htmlspecialchars($adresse['complement']); ?></p>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($adresse['code_postal'] . ' ' . $adresse['ville']); ?></p>
                                <p><?php echo htmlspecialchars($adresse['pays']); ?></p>
                            </div>
                            <div class="adresse-actions">
                                <?php if (!$adresse['par_defaut']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="adresse_id" value="<?php echo $adresse['id']; ?>">
                                        <button type="submit" name="definir_defaut" class="btn btn-success btn-small">⭐ Définir par défaut</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="adresse_id" value="<?php echo $adresse['id']; ?>">
                                    <button type="submit" name="supprimer_adresse" class="btn btn-danger btn-small" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette adresse ?')">
                                        🗑️ Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div>🏠</div>
                        <h3>Aucune adresse enregistrée</h3>
                        <p>Ajoutez votre première adresse pour faciliter vos commandes.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Formulaire d'ajout d'adresse -->
            <div class="form-container">
                <h2>Ajouter une nouvelle adresse</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($client['prenom']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($client['nom']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['telephone']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="adresse">Adresse *</label>
                        <textarea id="adresse" name="adresse" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="complement">Complément d'adresse</label>
                        <input type="text" id="complement" name="complement" placeholder="Appartement, étage, etc.">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="code_postal">Code postal *</label>
                            <input type="text" id="code_postal" name="code_postal" required>
                        </div>
                        <div class="form-group">
                            <label for="ville">Ville *</label>
                            <input type="text" id="ville" name="ville" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pays">Pays *</label>
                        <input type="text" id="pays" name="pays" value="France" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Type d'adresse *</label>
                        <select id="type" name="type" required>
                            <option value="les_deux">Livraison et facturation</option>
                            <option value="livraison">Livraison seulement</option>
                            <option value="facturation">Facturation seulement</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="par_defaut" name="par_defaut" value="1">
                            <label for="par_defaut">Définir comme adresse par défaut</label>
                        </div>
                    </div>

                    <button type="submit" name="ajouter_adresse" class="btn">➕ Ajouter l'adresse</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>