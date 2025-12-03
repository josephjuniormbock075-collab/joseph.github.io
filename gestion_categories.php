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

// Traitement des actions
$message = '';
$message_type = '';

// Ajouter une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_categorie'])) {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
        $stmt->execute([$nom, $description]);
        $message = "Catégorie ajoutée avec succès !";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Erreur lors de l'ajout : " . $e->getMessage();
        $message_type = "error";
    }
}

// Modifier une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_categorie'])) {
    $id = $_POST['id'];
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET nom = ?, description = ? WHERE id = ?");
        $stmt->execute([$nom, $description, $id]);
        $message = "Catégorie modifiée avec succès !";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Erreur lors de la modification : " . $e->getMessage();
        $message_type = "error";
    }
}

// Supprimer une catégorie
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'];
    
    try {
        // Vérifier si la catégorie est utilisée par des produits
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $message = "Impossible de supprimer cette catégorie : elle est utilisée par des produits.";
            $message_type = "error";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Catégorie supprimée avec succès !";
            $message_type = "success";
        }
    } catch(PDOException $e) {
        $message = "Erreur lors de la suppression : " . $e->getMessage();
        $message_type = "error";
    }
}

// Récupérer toutes les catégories
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as nombre_produits 
    FROM categories c 
    LEFT JOIN produits p ON c.id = p.categorie_id 
    GROUP BY c.id 
    ORDER BY c.nom
")->fetchAll();

// Récupérer une catégorie pour modification
$categorie_a_modifier = null;
if (isset($_GET['modifier'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['modifier']]);
    $categorie_a_modifier = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories | OVD Boutique</title>
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
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #3498db;
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

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Formulaires */
        .form-container {
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
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Table */
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

        .btn-edit { background: #f39c12; color: white; }
        .btn-delete { background: #e74c3c; color: white; }

        /* Messages */
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
                <li><a href="gestion_categories.php" class="active">🗂️ Catégories</a></li>
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
                    <h1>🗂️ Gestion des Catégories</h1>
                    <p>Créez et gérez les catégories de produits</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard_admin.php" class="btn btn-primary">← Retour au dashboard</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout/modification -->
            <div class="form-container">
                <h2><?php echo $categorie_a_modifier ? 'Modifier la catégorie' : 'Ajouter une nouvelle catégorie'; ?></h2>
                <form method="POST">
                    <?php if ($categorie_a_modifier): ?>
                        <input type="hidden" name="id" value="<?php echo $categorie_a_modifier['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nom">Nom de la catégorie *</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo $categorie_a_modifier ? htmlspecialchars($categorie_a_modifier['nom']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"><?php echo $categorie_a_modifier ? htmlspecialchars($categorie_a_modifier['description']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" name="<?php echo $categorie_a_modifier ? 'modifier_categorie' : 'ajouter_categorie'; ?>" 
                            class="btn btn-success">
                        <?php echo $categorie_a_modifier ? '💾 Modifier la catégorie' : '➕ Ajouter la catégorie'; ?>
                    </button>
                    
                    <?php if ($categorie_a_modifier): ?>
                        <a href="gestion_categories.php" class="btn btn-primary">❌ Annuler</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Liste des catégories -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Liste des catégories (<?php echo count($categories); ?>)</h2>
                </div>
                
                <?php if (count($categories) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Nombre de produits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categories as $categorie): ?>
                            <tr>
                                <td><?php echo $categorie['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($categorie['nom']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($categorie['description'] ?: 'Aucune description'); ?></td>
                                <td>
                                    <span class="badge badge-success"><?php echo $categorie['nombre_produits']; ?> produits</span>
                                </td>
                                <td>
                                    <a href="gestion_categories.php?modifier=<?php echo $categorie['id']; ?>" 
                                       class="action-btn btn-edit">✏️ Modifier</a>
                                    <a href="gestion_categories.php?supprimer=<?php echo $categorie['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">🗑️ Supprimer</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune catégorie n'a été créée pour le moment.</p>
                <?php endif; ?>
            </div>

            <!-- Statistiques des catégories -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📊 Statistiques des catégories</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div style="background: #e8f4fd; padding: 1rem; border-radius: 5px; text-align: center;">
                        <h3 style="color: #3498db; margin-bottom: 0.5rem;"><?php echo count($categories); ?></h3>
                        <p>Catégories totales</p>
                    </div>
                    <div style="background: #e8f6f3; padding: 1rem; border-radius: 5px; text-align: center;">
                        <h3 style="color: #27ae60; margin-bottom: 0.5rem;">
                            <?php echo array_sum(array_column($categories, 'nombre_produits')); ?>
                        </h3>
                        <p>Produits total</p>
                    </div>
                    <div style="background: #fef9e7; padding: 1rem; border-radius: 5px; text-align: center;">
                        <h3 style="color: #f39c12; margin-bottom: 0.5rem;">
                            <?php 
                            $moyenne = count($categories) > 0 ? array_sum(array_column($categories, 'nombre_produits')) / count($categories) : 0;
                            echo number_format($moyenne, 1);
                            ?>
                        </h3>
                        <p>Moyenne produits/catégorie</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>