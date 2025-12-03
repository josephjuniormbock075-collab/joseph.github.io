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

// Créer la table parametres_site si elle n'existe pas
$pdo->exec("
    CREATE TABLE IF NOT EXISTS parametres_site (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nom_site VARCHAR(255) NOT NULL DEFAULT 'OVD BOUTIQUE',
        couleur_principale VARCHAR(7) NOT NULL DEFAULT '#764ba2',
        couleur_secondaire VARCHAR(7) NOT NULL DEFAULT '#667eea',
        image_fond VARCHAR(255),
        logo VARCHAR(255) DEFAULT 'image/ovd.png',
        actif BOOLEAN DEFAULT TRUE
    )
");

// Créer la table medias_promotion si elle n'existe pas
$pdo->exec("
    CREATE TABLE IF NOT EXISTS medias_promotion (
        id INT PRIMARY KEY AUTO_INCREMENT,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        chemin VARCHAR(255) NOT NULL,
        type ENUM('image', 'video') NOT NULL,
        actif BOOLEAN DEFAULT TRUE,
        date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Récupérer les paramètres actuels
$stmt = $pdo->query("SELECT * FROM parametres_site WHERE id = 1");
$parametres = $stmt->fetch();

// Récupérer les médias promotionnels
$stmt_medias = $pdo->query("SELECT * FROM medias_promotion ORDER BY date_ajout DESC");
$medias = $stmt_medias->fetchAll();

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_parametres'])) {
        // Mise à jour des paramètres généraux
        $nom_site = $_POST['nom_site'];
        $couleur_principale = $_POST['couleur_principale'];
        $couleur_secondaire = $_POST['couleur_secondaire'];
        
        // Gestion de l'upload du logo
        $logo = $parametres['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $uploadDir = 'uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['logo']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                $logo = $uploadFile;
            }
        }
        
        // Gestion de l'upload de l'image de fond
        $image_fond = $parametres['image_fond'];
        if (isset($_FILES['image_fond']) && $_FILES['image_fond']['error'] === 0) {
            $uploadDir = 'uploads/fonds/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['image_fond']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image_fond']['tmp_name'], $uploadFile)) {
                $image_fond = $uploadFile;
            }
        }
        
        if ($parametres) {
            // Mise à jour
            $stmt = $pdo->prepare("
                UPDATE parametres_site 
                SET nom_site = ?, couleur_principale = ?, couleur_secondaire = ?, 
                    logo = ?, image_fond = ?
                WHERE id = 1
            ");
            $stmt->execute([$nom_site, $couleur_principale, $couleur_secondaire, $logo, $image_fond]);
        } else {
            // Insertion
            $stmt = $pdo->prepare("
                INSERT INTO parametres_site (nom_site, couleur_principale, couleur_secondaire, logo, image_fond)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nom_site, $couleur_principale, $couleur_secondaire, $logo, $image_fond]);
        }
        
        header('Location: parametres_site.php?success=1');
        exit();
    }
    
    if (isset($_POST['add_media'])) {
        // Ajout d'un média promotionnel
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $type = $_POST['type'];
        
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
            $uploadDir = 'uploads/medias/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['media_file']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $uploadFile)) {
                $stmt = $pdo->prepare("
                    INSERT INTO medias_promotion (titre, description, chemin, type)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$titre, $description, $uploadFile, $type]);
                
                header('Location: parametres_site.php?success=2');
                exit();
            }
        }
    }
    
    if (isset($_POST['delete_media'])) {
        // Suppression d'un média
        $media_id = $_POST['media_id'];
        $stmt = $pdo->prepare("DELETE FROM medias_promotion WHERE id = ?");
        $stmt->execute([$media_id]);
        
        header('Location: parametres_site.php?success=3');
        exit();
    }
    
    if (isset($_POST['toggle_media'])) {
        // Activation/désactivation d'un média
        $media_id = $_POST['media_id'];
        $stmt = $pdo->prepare("UPDATE medias_promotion SET actif = NOT actif WHERE id = ?");
        $stmt->execute([$media_id]);
        
        header('Location: parametres_site.php?success=4');
        exit();
    }
}

// Recharger les paramètres après modification
$stmt = $pdo->query("SELECT * FROM parametres_site WHERE id = 1");
$parametres = $stmt->fetch();
$stmt_medias = $pdo->query("SELECT * FROM medias_promotion ORDER BY date_ajout DESC");
$medias = $stmt_medias->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Site | Admin OVD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .admin-container {
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
        }

        .section-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .color-preview {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
            border: 1px solid #ddd;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
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

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .media-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .media-item:hover {
            transform: translateY(-5px);
        }

        .media-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .media-info {
            padding: 1rem;
        }

        .media-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .preview-area {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #ddd;
            text-align: center;
        }

        .preview-logo {
            max-width: 200px;
            max-height: 100px;
            margin-bottom: 1rem;
        }

        .preview-bg {
            width: 100%;
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            margin-top: 1rem;
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
        }

        @media (max-width: 768px) {
            .media-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Administration</h2>
                <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php">📊 Tableau de bord</a></li>
                <li><a href="parametres_site.php" class="active">⚙️ Paramètres du site</a></li>
                <li><a href="gestion_produits.php">📦 Produits</a></li>
                <li><a href="gestion_commandes.php">📋 Commandes</a></li>
                <li><a href="gestion_clients.php">👥 Clients</a></li>
                <li><a href="gestion_messages.php">📨 Messages</a></li>
                <li><a href="index.php">🏠 Voir le site</a></li>
                <li><a href="logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>⚙️ Paramètres du Site</h1>
                <p>Personnalisez l'apparence et le contenu de votre boutique en ligne</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch($_GET['success']) {
                        case 1: echo "✅ Les paramètres ont été mis à jour avec succès !"; break;
                        case 2: echo "✅ Le média promotionnel a été ajouté avec succès !"; break;
                        case 3: echo "✅ Le média promotionnel a été supprimé avec succès !"; break;
                        case 4: echo "✅ Le statut du média a été modifié avec succès !"; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Section Paramètres généraux -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem; color: #2c3e50;">Paramètres généraux</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nom_site">Nom du site :</label>
                        <input type="text" id="nom_site" name="nom_site" class="form-control" 
                               value="<?php echo htmlspecialchars($parametres['nom_site'] ?? 'OVD BOUTIQUE'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="couleur_principale">Couleur principale :</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <input type="color" id="couleur_principale" name="couleur_principale" 
                                   value="<?php echo htmlspecialchars($parametres['couleur_principale'] ?? '#764ba2'); ?>" 
                                   style="width: 60px; height: 40px;">
                            <span id="couleur_principale_value"><?php echo htmlspecialchars($parametres['couleur_principale'] ?? '#764ba2'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="couleur_secondaire">Couleur secondaire :</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <input type="color" id="couleur_secondaire" name="couleur_secondaire" 
                                   value="<?php echo htmlspecialchars($parametres['couleur_secondaire'] ?? '#667eea'); ?>" 
                                   style="width: 60px; height: 40px;">
                            <span id="couleur_secondaire_value"><?php echo htmlspecialchars($parametres['couleur_secondaire'] ?? '#667eea'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo du site :</label>
                        <?php if (!empty($parametres['logo'])): ?>
                            <div class="preview-area">
                                <img src="<?php echo htmlspecialchars($parametres['logo']); ?>" alt="Logo actuel" class="preview-logo">
                                <p>Logo actuel</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                        <small>Format recommandé : PNG ou SVG, taille max 2MB</small>
                    </div>

                    <div class="form-group">
                        <label for="image_fond">Image de fond :</label>
                        <?php if (!empty($parametres['image_fond'])): ?>
                            <div class="preview-area">
                                <div class="preview-bg" style="background-image: url('<?php echo htmlspecialchars($parametres['image_fond']); ?>');"></div>
                                <p>Image de fond actuelle</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image_fond" name="image_fond" class="form-control" accept="image/*">
                        <small>Format recommandé : JPG ou PNG, taille max 5MB</small>
                    </div>

                    <button type="submit" name="update_parametres" class="btn btn-primary">
                        💾 Enregistrer les modifications
                    </button>
                </form>
            </div>

            <!-- Section Médias promotionnels -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem; color: #2c3e50;">Médias promotionnels</h2>
                
                <!-- Formulaire d'ajout de média -->
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem; color: #3498db;">Ajouter un nouveau média</h3>
                    
                    <div class="form-group">
                        <label for="titre">Titre du média :</label>
                        <input type="text" id="titre" name="titre" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description :</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="type">Type de média :</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="image">Image</option>
                            <option value="video">Vidéo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="media_file">Fichier média :</label>
                        <input type="file" id="media_file" name="media_file" class="form-control" required accept="image/*,video/*">
                        <small>Images: JPG, PNG, GIF (max 5MB) | Vidéos: MP4, WebM (max 20MB)</small>
                    </div>

                    <button type="submit" name="add_media" class="btn btn-success">
                        📤 Ajouter le média
                    </button>
                </form>

                <!-- Liste des médias existants -->
                <h3 style="margin-bottom: 1rem; color: #3498db;">Médias existants</h3>
                
                <?php if (count($medias) > 0): ?>
                    <div class="media-grid">
                        <?php foreach($medias as $media): ?>
                            <div class="media-item">
                                <?php if ($media['type'] == 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($media['chemin']); ?>" alt="<?php echo htmlspecialchars($media['titre']); ?>" class="media-preview">
                                <?php else: ?>
                                    <video class="media-preview" controls>
                                        <source src="<?php echo htmlspecialchars($media['chemin']); ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>
                                
                                <div class="media-info">
                                    <h4><?php echo htmlspecialchars($media['titre']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($media['description'], 0, 100)); ?></p>
                                    <p><small>Type: <?php echo $media['type']; ?></small></p>
                                    <p><small>Ajouté: <?php echo date('d/m/Y', strtotime($media['date_ajout'])); ?></small></p>
                                    <p>Statut: 
                                        <span style="color: <?php echo $media['actif'] ? '#27ae60' : '#e74c3c'; ?>">
                                            <?php echo $media['actif'] ? '✅ Actif' : '❌ Inactif'; ?>
                                        </span>
                                    </p>
                                    
                                    <div class="media-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="media_id" value="<?php echo $media['id']; ?>">
                                            <button type="submit" name="toggle_media" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.9rem;">
                                                <?php echo $media['actif'] ? 'Désactiver' : 'Activer'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce média ?');">
                                            <input type="hidden" name="media_id" value="<?php echo $media['id']; ?>">
                                            <button type="submit" name="delete_media" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.9rem;">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 2rem;">
                        Aucun média promotionnel n'a été ajouté pour le moment.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Aperçu en temps réel -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem; color: #2c3e50;">Aperçu des paramètres</h2>
                
                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <div>
                        <h3>Couleurs</h3>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem;">
                            <div class="color-preview" id="preview_couleur_principale"></div>
                            <span>Couleur principale</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem;">
                            <div class="color-preview" id="preview_couleur_secondaire"></div>
                            <span>Couleur secondaire</span>
                        </div>
                    </div>
                    
                    <div>
                        <h3>Gradient</h3>
                        <div id="preview_gradient" style="width: 200px; height: 100px; border-radius: 8px; margin-top: 1rem;"></div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <p><strong>Nom du site :</strong> <span id="preview_nom_site"></span></p>
                    <p><strong>Couleur principale :</strong> <span id="preview_couleur_principale_text"></span></p>
                    <p><strong>Couleur secondaire :</strong> <span id="preview_couleur_secondaire_text"></span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mise à jour en temps réel des aperçus
        document.addEventListener('DOMContentLoaded', function() {
            function updatePreviews() {
                // Récupérer les valeurs
                const couleurPrincipale = document.getElementById('couleur_principale').value;
                const couleurSecondaire = document.getElementById('couleur_secondaire').value;
                const nomSite = document.getElementById('nom_site').value;
                
                // Mettre à jour les aperçus de couleur
                document.getElementById('preview_couleur_principale').style.backgroundColor = couleurPrincipale;
                document.getElementById('preview_couleur_secondaire').style.backgroundColor = couleurSecondaire;
                document.getElementById('preview_gradient').style.background = `linear-gradient(135deg, ${couleurSecondaire} 0%, ${couleurPrincipale} 100%)`;
                
                // Mettre à jour les textes
                document.getElementById('couleur_principale_value').textContent = couleurPrincipale;
                document.getElementById('couleur_secondaire_value').textContent = couleurSecondaire;
                document.getElementById('preview_couleur_principale_text').textContent = couleurPrincipale;
                document.getElementById('preview_couleur_secondaire_text').textContent = couleurSecondaire;
                document.getElementById('preview_nom_site').textContent = nomSite;
            }
            
            // Écouter les changements
            document.getElementById('couleur_principale').addEventListener('input', updatePreviews);
            document.getElementById('couleur_secondaire').addEventListener('input', updatePreviews);
            document.getElementById('nom_site').addEventListener('input', updatePreviews);
            
            // Initialiser les aperçus
            updatePreviews();
        });
    </script>
</body>
</html>