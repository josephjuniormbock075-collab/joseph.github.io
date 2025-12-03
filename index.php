<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accueil | BoutiquePro</title>
  <style>
  /* Reset et styles de base */
  <?php
  // Connexion à la base de données pour récupérer les paramètres
  session_start();
  $host = 'localhost';
  $dbname = 'boutique_pro';
  $username = 'root';
  $password = '';
  
  try {
      $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
      // Récupérer les paramètres du site
      $stmt = $pdo->query("SELECT * FROM parametres_site WHERE id = 1");
      $parametres = $stmt->fetch();
      
      // Si pas de paramètres, créer des valeurs par défaut
      if (!$parametres) {
          $parametres = [
              'nom_site' => 'OVD BOUTIQUE',
              'couleur_principale' => '#764ba2',
              'couleur_secondaire' => '#667eea',
              'image_fond' => '',
              'logo' => 'image/ovd.png'
          ];
      }
  } catch(PDOException $e) {
      $parametres = [
          'nom_site' => 'OVD BOUTIQUE',
          'couleur_principale' => '#764ba2',
          'couleur_secondaire' => '#667eea',
          'image_fond' => '',
          'logo' => 'image/ovd.png'
      ];
  }
  
  // Déterminer la couleur principale
  $couleur_principale = $parametres['couleur_principale'];
  $couleur_secondaire = $parametres['couleur_secondaire'] ?? '#667eea';
  $nom_site = $parametres['nom_site'];
  $logo = $parametres['logo'];
  $image_fond = $parametres['image_fond'];
  ?>
  
  :root {
    --couleur-principale: <?php echo $couleur_principale; ?>;
    --couleur-secondaire: <?php echo $couleur_secondaire; ?>;
    --couleur-gradient: linear-gradient(135deg, var(--couleur-secondaire) 0%, var(--couleur-principale) 100%);
  }

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
    <?php if ($image_fond): ?>
    background-image: url('<?php echo $image_fond; ?>');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    background-blend-mode: overlay;
    background-color: rgba(248, 249, 250, 0.9);
    <?php endif; ?>
  }

  /* Header */
  header {
    background: var(--couleur-gradient);
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
    font-weight: 500;
  }

  nav ul li a:hover {
    background-color: rgba(255,255,255,0.2);
    transform: translateY(-2px);
  }

  /* Navigation selon rôle */
  .user-nav {
    background: rgba(0,0,0,0.1);
    padding: 10px;
    border-radius: 10px;
    margin-top: 10px;
  }

  .user-nav a {
    background: rgba(255,255,255,0.3);
    margin: 0 5px;
  }

  /* Main content */
  main {
    margin-top: 140px;
    padding: 2rem;
    min-height: calc(100vh - 200px);
  }

  .welcome {
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 3rem 2rem;
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  }

  .welcome h1 {
    font-size: 3rem;
    margin-bottom: 1.5rem;
    background: var(--couleur-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .welcome p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    color: #666;
    line-height: 1.8;
  }

  .btn {
    display: inline-block;
    background: var(--couleur-gradient);
    color: white;
    padding: 15px 30px;
    text-decoration: none;
    border-radius: 30px;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
  }

  .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
  }

  /* Footer */
  footer {
    background: #333;
    color: white;
    text-align: center;
    padding: 2rem 0;
    margin-top: 3rem;
  }

  /* Section produits */
  .products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
  }

  .product-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
  }

  .product-card:hover {
    transform: translateY(-5px);
  }

  .product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
  }

  .product-info {
    padding: 1.5rem;
  }

  .product-info h3 {
    color: var(--couleur-principale);
    margin-bottom: 0.5rem;
  }

  .price {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--couleur-secondaire);
    margin: 1rem 0;
  }

  /* Section médias promotionnels */
  .promotion-section {
    margin: 4rem 0;
    text-align: center;
  }

  .promotion-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
  }

  .media-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }

  .media-card img, .media-card video {
    width: 100%;
    height: 250px;
    object-fit: cover;
  }

  .media-info {
    padding: 1.5rem;
  }

  .media-info h3 {
    color: var(--couleur-principale);
    margin-bottom: 1rem;
  }

  /* Modal pour les détails du produit */
  .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 2000;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: modalFadeIn 0.3s ease;
  }

  .modal-header {
    background: var(--couleur-gradient);
    color: white;
    padding: 1.5rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-header h2 {
    margin: 0;
  }

  .close-modal {
    background: none;
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
  }

  .modal-body {
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
  }

  .modal-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 10px;
  }

  .modal-details h3 {
    color: var(--couleur-principale);
    margin-bottom: 1rem;
  }

  .detail-item {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
  }

  .detail-label {
    font-weight: bold;
    color: #666;
    display: inline-block;
    width: 150px;
  }

  .detail-value {
    color: #333;
  }

  .stock-available {
    color: #28a745;
    font-weight: bold;
  }

  .stock-low {
    color: #ffc107;
    font-weight: bold;
  }

  .stock-unavailable {
    color: #dc3545;
    font-weight: bold;
  }

  .modal-footer {
    padding: 1.5rem;
    text-align: center;
    border-top: 1px solid #eee;
  }

  @keyframes modalFadeIn {
    from {
      opacity: 0;
      transform: translateY(-50px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    header .logo span {
      font-size: 1.5rem;
    }
    
    nav ul {
      flex-direction: column;
      align-items: center;
    }
    
    nav ul li {
      margin: 5px 0;
    }
    
    .welcome h1 {
      font-size: 2rem;
    }
    
    main {
      margin-top: 180px;
      padding: 1rem;
    }
    
    .promotion-grid {
      grid-template-columns: 1fr;
    }
    
    .modal-body {
      grid-template-columns: 1fr;
    }
  }

  /* Animation */
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .welcome, .product-card, .media-card {
    animation: fadeIn 1s ease-out;
  }
  </style>
</head>
<body>
  <?php
  // Récupérer les produits à afficher
  try {
      $stmt_produits = $pdo->query("SELECT p.*, c.nom as categorie_nom FROM produits p 
                                   LEFT JOIN categories c ON p.categorie_id = c.id 
                                   WHERE p.statut = 'disponible' LIMIT 6");
      $produits = $stmt_produits->fetchAll();
      
      // Récupérer les médias promotionnels
      $stmt_medias = $pdo->query("SELECT * FROM medias_promotion WHERE actif = 1");
      $medias = $stmt_medias->fetchAll();
  } catch(PDOException $e) {
      $produits = [];
      $medias = [];
  }
  ?>
  
  <header>
    <div class="logo">
      <img src="<?php echo $logo; ?>" alt="Logo">
      <span><?php echo htmlspecialchars($nom_site); ?></span>
    </div>
    <nav>
      <ul>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="produits.php">Produits</a></li>
        <li><a href="commande.php">Commander</a></li>
        <li><a href="panier.php">Panier</a></li>
        
        <?php if(isset($_SESSION['user_id'])): ?>
          <!-- Navigation selon le rôle -->
          <li class="user-nav">
            <?php if($_SESSION['role'] == 'admin'): ?>
              <a href="dashboard_admin.php" class="btn-admin">Tableau de Bord Admin</a>
            <?php elseif($_SESSION['role'] == 'client'): ?>
              <a href="dashboard_client.php" class="btn-client">Mon Compte</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
          </li>
        <?php else: ?>
          <li><a href="connexion.php">Connexion</a></li>
        <?php endif; ?>
        
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </nav>
  </header>
  
  <main>
    <section class="welcome">
      <h1>Bienvenue chez <?php echo htmlspecialchars($nom_site); ?></h1>
      <p>
        Votre boutique en ligne spécialisée dans la vente de produits cosmétiques, vêtements et chaussures. 
        Nous vous offrons une expérience de shopping fluide, rapide et fiable.
      </p>
      <a href="commande.php" class="btn">Commander maintenant</a>
    </section>

    <!-- Section des produits -->
    <section class="promotion-section">
      <h2 style="color: var(--couleur-principale); margin-bottom: 2rem;">Nos Produits Populaires</h2>
      <div class="products">
        <?php foreach($produits as $produit): ?>
          <div class="product-card">
            <?php if($produit['image']): ?>
              <img src="<?php echo $produit['image']; ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>">
            <?php else: ?>
              <img src="https://via.placeholder.com/300x200/<?php echo substr($couleur_principale, 1); ?>/ffffff?text=<?php echo urlencode($produit['nom']); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>">
            <?php endif; ?>
            <div class="product-info">
              <h3><?php echo htmlspecialchars($produit['nom']); ?></h3>
              <p><?php echo htmlspecialchars(substr($produit['description'], 0, 100)); ?>...</p>
              <div class="price"><?php echo number_format($produit['prix'], 2, ',', ' '); ?> FCFA</div>
              <button class="btn view-details" data-product-id="<?php echo $produit['id']; ?>" style="padding: 10px 20px; font-size: 0.9rem;">Voir détails</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Section des médias promotionnels -->
    <section class="promotion-section">
      <h2 style="color: var(--couleur-principale); margin-bottom: 2rem;">Découvrez notre boutique</h2>
      <div class="promotion-grid">
        <?php if(count($medias) > 0): ?>
          <?php foreach($medias as $media): ?>
            <div class="media-card">
              <?php if($media['type'] == 'image'): ?>
                <img src="<?php echo $media['chemin']; ?>" alt="<?php echo htmlspecialchars($media['titre']); ?>">
              <?php elseif($media['type'] == 'video'): ?>
                <video controls>
                  <source src="<?php echo $media['chemin']; ?>" type="video/mp4">
                  Votre navigateur ne supporte pas la vidéo.
                </video>
              <?php endif; ?>
              <div class="media-info">
                <h3><?php echo htmlspecialchars($media['titre']); ?></h3>
                <p><?php echo htmlspecialchars($media['description']); ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Médias par défaut -->
          <div class="media-card">
            <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Boutique en ligne">
            <div class="media-info">
              <h3>Expérience de shopping unique</h3>
              <p>Découvrez nos produits de qualité avec une livraison rapide</p>
            </div>
          </div>
          <div class="media-card">
            <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promotions">
            <div class="media-info">
              <h3>Promotions exclusives</h3>
              <p>Profitez de nos offres spéciales toute l'année</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  
  <!-- Modal pour les détails du produit -->
  <div id="productModal" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Détails du produit</h2>
        <button class="close-modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="modal-image-container">
          <img id="modalProductImage" class="modal-image" src="" alt="Produit">
        </div>
        <div class="modal-details">
          <h3 id="modalProductName"></h3>
          <p id="modalProductDescription"></p>
          
          <div class="detail-item">
            <span class="detail-label">Prix:</span>
            <span class="detail-value" id="modalProductPrice"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Stock disponible:</span>
            <span class="detail-value" id="modalProductStock"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Catégorie:</span>
            <span class="detail-value" id="modalProductCategory"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Date d'ajout:</span>
            <span class="detail-value" id="modalProductDate"></span>
          </div>
          
          <div class="detail-item">
            <span class="detail-label">Statut:</span>
            <span class="detail-value" id="modalProductStatus"></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" id="modalOrderButton" style="margin-right: 10px;">Commander maintenant</a>
        <button class="btn close-modal" style="background: #6c757d;">Fermer</button>
      </div>
    </div>
  </div>
  
  <footer>
    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($nom_site); ?> - Tous droits réservés.</p>
  </footer>

  <script>
  // Script principal pour l'interactivité du site
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

      // Observer les éléments à animer
      document.querySelectorAll('.product-card, .media-card').forEach(card => {
          card.style.opacity = '0';
          card.style.transform = 'translateY(30px)';
          card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
          observer.observe(card);
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

      // Gestion des modaux de détails produits
      const modal = document.getElementById('productModal');
      const closeModalButtons = document.querySelectorAll('.close-modal');
      const viewDetailsButtons = document.querySelectorAll('.view-details');
      
      // Fonction pour afficher les détails du produit
      function showProductDetails(productId) {
          fetch(`get_product_details.php?id=${productId}`)
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      const product = data.product;
                      
                      // Mettre à jour le contenu du modal
                      document.getElementById('modalProductName').textContent = product.nom;
                      document.getElementById('modalProductDescription').textContent = product.description;
                      document.getElementById('modalProductPrice').textContent = parseFloat(product.prix).toFixed(2) + ' FCFA';
                      
                      // Gérer le stock
                      const stockElement = document.getElementById('modalProductStock');
                      if (product.stock > 20) {
                          stockElement.textContent = product.stock + ' unités';
                          stockElement.className = 'detail-value stock-available';
                      } else if (product.stock > 0) {
                          stockElement.textContent = product.stock + ' unités (stock faible)';
                          stockElement.className = 'detail-value stock-low';
                      } else {
                          stockElement.textContent = 'Rupture de stock';
                          stockElement.className = 'detail-value stock-unavailable';
                      }
                      
                      document.getElementById('modalProductCategory').textContent = product.categorie_nom || 'Non catégorisé';
                      
                      // Formater la date
                      const date = new Date(product.date_ajout);
                      const formattedDate = date.toLocaleDateString('fr-FR', {
                          day: '2-digit',
                          month: '2-digit',
                          year: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit'
                      });
                      document.getElementById('modalProductDate').textContent = formattedDate;
                      
                      // Statut
                      const statusElement = document.getElementById('modalProductStatus');
                      if (product.statut === 'disponible') {
                          statusElement.textContent = 'Disponible';
                          statusElement.className = 'detail-value stock-available';
                      } else {
                          statusElement.textContent = 'Indisponible';
                          statusElement.className = 'detail-value stock-unavailable';
                      }
                      
                      // Image du produit
                      const productImage = document.getElementById('modalProductImage');
                      if (product.image) {
                          productImage.src = product.image;
                      } else {
                          productImage.src = `https://via.placeholder.com/400x300/<?php echo substr($couleur_principale, 1); ?>/ffffff?text=${encodeURIComponent(product.nom)}`;
                      }
                      
                      // Lien de commande
                      document.getElementById('modalOrderButton').href = `commande.php?product_id=${product.id}`;
                      
                      // Afficher le modal
                      modal.style.display = 'flex';
                  } else {
                      showMessage('Erreur lors du chargement des détails du produit', 'error');
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  showMessage('Erreur de connexion au serveur', 'error');
              });
      }
      
      // Ajouter les événements aux boutons "Voir détails"
      viewDetailsButtons.forEach(button => {
          button.addEventListener('click', function() {
              const productId = this.getAttribute('data-product-id');
              showProductDetails(productId);
          });
      });
      
      // Fermer le modal
      function closeModal() {
          modal.style.display = 'none';
      }
      
      closeModalButtons.forEach(button => {
          button.addEventListener('click', closeModal);
      });
      
      // Fermer le modal en cliquant en dehors
      modal.addEventListener('click', function(event) {
          if (event.target === modal) {
              closeModal();
          }
      });
      
      // Fermer avec la touche Echap
      document.addEventListener('keydown', function(event) {
          if (event.key === 'Escape') {
              closeModal();
          }
      });

      // Ajout de styles pour le menu toggle
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
              .user-nav {
                  flex-direction: column;
              }
              .user-nav a {
                  margin: 5px 0;
              }
          }
      `;
      document.head.appendChild(style);
  });

  // Fonction pour afficher les messages
  function showMessage(message, type = 'success') {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${type}`;
      messageDiv.textContent = message;
      messageDiv.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          padding: 15px 20px;
          border-radius: 5px;
          color: white;
          z-index: 10000;
          animation: slideIn 0.3s ease;
      `;
      
      if (type === 'success') {
          messageDiv.style.background = '<?php echo $couleur_principale; ?>';
      } else {
          messageDiv.style.background = '#dc3545';
      }
      
      document.body.appendChild(messageDiv);
      
      setTimeout(() => {
          messageDiv.remove();
      }, 3000);
  }
  </script>
</body>
</html>