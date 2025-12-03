<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser la Commande | BoutiquePro</title>
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
        background: linear-gradient(135deg, #66eaa4ff 0%, #764ba2 100%);
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

    /* Sections de commande */
    .commande-section {
        background-color: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .commande-section h1 {
        color: #764ba2;
        margin-bottom: 20px;
        text-align: center;
        font-size: 2rem;
    }

    /* Étapes de commande */
    .etapes-commande {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
        position: relative;
    }

    .etape {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    }

    .cercle-etape {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ddd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 10px;
        transition: all 0.3s;
    }

    .etape.active .cercle-etape {
        background: #764ba2;
    }

    .etape.termine .cercle-etape {
        background: #66eaa4;
    }

    .ligne-etapes {
        position: absolute;
        top: 20px;
        left: 10%;
        right: 10%;
        height: 3px;
        background: #ddd;
        z-index: 1;
    }

    .ligne-progression {
        position: absolute;
        top: 20px;
        left: 10%;
        height: 3px;
        background: #66eaa4;
        z-index: 1;
        transition: width 0.5s;
    }

    /* Formulaires */
    .form-etape {
        display: none;
    }

    .form-etape.active {
        display: block;
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border 0.3s;
    }

    .form-control:focus {
        border-color: #764ba2;
        outline: none;
    }

    /* Méthodes de paiement */
    .methodes-paiement {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .methode-paiement {
        border: 2px solid #ddd;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .methode-paiement:hover {
        border-color: #764ba2;
        transform: translateY(-5px);
    }

    .methode-paiement.selected {
        border-color: #66eaa4;
        background: #f8fff8;
    }

    .methode-paiement img {
        height: 40px;
        margin-bottom: 10px;
    }

    /* Produits du panier */
    .produit-panier {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .produit-panier img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
        margin-right: 15px;
    }

    .produit-info {
        flex-grow: 1;
    }

    .produit-nom {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .produit-prix {
        color: #764ba2;
        font-weight: bold;
    }

    .quantite {
        font-weight: bold;
        color: #666;
    }

    /* Résumé de commande */
    .resume-commande {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
        margin-top: 30px;
        border-left: 4px solid #764ba2;
    }

    .resume-commande h3 {
        color: #764ba2;
        margin-bottom: 15px;
        text-align: center;
    }

    .ligne-resume {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .total {
        font-weight: bold;
        font-size: 1.2rem;
        color: #764ba2;
        margin-top: 10px;
    }

    /* Boutons de navigation */
    .navigation-boutons {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }

    .btn-nav {
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-precedent {
        background: #f8f9fa;
        color: #333;
        border: 1px solid #ddd;
    }

    .btn-precedent:hover {
        background: #e9ecef;
    }

    .btn-suivant {
        background: linear-gradient(135deg, #66eaa4ff 0%, #764ba2 100%);
        color: white;
    }

    .btn-suivant:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .btn-suivant:disabled {
        background: #ddd;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* Message de succès */
    .message-succes {
        text-align: center;
        padding: 40px 20px;
    }

    .message-succes .icon {
        font-size: 4rem;
        color: #66eaa4;
        margin-bottom: 20px;
    }

    /* Footer */
    footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 20px 0;
        margin-top: 40px;
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

        .etapes-commande {
            flex-direction: column;
            align-items: center;
        }

        .etape {
            margin-bottom: 20px;
        }

        .ligne-etapes, .ligne-progression {
            display: none;
        }

        .methodes-paiement {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="assets/logo.png" alt="Logo BoutiquePro">
            <span>BoutiquePro</span>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="commande.php">Commander</a></li>
                
                <li><a href="connexion.php">Connexion</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="panier.php">Panier</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <section class="commande-section">
            <h1>Finaliser votre commande</h1>
            
            <!-- Étapes de la commande -->
            <div class="etapes-commande">
                <div class="ligne-etapes"></div>
                <div class="ligne-progression" id="ligne-progression"></div>
                
                <div class="etape active" id="etape1">
                    <div class="cercle-etape">1</div>
                    <span>Informations</span>
                </div>
                <div class="etape" id="etape2">
                    <div class="cercle-etape">2</div>
                    <span>Paiement</span>
                </div>
                <div class="etape" id="etape3">
                    <div class="cercle-etape">3</div>
                    <span>Validation</span>
                </div>
                <div class="etape" id="etape4">
                    <div class="cercle-etape">4</div>
                    <span>Confirmation</span>
                </div>
            </div>

            <form id="form-commande" action="traitement-commande.php" method="POST">
                <!-- Étape 1: Informations client -->
                <div class="form-etape active" id="form-etape1">
                    <h2>Informations personnelles</h2>
                    
                    <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Numéro de téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse">Adresse de livraison</label>
                        <textarea id="adresse" name="adresse" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="resume-commande">
                        <h3>Récapitulatif de votre commande</h3>
                        <div id="recap-panier">
                            <!-- Le contenu du panier sera affiché ici -->
                        </div>
                        <div class="ligne-resume">
                            <span>Livraison:</span>
                            <span id="frais-livraison">9,99 FCFA</span>
                        </div>
                        <div class="ligne-resume total">
                            <span>Total:</span>
                            <span id="total-commande">0,00 FCFA</span>
                        </div>
                    </div>

                    <div class="navigation-boutons">
                        <div></div> <!-- Espace vide pour aligner à droite -->
                        <button type="button" class="btn-nav btn-suivant" onclick="changerEtape(2)">
                            Continuer vers le paiement
                        </button>
                    </div>
                </div>

                <!-- Étape 2: Méthode de paiement -->
                <div class="form-etape" id="form-etape2">
                    <h2>Choisissez votre mode de paiement</h2>
                    
                    <div class="methodes-paiement">
                        <div class="methode-paiement" onclick="selectionnerPaiement('orange_money')">
                            <img src="assets/orange-money.png" alt="Orange Money">
                            <div>Orange Money</div>
                        </div>
                        <div class="methode-paiement" onclick="selectionnerPaiement('mtn_money')">
                            <img src="assets/mtn-money.png" alt="MTN Mobile Money">
                            <div>MTN Mobile Money</div>
                        </div>
                        <div class="methode-paiement" onclick="selectionnerPaiement('carte')">
                            <img src="assets/carte.png" alt="Carte Bancaire">
                            <div>Carte Bancaire</div>
                        </div>
                    </div>

                    <input type="hidden" id="methode-paiement" name="methode_paiement">

                    <div id="formulaire-paiement" style="display: none;">
                        <div class="form-group">
                            <label for="numero-paiement" id="label-numero">Numéro de téléphone</label>
                            <input type="tel" id="numero-paiement" class="form-control" placeholder="Ex: 0771234567">
                        </div>
                    </div>

                    <div class="navigation-boutons">
                        <button type="button" class="btn-nav btn-precedent" onclick="changerEtape(1)">
                            ← Retour
                        </button>
                        <button type="button" class="btn-nav btn-suivant" id="btn-valider-numero" onclick="validerNumero()" disabled>
                            Valider le numéro
                        </button>
                    </div>
                </div>

                <!-- Étape 3: Code secret -->
                <div class="form-etape" id="form-etape3">
                    <h2>Confirmation de paiement</h2>
                    
                    <div class="form-group">
                        <label for="code-secret">Entrez votre code secret</label>
                        <input type="password" id="code-secret" class="form-control" maxlength="4" placeholder="****">
                        <small style="color: #666; margin-top: 5px; display: block;">
                            Code secret à 4 chiffres
                        </small>
                    </div>

                    <div class="navigation-boutons">
                        <button type="button" class="btn-nav btn-precedent" onclick="changerEtape(2)">
                            ← Retour
                        </button>
                        <button type="button" class="btn-nav btn-suivant" id="btn-valider-code" onclick="validerCodeSecret()" disabled>
                            Valider le paiement
                        </button>
                    </div>
                </div>

                <!-- Étape 4: Confirmation -->
                <div class="form-etape" id="form-etape4">
                    <div class="message-succes">
                        <div class="icon">✓</div>
                        <h2>Paiement réussi !</h2>
                        <p>Votre commande a été validée et sera livrée dans les plus brefs délais.</p>
                        <p><strong>Numéro de commande: </strong><span id="numero-commande">CMD-<?php echo time(); ?></span></p>
                        
                        <div class="navigation-boutons" style="justify-content: center; margin-top: 30px;">
                            <button type="submit" class="btn-nav btn-suivant">
                                Finaliser la commande
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <footer>
        <p>&copy; 2023 BoutiquePro - Tous droits réservés</p>
    </footer>

    <script>
    // Variables globales
    let etapeActuelle = 1;
    let methodePaiement = '';
    let panier = [];
    let totalCommande = 0;

    // Chargement initial
    document.addEventListener('DOMContentLoaded', function() {
        chargerPanier();
        mettreAJourEtapes();
        
        // Écouteurs d'événements
        document.getElementById('numero-paiement').addEventListener('input', function() {
            document.getElementById('btn-valider-numero').disabled = !this.value.trim();
        });

        document.getElementById('code-secret').addEventListener('input', function() {
            document.getElementById('btn-valider-code').disabled = this.value.length !== 4;
        });
    });

    // Charger le panier depuis le localStorage
    function chargerPanier() {
        panier = JSON.parse(localStorage.getItem('panier')) || [];
        afficherRecapPanier();
        calculerTotal();
    }

    // Afficher le récapitulatif du panier
    function afficherRecapPanier() {
        const recapPanier = document.getElementById('recap-panier');
        
        if (panier.length === 0) {
            recapPanier.innerHTML = '<p>Votre panier est vide</p>';
            return;
        }

        let html = '';
        panier.forEach(item => {
            html += `
                <div class="produit-panier">
                    <img src="${item.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTAiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZTwvdGV4dD48L3N2Zz4='}" 
                         alt="${item.nom}">
                    <div class="produit-info">
                        <div class="produit-nom">${item.nom}</div>
                        <div class="produit-prix">${item.prix.toFixed(2)} FCFA</div>
                    </div>
                    <div class="quantite">x${item.quantite}</div>
                </div>
            `;
        });

        recapPanier.innerHTML = html;
    }

    // Calculer le total de la commande
    function calculerTotal() {
        const fraisLivraison = 9.99;
        totalCommande = panier.reduce((total, item) => total + (item.prix * item.quantite), 0) + fraisLivraison;
        document.getElementById('total-commande').textContent = totalCommande.toFixed(2) + ' FCFA';
    }

    // Changer d'étape
    function changerEtape(nouvelleEtape) {
        // Validation de l'étape actuelle
        if (nouvelleEtape > etapeActuelle) {
            if (!validerEtape(etapeActuelle)) {
                return;
            }
        }

        // Changer d'étape
        document.getElementById('form-etape' + etapeActuelle).classList.remove('active');
        document.getElementById('etape' + etapeActuelle).classList.remove('active');
        
        etapeActuelle = nouvelleEtape;
        
        document.getElementById('form-etape' + etapeActuelle).classList.add('active');
        document.getElementById('etape' + etapeActuelle).classList.add('active');
        
        mettreAJourEtapes();
    }

    // Valider l'étape actuelle
    function validerEtape(etape) {
        switch(etape) {
            case 1:
                const nom = document.getElementById('nom').value;
                const email = document.getElementById('email').value;
                const telephone = document.getElementById('telephone').value;
                const adresse = document.getElementById('adresse').value;
                
                if (!nom || !email || !telephone || !adresse) {
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return false;
                }
                
                if (panier.length === 0) {
                    alert('Votre panier est vide. Veuillez ajouter des produits avant de commander.');
                    return false;
                }
                return true;
                
            case 2:
                if (!methodePaiement) {
                    alert('Veuillez sélectionner un mode de paiement.');
                    return false;
                }
                return true;
                
            default:
                return true;
        }
    }

    // Mettre à jour l'affichage des étapes
    function mettreAJourEtapes() {
        // Mettre à jour les cercles des étapes
        for (let i = 1; i <= 4; i++) {
            const etape = document.getElementById('etape' + i);
            if (i < etapeActuelle) {
                etape.classList.add('termine');
                etape.classList.remove('active');
            } else if (i === etapeActuelle) {
                etape.classList.add('active');
                etape.classList.remove('termine');
            } else {
                etape.classList.remove('active', 'termine');
            }
        }
        
        // Mettre à jour la ligne de progression
        const progression = ((etapeActuelle - 1) / 3) * 80 + 10;
        document.getElementById('ligne-progression').style.width = progression + '%';
    }

    // Sélectionner une méthode de paiement
    function selectionnerPaiement(methode) {
        methodePaiement = methode;
        document.getElementById('methode-paiement').value = methode;
        
        // Retirer la sélection précédente
        document.querySelectorAll('.methode-paiement').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Ajouter la sélection actuelle
        event.currentTarget.classList.add('selected');
        
        // Afficher le formulaire approprié
        const formulairePaiement = document.getElementById('formulaire-paiement');
        const labelNumero = document.getElementById('label-numero');
        
        formulairePaiement.style.display = 'block';
        
        if (methode === 'orange_money') {
            labelNumero.textContent = 'Numéro Orange Money';
        } else if (methode === 'mtn_money') {
            labelNumero.textContent = 'Numéro MTN Mobile Money';
        } else {
            labelNumero.textContent = 'Numéro de carte';
        }
        
        document.getElementById('btn-valider-numero').disabled = true;
    }

    // Valider le numéro de téléphone
    function validerNumero() {
        const numero = document.getElementById('numero-paiement').value.trim();
        
        if (!numero) {
            alert('Veuillez entrer votre numéro.');
            return;
        }
        
        // Validation basique du numéro
        const regex = /^[0-9]{9,10}$/;
        if (!regex.test(numero)) {
            alert('Veuillez entrer un numéro valide (9-10 chiffres).');
            return;
        }
        
        // Simuler l'envoi du code de confirmation
        setTimeout(() => {
            changerEtape(3);
        }, 1000);
    }

    // Valider le code secret
    function validerCodeSecret() {
        const codeSecret = document.getElementById('code-secret').value;
        
        if (codeSecret.length !== 4) {
            alert('Le code secret doit contenir 4 chiffres.');
            return;
        }
        
        // Simuler la validation du paiement
        document.getElementById('btn-valider-code').disabled = true;
        document.getElementById('btn-valider-code').textContent = 'Validation en cours...';
        
        setTimeout(() => {
            // Simuler un paiement réussi
            changerEtape(4);
            
            // Préparer les données pour l'envoi
            preparerDonneesCommande();
            
        }, 2000);
    }

    // Préparer les données de la commande pour l'envoi
    function preparerDonneesCommande() {
        // Ajouter les données du panier au formulaire
        panier.forEach((item, index) => {
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = `produits[${index}][id]`;
            inputId.value = item.id;
            document.getElementById('form-commande').appendChild(inputId);
            
            const inputQuantite = document.createElement('input');
            inputQuantite.type = 'hidden';
            inputQuantite.name = `produits[${index}][quantite]`;
            inputQuantite.value = item.quantite;
            document.getElementById('form-commande').appendChild(inputQuantite);
            
            const inputPrix = document.createElement('input');
            inputPrix.type = 'hidden';
            inputPrix.name = `produits[${index}][prix]`;
            inputPrix.value = item.prix;
            document.getElementById('form-commande').appendChild(inputPrix);
        });
        
        // Ajouter le total
        const inputTotal = document.createElement('input');
        inputTotal.type = 'hidden';
        inputTotal.name = 'total';
        inputTotal.value = totalCommande;
        document.getElementById('form-commande').appendChild(inputTotal);
    }

    // Gestion de la soumission du formulaire
    document.getElementById('form-commande').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Vider le panier après commande réussie
        localStorage.removeItem('panier');
        
        // Rediriger vers la page d'accueil ou de confirmation
        alert('Commande finalisée avec succès ! Merci pour votre achat.');
        window.location.href = 'index.php';
    });
    </script>
</body>
</html>