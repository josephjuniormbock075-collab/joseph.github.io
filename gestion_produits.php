<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: connexion.php');
    exit();
}

// Inclure la configuration de la base de données
require_once 'config.php';

// Traitement des actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Ajouter un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_produit'])) {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $categorie_id = intval($_POST['categorie_id']);
    $statut = $_POST['statut'];
    
    // Gestion de l'upload d'image
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = 'uploads/produits/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . $_FILES['image']['name'];
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = $uploadFile;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO produits (nom, description, prix, stock, categorie_id, image, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $description, $prix, $stock, $categorie_id, $image, $statut]);
        
        $_SESSION['success'] = "Produit ajouté avec succès!";
        header('Location: gestion_produits.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout: " . $e->getMessage();
    }
}

// Modifier un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_produit'])) {
    $id = intval($_POST['id']);
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $categorie_id = intval($_POST['categorie_id']);
    $statut = $_POST['statut'];
    
    // Gestion de l'upload d'image
    $image = $_POST['image_actuelle'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = 'uploads/produits/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . $_FILES['image']['name'];
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            // Supprimer l'ancienne image si elle existe
            if ($image && file_exists($image)) {
                unlink($image);
            }
            $image = $uploadFile;
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE produits SET nom = ?, description = ?, prix = ?, stock = ?, categorie_id = ?, image = ?, statut = ? WHERE id = ?");
        $stmt->execute([$nom, $description, $prix, $stock, $categorie_id, $image, $statut, $id]);
        
        $_SESSION['success'] = "Produit modifié avec succès!";
        header('Location: gestion_produits.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification: " . $e->getMessage();
    }
}

// Supprimer un produit
if ($action === 'supprimer' && $id > 0) {
    try {
        // Récupérer l'image pour la supprimer
        $stmt = $pdo->prepare("SELECT image FROM produits WHERE id = ?");
        $stmt->execute([$id]);
        $produit = $stmt->fetch();
        
        if ($produit && $produit['image'] && file_exists($produit['image'])) {
            unlink($produit['image']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Produit supprimé avec succès!";
        header('Location: gestion_produits.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupérer les catégories
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();

// Récupérer les produits avec pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Recherche
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE p.nom LIKE ? OR p.description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Compter le nombre total de produits
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produits p $where");
$countStmt->execute($params);
$totalProduits = $countStmt->fetchColumn();
$totalPages = ceil($totalProduits / $limit);

// Récupérer les produits
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    $where 
    ORDER BY p.date_ajout DESC 
    LIMIT ? OFFSET ?
");
$searchParams = $params;
$searchParams[] = $limit;
$searchParams[] = $offset;
$stmt->execute($searchParams);
$produits = $stmt->fetchAll();

// Récupérer un produit pour modification
$produit_edition = null;
if ($action === 'modifier' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    $produit_edition = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits | Admin OVD Boutique</title>
    <style>
        /* Styles du dashboard admin (reprise du dashboard_admin.php) */
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

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
            text-align: center;
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

        /* Styles spécifiques à la gestion des produits */
        .form-container {
            background: white;
            padding: 2rem;
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
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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

        .badge-success { background: #d1ecf1; color: #0c5460; }
        .badge-danger { background: #f8d7da; color: #721c24; }
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

        .btn-view { background: #3498db; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-delete { background: #e74c3c; color: white; }

        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-form input {
            flex: 1;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 3px;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
        }

        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .alert {
            padding: 1rem;
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

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
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
                <li><a href="gestion_produits.php" class="active">📦 Produits</a></li>
                <li><a href="gestion_categories.php">🗂️ Catégories</a></li>
                <li><a href="gestion_commandes.php">📋 Commandes</a></li>
                <li><a href="gestion_clients.php">👥 Clients</a></li>
                
                <div class="menu-section">COMPTE</div>
                <li><a href="profil_admin.php">👤 Mon profil</a></li>
                <li><a href="logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Gestion des Produits</h1>
                <p>Ajouter, modifier ou supprimer des produits de votre boutique</p>
            </div>

            <!-- Messages de succès/erreur -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Formulaire d'ajout/modification -->
            <div class="form-container">
                <h2><?php echo $produit_edition ? 'Modifier le produit' : 'Ajouter un nouveau produit'; ?></h2>
                <form method="POST" enctype="multipart/form-data" action="gestion_produits.php">
                    <?php if ($produit_edition): ?>
                        <input type="hidden" name="id" value="<?php echo $produit_edition['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="nom">Nom du produit *</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo $produit_edition['nom'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo $produit_edition['description'] ?? ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="prix">Prix (FCFA) *</label>
                        <input type="number" id="prix" name="prix" class="form-control" step="0.01" min="0"
                               value="<?php echo $produit_edition['prix'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock *</label>
                        <input type="number" id="stock" name="stock" class="form-control" min="0"
                               value="<?php echo $produit_edition['stock'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="categorie_id">Catégorie *</label>
                        <select id="categorie_id" name="categorie_id" class="form-control" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo $categorie['id']; ?>"
                                    <?php if (isset($produit_edition['categorie_id']) && $produit_edition['categorie_id'] == $categorie['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">Image du produit</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <?php if ($produit_edition && $produit_edition['image']): ?>
                            <input type="hidden" name="image_actuelle" value="<?php echo $produit_edition['image']; ?>">
                            <p>Image actuelle: 
                                <a href="<?php echo $produit_edition['image']; ?>" target="_blank">Voir l'image</a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="statut">Statut *</label>
                        <select id="statut" name="statut" class="form-control" required>
                            <option value="disponible" <?php if (isset($produit_edition['statut']) && $produit_edition['statut'] === 'disponible') echo 'selected'; ?>>Disponible</option>
                            <option value="indisponible" <?php if (isset($produit_edition['statut']) && $produit_edition['statut'] === 'indisponible') echo 'selected'; ?>>Indisponible</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <?php if ($produit_edition): ?>
                            <button type="submit" name="modifier_produit" class="btn btn-warning">Modifier le produit</button>
                            <a href="gestion_produits.php" class="btn btn-primary">Annuler</a>
                        <?php else: ?>
                            <button type="submit" name="ajouter_produit" class="btn btn-success">Ajouter le produit</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Recherche -->
            <div class="search-form">
                <form method="GET" action="gestion_produits.php" style="display: flex; width: 100%; gap: 1rem;">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher un produit..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                    <?php if ($search): ?>
                        <a href="gestion_produits.php" class="btn btn-danger">Effacer</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Liste des produits -->
            <div class="table-container">
                <h2>Liste des produits (<?php echo $totalProduits; ?>)</h2>
                
                <?php if (count($produits) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Statut</th>
                                <th>Date d'ajout</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $produit): ?>
                                <tr>
                                    <td>
                                        <?php if ($produit['image']): ?>
                                            <img src="<?php echo $produit['image']; ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="product-image">
                                        <?php else: ?>
                                            <span>Aucune image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($produit['categorie_nom'] ?? 'Non catégorisé'); ?></td>
                                    <td><?php echo number_format($produit['prix'], 2, ',', ' '); ?> FCFA</td>
                                    <td>
                                        <span class="badge <?php echo $produit['stock'] < 10 ? 'badge-danger' : 'badge-success'; ?>">
                                            <?php echo $produit['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $produit['statut'] === 'disponible' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($produit['statut']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($produit['date_ajout'])); ?></td>
                                    <td>
                                        <a href="gestion_produits.php?action=modifier&id=<?php echo $produit['id']; ?>" class="action-btn btn-edit">Modifier</a>
                                        <a href="gestion_produits.php?action=supprimer&id=<?php echo $produit['id']; ?>" 
                                           class="action-btn btn-delete" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="gestion_produits.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #666;">
                        <?php echo $search ? 'Aucun produit trouvé pour votre recherche.' : 'Aucun produit disponible.'; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Confirmation de suppression
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>