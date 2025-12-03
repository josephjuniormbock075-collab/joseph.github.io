<?php
// Inclure la configuration de la base de données
require_once 'config.php';

// Récupérer les catégories
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();

// Récupérer les produits disponibles
$where = "WHERE p.statut = 'disponible'";
$params = [];

// Filtrage par catégorie
$categorie_filter = $_GET['categorie'] ?? '';
if ($categorie_filter) {
    $where .= " AND c.nom = ?";
    $params[] = $categorie_filter;
}

// Filtrage par prix
$prix_min = $_GET['prix_min'] ?? '';
$prix_max = $_GET['prix_max'] ?? '';
if ($prix_min !== '') {
    $where .= " AND p.prix >= ?";
    $params[] = floatval($prix_min);
}
if ($prix_max !== '') {
    $where .= " AND p.prix <= ?";
    $params[] = floatval($prix_max);
}

// Tri des produits
$tri = $_GET['tri'] ?? 'date_ajout';
$order_by = "ORDER BY ";
switch ($tri) {
    case 'prix-croissant':
        $order_by .= "p.prix ASC";
        break;
    case 'prix-decroissant':
        $order_by .= "p.prix DESC";
        break;
    case 'nom':
        $order_by .= "p.nom ASC";
        break;
    default:
        $order_by .= "p.date_ajout DESC";
        break;
}

// Récupérer les produits avec pagination
$page = $_GET['page'] ?? 1;
$limit = 8;
$offset = ($page - 1) * $limit;

// Compter le nombre total de produits
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    $where
");
$countStmt->execute($params);
$totalProduits = $countStmt->fetchColumn();
$totalPages = ceil($totalProduits / $limit);

// Récupérer les produits
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    $where 
    $order_by 
    LIMIT ? OFFSET ?
");
$searchParams = array_merge($params, [$limit, $offset]);
$stmt->execute($searchParams);
$produits = $stmt->fetchAll();

// Compter les produits par catégorie
$countsParCategorie = $pdo->query("
    SELECT c.nom, COUNT(p.id) as count 
    FROM categories c 
    LEFT JOIN produits p ON c.id = p.categorie_id AND p.statut = 'disponible' 
    GROUP BY c.id, c.nom
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits | OVD Boutique</title>
    <style>
    /* Reset et styles de base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #f8f9fa;
    }

    /* Header */
    header {
        background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
        color: white;
        padding: 1rem 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }

    header .logo {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    header .logo img {
        height: 50px;
        margin-right: 15px;
        border-radius: 50%;
    }

    header .logo span {
        font-size: 2rem;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    nav ul {
        display: flex;
        justify-content: center;
        list-style: none;
        flex-wrap: wrap;
    }

    nav ul li {
        margin: 0 15px;
    }

    nav ul li a {
        color: white;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 25px;
        transition: all 0.3s ease;
        font-weight: bold;
    }

    nav ul li a:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    /* Conteneur principal */
    .container {
        max-width: 1400px;
        margin: 120px auto 40px;
        padding: 0 20px;
    }

    /* Section hero produits */
    .hero-produits {
        text-align: center;
        padding: 40px 0;
        margin-bottom: 40px;
    }

    .hero-produits h1 {
        font-size: 3rem;
        color: #764ba2;
        margin-bottom: 20px;
    }

    .hero-produits p {
        font-size: 1.2rem;
        color: #666;
        max-width: 800px;
        margin: 0 auto;
    }

    /* Filtres et recherche */
    .filtres-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .filtres-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        align-items: end;
    }

    .filtre-group {
        margin-bottom: 0;
    }

    .filtre-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }

    .filtre-select, .filtre-input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        background: white;
    }

    .btn-filtrer {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-filtrer:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    /* Grille de produits */
    .produits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }

    .produit-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
    }

    .produit-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    .produit-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #66eaa4;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        z-index: 2;
    }

    .produit-badge.nouveau {
        background: #667eea;
    }

    .produit-badge.promo {
        background: #ff6b6b;
    }

    .produit-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .produit-card:hover .produit-image {
        transform: scale(1.05);
    }

    .produit-info {
        padding: 20px;
    }

    .produit-categorie {
        color: #667eea;
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .produit-nom {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .produit-description {
        color: #666;
        margin-bottom: 15px;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .produit-prix {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .prix-actuel {
        font-size: 1.5rem;
        font-weight: bold;
        color: #764ba2;
    }

    .prix-original {
        font-size: 1rem;
        color: #999;
        text-decoration: line-through;
        margin-right: 10px;
    }

    .produit-stock {
        color: #66eaa4;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .produit-stock.faible {
        color: #ff6b6b;
    }

    .produit-actions {
        display: flex;
        gap: 10px;
    }

    .btn-ajouter {
        flex: 1;
        background: linear-gradient(135deg, #66eaa4 0%, #667eea 100%);
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-ajouter:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 234, 164, 0.4);
    }

    .btn-commander-direct {
        flex: 1;
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
        margin-top: 5px;
    }

    .btn-commander-direct:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
    }

    .btn-favori {
        background: #f8f9fa;
        border: 2px solid #ddd;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-favori:hover {
        border-color: #ff6b6b;
        color: #ff6b6b;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-top: 40px;
    }

    .page-btn {
        padding: 10px 20px;
        border: 2px solid #764ba2;
        background: white;
        color: #764ba2;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }

    .page-btn.active {
        background: #764ba2;
        color: white;
    }

    .page-btn:hover:not(.active) {
        background: #f0f0f0;
    }

    /* Section catégories */
    .categories-section {
        margin-bottom: 50px;
    }

    .categories-section h2 {
        text-align: center;
        color: #764ba2;
        margin-bottom: 40px;
        font-size: 2.5rem;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }

    .categorie-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .categorie-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .categorie-card:hover .categorie-nom {
        color: white;
    }

    .categorie-icon {
        font-size: 3rem;
        margin-bottom: 15px;
    }

    .categorie-nom {
        font-size: 1.3rem;
        font-weight: bold;
        color: #764ba2;
        margin-bottom: 10px;
    }

    .categorie-count {
        color: #666;
        font-size: 0.9rem;
    }

    .categorie-card:hover .categorie-count {
        color: rgba(255,255,255,0.8);
    }

    /* Footer */
    footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 30px 0;
        margin-top: 60px;
    }

    /* Message aucun produit */
    .aucun-produit {
        text-align: center;
        padding: 60px 20px;
        color: #666;
        font-size: 1.2rem;
    }

    /* Popup confirmation */
    .popup-confirmation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .popup-confirmation.active {
        opacity: 1;
        visibility: visible;
    }

    .popup-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        transform: translateY(-20px);
        transition: all 0.3s ease;
    }

    .popup-confirmation.active .popup-content {
        transform: translateY(0);
    }

    .popup-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        justify-content: center;
    }

    .btn-popup {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-continuer {
        background: #95a5a6;
        color: white;
    }

    .btn-panier {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        nav ul {
            flex-direction: column;
            align-items: center;
        }

        nav ul li {
            margin: 5px 0;
        }

        .container {
            margin-top: 180px;
        }

        .hero-produits h1 {
            font-size: 2rem;
        }

        .filtres-grid {
            grid-template-columns: 1fr;
        }

        .produits-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }

        .produit-actions {
            flex-direction: column;
        }

        .popup-actions {
            flex-direction: column;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .produit-card, .categorie-card {
        animation: fadeInUp 0.6s ease-out;
    }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/ovd.png" alt="Logo OVD Boutique">
            <span>OVD BOUTIQUE</span>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="produits.php" style="background-color: rgba(255, 255, 255, 0.3);">Produits</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="commande.php">Commander</a></li>
                <li><a href="panier.php">Panier (<span id="count-panier">0</span>)</a></li>
                <li><a href="connexion.php">Connexion</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <!-- Section Hero -->
        <section class="hero-produits">
            <h1>Nos Produits</h1>
            <p>Découvrez notre sélection exclusive de produits de qualité, soigneusement choisis pour vous</p>
        </section>

        <!-- Section Catégories -->
        <section class="categories-section">
            <h2>Catégories</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $categorie): ?>
                    <a href="produits.php?categorie=<?php echo urlencode($categorie['nom']); ?>" class="categorie-card">
                        <div class="categorie-icon">
                            <?php 
                            // Icônes selon la catégorie
                            $icone = '📦';
                            if (stripos($categorie['nom'], 'cosmétique') !== false) $icone = '💄';
                            if (stripos($categorie['nom'], 'vêtement') !== false) $icone = '👕';
                            if (stripos($categorie['nom'], 'chaussure') !== false) $icone = '👟';
                            echo $icone;
                            ?>
                        </div>
                        <div class="categorie-nom"><?php echo htmlspecialchars($categorie['nom']); ?></div>
                        <div class="categorie-count">
                            <?php echo ($countsParCategorie[$categorie['nom']] ?? 0); ?> produits
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section Filtres -->
        <section class="filtres-section">
            <form method="GET" action="produits.php">
                <div class="filtres-grid">
                    <div class="filtre-group">
                        <label for="categorie">Catégorie</label>
                        <select id="categorie" name="categorie" class="filtre-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo htmlspecialchars($categorie['nom']); ?>"
                                    <?php if ($categorie_filter === $categorie['nom']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtre-group">
                        <label for="prix-min">Prix min (FCFA)</label>
                        <input type="number" id="prix-min" name="prix_min" class="filtre-input" 
                               placeholder="0" min="0" value="<?php echo htmlspecialchars($prix_min); ?>">
                    </div>
                    <div class="filtre-group">
                        <label for="prix-max">Prix max (FCFA)</label>
                        <input type="number" id="prix-max" name="prix_max" class="filtre-input" 
                               placeholder="1000" min="0" value="<?php echo htmlspecialchars($prix_max); ?>">
                    </div>
                    <div class="filtre-group">
                        <label for="tri">Trier par</label>
                        <select id="tri" name="tri" class="filtre-select">
                            <option value="date_ajout" <?php if ($tri === 'date_ajout') echo 'selected'; ?>>Nouveautés</option>
                            <option value="prix-croissant" <?php if ($tri === 'prix-croissant') echo 'selected'; ?>>Prix croissant</option>
                            <option value="prix-decroissant" <?php if ($tri === 'prix-decroissant') echo 'selected'; ?>>Prix décroissant</option>
                            <option value="nom" <?php if ($tri === 'nom') echo 'selected'; ?>>Nom A-Z</option>
                        </select>
                    </div>
                    <div class="filtre-group">
                        <button type="submit" class="btn-filtrer">Appliquer les filtres</button>
                        <?php if ($categorie_filter || $prix_min || $prix_max): ?>
                            <a href="produits.php" class="btn-filtrer" style="background: #e74c3c; margin-top: 10px; display: block; text-align: center; text-decoration: none;">
                                Effacer les filtres
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </section>

        <!-- Grille de produits -->
        <div class="produits-grid">
            <?php if (count($produits) > 0): ?>
                <?php foreach ($produits as $produit): ?>
                    <div class="produit-card" data-categorie="<?php echo htmlspecialchars($produit['categorie_nom']); ?>" 
                         data-prix="<?php echo $produit['prix']; ?>">
                        <?php 
                        // Badge nouveau pour les produits ajoutés récemment (moins de 30 jours)
                        $dateAjout = strtotime($produit['date_ajout']);
                        $dateNow = time();
                        $diffJours = ($dateNow - $dateAjout) / (60 * 60 * 24);
                        if ($diffJours < 30): ?>
                            <div class="produit-badge nouveau">Nouveau</div>
                        <?php endif; ?>

                        <?php if ($produit['image']): ?>
                            <img src="<?php echo htmlspecialchars($produit['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($produit['nom']); ?>" 
                                 class="produit-image"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vbiBkaXNwb25pYmxlPC90ZXh0Pjwvc3ZnPg=='">
                        <?php else: ?>
                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=" 
                                 alt="<?php echo htmlspecialchars($produit['nom']); ?>" 
                                 class="produit-image">
                        <?php endif; ?>

                        <div class="produit-info">
                            <div class="produit-categorie"><?php echo htmlspecialchars($produit['categorie_nom'] ?? 'Non catégorisé'); ?></div>
                            <h3 class="produit-nom"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                            <p class="produit-description"><?php echo htmlspecialchars($produit['description']); ?></p>
                            <div class="produit-prix">
                                <div class="prix-actuel"><?php echo number_format($produit['prix'], 2, ',', ' '); ?> FCFA</div>
                                <div class="produit-stock <?php echo $produit['stock'] < 10 ? 'faible' : ''; ?>">
                                    <?php echo $produit['stock'] < 10 ? 'Stock limité' : 'En stock'; ?>
                                </div>
                            </div>
                            <div class="produit-actions">
                                <button class="btn-ajouter" onclick="ajouterAuPanier(<?php echo $produit['id']; ?>, '<?php echo htmlspecialchars(addslashes($produit['nom'])); ?>', <?php echo $produit['prix']; ?>)">
                                    Ajouter au panier
                                </button>
                                <button class="btn-commander-direct" onclick="commanderDirect(<?php echo $produit['id']; ?>, '<?php echo htmlspecialchars(addslashes($produit['nom'])); ?>', <?php echo $produit['prix']; ?>)">
                                    Commander maintenant
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="aucun-produit">
                    <h3>Aucun produit trouvé</h3>
                    <p>Aucun produit ne correspond à vos critères de recherche.</p>
                    <a href="produits.php" class="btn-ajouter" style="display: inline-block; margin-top: 20px; text-decoration: none;">
                        Voir tous les produits
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="produits.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-btn">Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="produits.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="produits.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-btn">Suivant</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Popup de confirmation -->
    <div class="popup-confirmation" id="popupConfirmation">
        <div class="popup-content">
            <h3>Produit ajouté au panier !</h3>
            <p id="popup-message">Votre produit a été ajouté avec succès à votre panier.</p>
            <div class="popup-actions">
                <button class="btn-popup btn-continuer" onclick="fermerPopup()">Continuer mes achats</button>
                <a href="panier.php" class="btn-popup btn-panier">Voir mon panier</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 OVD Boutique - Tous droits réservés</p>
        <p>Service Client : 01 23 45 67 89 | Email : contact@ovd-boutique.fr</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation au défilement
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les produits
        document.querySelectorAll('.produit-card').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });

        // Gestion du menu responsive
        const menuToggle = document.createElement('button');
        menuToggle.innerHTML = '☰';
        menuToggle.className = 'menu-toggle';
        document.querySelector('nav').prepend(menuToggle);

        menuToggle.addEventListener('click', function() {
            const navUl = document.querySelector('nav ul');
            navUl.classList.toggle('show');
        });

        // Styles pour le menu toggle
        const style = document.createElement('style');
        style.textContent = `
            .menu-toggle {
                display: none;
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                position: absolute;
                right: 20px;
                top: 10px;
            }
            
            @media (max-width: 768px) {
                .menu-toggle {
                    display: block;
                }
                nav ul {
                    display: none;
                    flex-direction: column;
                    width: 100%;
                }
                nav ul.show {
                    display: flex;
                }
            }
        `;
        document.head.appendChild(style);

        // Mettre à jour le compteur du panier au chargement
        mettreAJourCompteurPanier();
    });

    // Fonction pour ajouter au panier
    function ajouterAuPanier(produitId, produitNom, produitPrix) {
        // Récupérer le panier actuel depuis le localStorage
        let panier = JSON.parse(localStorage.getItem('panier')) || [];
        
        // Vérifier si le produit est déjà dans le panier
        const produitExistant = panier.find(item => item.id === produitId);
        
        if (produitExistant) {
            produitExistant.quantite += 1;
        } else {
            panier.push({
                id: produitId,
                nom: produitNom,
                prix: produitPrix,
                quantite: 1
            });
        }
        
        // Sauvegarder le panier
        localStorage.setItem('panier', JSON.stringify(panier));
        
        // Mettre à jour le compteur du panier
        mettreAJourCompteurPanier();
        
        // Afficher le popup de confirmation
        document.getElementById('popup-message').textContent = `${produitNom} a été ajouté avec succès à votre panier.`;
        document.getElementById('popupConfirmation').classList.add('active');
        
        // Animation du bouton
        const bouton = event.target;
        const texteOriginal = bouton.innerHTML;
        bouton.innerHTML = '✓ Ajouté !';
        bouton.style.background = '#66eaa4';
        
        setTimeout(() => {
            bouton.innerHTML = texteOriginal;
            bouton.style.background = '';
        }, 2000);
    }

    // Fonction pour commander directement
    function commanderDirect(produitId, produitNom, produitPrix) {
        // Vider le panier actuel
        localStorage.removeItem('panier');
        
        // Ajouter uniquement ce produit au panier
        const panier = [{
            id: produitId,
            nom: produitNom,
            prix: produitPrix,
            quantite: 1
        }];
        
        // Sauvegarder le panier
        localStorage.setItem('panier', JSON.stringify(panier));
        
        // Mettre à jour le compteur du panier
        mettreAJourCompteurPanier();
        
        // Rediriger directement vers la page de commande
        window.location.href = 'commande.php';
    }

    // Fonction pour fermer le popup
    function fermerPopup() {
        document.getElementById('popupConfirmation').classList.remove('active');
    }

    // Fonction pour mettre à jour le compteur du panier
    function mettreAJourCompteurPanier() {
        const panier = JSON.parse(localStorage.getItem('panier')) || [];
        const totalArticles = panier.reduce((total, item) => total + item.quantite, 0);
        
        // Mettre à jour l'affichage du compteur
        const compteurPanier = document.getElementById('count-panier');
        if (compteurPanier) {
            compteurPanier.textContent = totalArticles;
        }
    }

    // Fonction pour afficher les messages
    function showMessage(message, type = 'success') {
        const messageDiv = document.createElement('div');
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: ${type === 'success' ? '#66eaa4' : '#e74c3c'};
            color: white;
            border-radius: 8px;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    // Ajout du style d'animation pour le message
    const styleAnimation = document.createElement('style');
    styleAnimation.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(styleAnimation);
    </script>
</body>
</html>