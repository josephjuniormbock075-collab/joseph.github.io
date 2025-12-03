<?php
session_start();
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier | OVD Boutique</title>
    <style>
        /* [Tous les styles CSS restent identiques] */
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
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        /* Titre de la page */
        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: #764ba2;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Layout du panier */
        .panier-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        @media (max-width: 768px) {
            .panier-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Section articles du panier */
        .panier-articles {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #764ba2;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Article du panier */
        .article-panier {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            gap: 20px;
        }

        .article-panier:last-child {
            border-bottom: none;
        }

        .article-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            flex-shrink: 0;
        }

        .article-details {
            flex: 1;
        }

        .article-nom {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .article-prix {
            font-size: 1.1rem;
            color: #764ba2;
            font-weight: bold;
        }

        .article-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .quantite-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-quantite {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-quantite:hover {
            background: #f8f9fa;
            border-color: #764ba2;
        }

        .quantite {
            font-size: 1.1rem;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }

        .btn-supprimer {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-supprimer:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .sous-total {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-left: auto;
            min-width: 100px;
            text-align: right;
        }

        /* Panier vide */
        .panier-vide {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .panier-vide .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .panier-vide h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }

        .btn-principal {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-principal:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        /* Section récapitulatif */
        .panier-recap {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 140px;
        }

        .recap-ligne {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .recap-ligne.total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #764ba2;
            border-bottom: 2px solid #764ba2;
            margin-top: 20px;
        }

        .recap-label {
            color: #666;
        }

        .recap-valeur {
            font-weight: bold;
        }

        .btn-commander {
            width: 100%;
            margin-top: 25px;
            padding: 18px;
            font-size: 1.1rem;
        }

        .btn-secondaire {
            display: inline-block;
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }

        .btn-secondaire:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .actions-panier {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        /* Message d'alerte stock */
        .alerte-stock {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        /* Footer */
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 30px 0;
            margin-top: 60px;
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
            
            .container {
                margin-top: 180px;
            }
            
            .article-panier {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .article-actions {
                justify-content: center;
            }
            
            .sous-total {
                margin-left: 0;
                text-align: center;
            }
            
            .panier-recap {
                position: static;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .panier-articles, .panier-recap {
            animation: fadeIn 0.6s ease-out;
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
                <li><a href="produits.php">Produits</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="commande.php">Commander</a></li>
                <li><a href="panier.php" style="background-color: rgba(255, 255, 255, 0.3);">Panier (<span id="count-panier">0</span>)</a></li>
                <li><a href="connexion.php">Connexion</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="page-title">
            <h1>Votre Panier</h1>
            <p>Revoyez vos articles et passez à la caisse</p>
        </div>

        <div class="panier-layout">
            <!-- Section articles du panier -->
            <div class="panier-articles">
                <h2 class="section-title">Articles dans votre panier</h2>
                
                <div id="contenu-panier">
                    <!-- Le contenu du panier sera généré dynamiquement par JavaScript -->
                </div>

                <div id="panier-vide" class="panier-vide" style="display: none;">
                    <div class="icon">🛒</div>
                    <h3>Votre panier est vide</h3>
                    <p>Découvrez nos produits et ajoutez-les à votre panier</p>
                    <a href="produits.php" class="btn-principal">Découvrir nos produits</a>
                </div>
            </div>

            <!-- Section récapitulatif -->
            <div class="panier-recap">
                <h2 class="section-title">Récapitulatif</h2>
                
                <div class="recap-ligne">
                    <span class="recap-label">Sous-total</span>
                    <span class="recap-valeur" id="sous-total">0,00 FCFA</span>
                </div>
                
                <div class="recap-ligne">
                    <span class="recap-label">Livraison</span>
                    <span class="recap-valeur" id="livraison">0,00 FCFA</span>
                </div>
                
                <div class="recap-ligne total">
                    <span class="recap-label">Total</span>
                    <span class="recap-valeur" id="total">0,00 FCFA</span>
                </div>

                <button class="btn-principal btn-commander" id="btn-commander" disabled>
                    Commander maintenant
                </button>

                <div class="actions-panier">
                    <button class="btn-secondaire" onclick="viderPanier()" id="btn-vider" style="display: none;">
                        Vider le panier
                    </button>
                    <a href="produits.php" class="btn-secondaire">
                        Continuer mes achats
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 OVD Boutique - Tous droits réservés</p>
        <p>Service Client : 01 23 45 67 89 | Email : contact@ovd-boutique.fr</p>
    </footer>

    <script>
    // Fonction pour récupérer le panier depuis le localStorage
    function getPanier() {
        const panier = JSON.parse(localStorage.getItem('panier')) || [];
        console.log('Panier récupéré:', panier);
        return panier;
    }

    // Fonction pour sauvegarder le panier dans le localStorage
    function savePanier(panier) {
        console.log('Sauvegarde du panier:', panier);
        localStorage.setItem('panier', JSON.stringify(panier));
        mettreAJourCompteurPanier();
        afficherPanier();
    }

    // Fonction pour mettre à jour le compteur du panier
    function mettreAJourCompteurPanier() {
        const panier = getPanier();
        const totalArticles = panier.reduce((total, item) => total + item.quantite, 0);
        document.getElementById('count-panier').textContent = totalArticles;
    }

    // Fonction pour afficher le contenu du panier
    async function afficherPanier() {
        const panier = getPanier();
        const contenuPanier = document.getElementById('contenu-panier');
        const panierVide = document.getElementById('panier-vide');
        const btnCommander = document.getElementById('btn-commander');
        const btnVider = document.getElementById('btn-vider');

        console.log('Affichage du panier, nombre d\'articles:', panier.length);

        if (panier.length === 0) {
            contenuPanier.innerHTML = '';
            panierVide.style.display = 'block';
            btnCommander.disabled = true;
            btnVider.style.display = 'none';
            updateRecap(0, 0);
            return;
        }

        panierVide.style.display = 'none';
        btnCommander.disabled = false;
        btnVider.style.display = 'block';

        // Récupérer les détails des produits depuis la base de données
        try {
            console.log('Tentative de récupération des détails produits...');
            const response = await fetch('api_get_produits_panier.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: panier.map(item => item.id) })
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const produits = await response.json();
            console.log('Produits récupérés:', produits);

            let html = '';
            let sousTotal = 0;
            let nombreArticles = 0;

            panier.forEach(itemPanier => {
                const produit = produits.find(p => p.id == itemPanier.id);
                if (produit) {
                    const sousTotalProduit = produit.prix * itemPanier.quantite;
                    sousTotal += sousTotalProduit;
                    nombreArticles += itemPanier.quantite;

                    html += `
                        <div class="article-panier" data-produit-id="${produit.id}">
                            <img src="${produit.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4='}" 
                                 alt="${produit.nom}" class="article-image">
                            <div class="article-details">
                                <div class="article-nom">${produit.nom}</div>
                                <div class="article-prix">${produit.prix.toFixed(2)} FCFA</div>
                                ${itemPanier.quantite > produit.stock ? 
                                    `<div class="alerte-stock">
                                        ❌ Stock insuffisant (${produit.stock} disponible${produit.stock > 1 ? 's' : ''})
                                    </div>` : ''
                                }
                                <div class="article-actions">
                                    <div class="quantite-container">
                                        <button class="btn-quantite" onclick="modifierQuantite(${produit.id}, -1)">-</button>
                                        <span class="quantite">${itemPanier.quantite}</span>
                                        <button class="btn-quantite" onclick="modifierQuantite(${produit.id}, 1)" 
                                                ${itemPanier.quantite >= produit.stock ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''}>+</button>
                                    </div>
                                    <button class="btn-supprimer" onclick="supprimerProduit(${produit.id})">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                            <div class="sous-total">${sousTotalProduit.toFixed(2)} FCFA</div>
                        </div>
                    `;
                } else {
                    console.warn('Produit non trouvé dans la base de données:', itemPanier.id);
                }
            });

            contenuPanier.innerHTML = html;
            updateRecap(sousTotal, nombreArticles);

        } catch (error) {
            console.error('Erreur lors du chargement du panier:', error);
            
            // Fallback : afficher les produits avec les données du localStorage uniquement
            let html = '';
            let sousTotal = 0;
            let nombreArticles = 0;

            panier.forEach(itemPanier => {
                const sousTotalProduit = itemPanier.prix * itemPanier.quantite;
                sousTotal += sousTotalProduit;
                nombreArticles += itemPanier.quantite;

                html += `
                    <div class="article-panier" data-produit-id="${itemPanier.id}">
                        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkF1Y3VuZSBpbWFnZTwvdGV4dD48L3N2Zz4=" 
                             alt="${itemPanier.nom}" class="article-image">
                        <div class="article-details">
                            <div class="article-nom">${itemPanier.nom}</div>
                            <div class="article-prix">${itemPanier.prix.toFixed(2)} FCFA</div>
                            <div class="article-actions">
                                <div class="quantite-container">
                                    <button class="btn-quantite" onclick="modifierQuantite(${itemPanier.id}, -1)">-</button>
                                    <span class="quantite">${itemPanier.quantite}</span>
                                    <button class="btn-quantite" onclick="modifierQuantite(${itemPanier.id}, 1)">+</button>
                                </div>
                                <button class="btn-supprimer" onclick="supprimerProduit(${itemPanier.id})">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                        <div class="sous-total">${sousTotalProduit.toFixed(2)} FCFA</div>
                    </div>
                `;
            });

            contenuPanier.innerHTML = html;
            updateRecap(sousTotal, nombreArticles);
            
            showMessage('Les données de stock ne sont pas disponibles', 'warning');
        }
    }

    // Fonction pour mettre à jour le récapitulatif
    function updateRecap(sousTotal, nombreArticles) {
        const livraison = sousTotal > 50 ? 0 : 4.90;
        const total = sousTotal + livraison;

        document.getElementById('sous-total').textContent = sousTotal.toFixed(2) + ' FCFA';
        document.getElementById('livraison').textContent = livraison.toFixed(2) + ' FCFA';
        document.getElementById('total').textContent = total.toFixed(2) + ' FCFA';

        // Mettre à jour le texte du bouton commander
        const btnCommander = document.getElementById('btn-commander');
        btnCommander.textContent = `Commander (${nombreArticles} article${nombreArticles > 1 ? 's' : ''})`;
    }

    // Fonction pour modifier la quantité d'un produit
    function modifierQuantite(produitId, changement) {
        const panier = getPanier();
        const index = panier.findIndex(item => item.id === produitId);

        if (index !== -1) {
            const nouvelleQuantite = panier[index].quantite + changement;

            if (nouvelleQuantite <= 0) {
                supprimerProduit(produitId);
            } else {
                panier[index].quantite = nouvelleQuantite;
                savePanier(panier);
                showMessage('Quantité mise à jour');
            }
        }
    }

    // Fonction pour supprimer un produit du panier
    function supprimerProduit(produitId) {
        const panier = getPanier();
        const nouveauPanier = panier.filter(item => item.id !== produitId);
        savePanier(nouveauPanier);
        showMessage('Produit retiré du panier');
    }

    // Fonction pour vider le panier
    function viderPanier() {
        if (confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
            localStorage.removeItem('panier');
            mettreAJourCompteurPanier();
            afficherPanier();
            showMessage('Panier vidé');
        }
    }

    // Fonction pour passer commande
    document.getElementById('btn-commander').addEventListener('click', function() {
        const panier = getPanier();
        
        if (panier.length === 0) {
            showMessage('Votre panier est vide', 'error');
            return;
        }

        // Vérifier le stock avant de commander
        verifierStockEtCommander();
    });

    // Fonction pour vérifier le stock et procéder à la commande
    async function verifierStockEtCommander() {
        const panier = getPanier();
        
        try {
            const response = await fetch('api_verifier_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ panier: panier })
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                // Rediriger vers la page de commande
                window.location.href = 'commande.php';
            } else {
                showMessage(result.message, 'error');
                // Recharger le panier pour afficher les stocks actuels
                afficherPanier();
            }
        } catch (error) {
            console.error('Erreur lors de la vérification du stock:', error);
            // Continuer malgré l'erreur
            window.location.href = 'commande.php';
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
            background: ${type === 'success' ? '#66eaa4' : type === 'warning' ? '#ffa726' : '#e74c3c'};
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

    // Gestion du menu responsive
    document.addEventListener('DOMContentLoaded', function() {
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

        // Charger le panier au démarrage
        mettreAJourCompteurPanier();
        afficherPanier();
    });
    </script>

    <!-- Création des fichiers API s'ils n'existent pas -->
    <?php
    // Créer le fichier api_get_produits_panier.php s'il n'existe pas
    $api_file1 = 'api_get_produits_panier.php';
    if (!file_exists($api_file1)) {
        $api_content1 = '<?php
require_once "config.php";
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        $ids = $input["ids"] ?? [];
        
        if (!empty($ids)) {
            $placeholders = str_repeat("?,", count($ids) - 1) . "?";
            $stmt = $pdo->prepare("SELECT id, nom, prix, image, stock FROM produits WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($produits);
        } else {
            echo json_encode([]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
}
?>';
        file_put_contents($api_file1, $api_content1);
        chmod($api_file1, 0644);
    }

    // Créer le fichier api_verifier_stock.php s'il n'existe pas
    $api_file2 = 'api_verifier_stock.php';
    if (!file_exists($api_file2)) {
        $api_content2 = '<?php
require_once "config.php";
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        $panier = $input["panier"] ?? [];
        
        $errors = [];
        
        foreach ($panier as $item) {
            $stmt = $pdo->prepare("SELECT nom, stock FROM produits WHERE id = ?");
            $stmt->execute([$item["id"]]);
            $produit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($produit && $item["quantite"] > $produit["stock"]) {
                $errors[] = "Stock insuffisant pour " . $produit["nom"] . " (" . $item["quantite"] . " demandés, " . $produit["stock"] . " disponibles)";
            }
        }
        
        if (empty($errors)) {
            echo json_encode(["success" => true, "message" => "Stock disponible"]);
        } else {
            echo json_encode(["success" => false, "message" => implode(". ", $errors)]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
}
?>';
        file_put_contents($api_file2, $api_content2);
        chmod($api_file2, 0644);
    }
    ?>
</body>
</html>