<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services | BoutiquePro</title>
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
        background: linear-gradient(135deg, #764ba2 100%, #764ba2 100%);
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

    /* Section hero */
    .hero {
        text-align: center;
        padding: 60px 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        margin-bottom: 50px;
    }

    .hero h1 {
        font-size: 3rem;
        margin-bottom: 20px;
    }

    .hero p {
        font-size: 1.2rem;
        max-width: 800px;
        margin: 0 auto;
    }

    /* Section services */
    .services-section {
        margin-bottom: 50px;
    }

    .services-section h2 {
        text-align: center;
        color: #764ba2;
        margin-bottom: 40px;
        font-size: 2.5rem;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }

    .service-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-align: center;
    }

    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    .service-icon {
        font-size: 3rem;
        color: #764ba2;
        margin-bottom: 20px;
    }

    .service-card h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .service-card p {
        color: #666;
        margin-bottom: 20px;
    }

    .service-features {
        list-style: none;
        text-align: left;
        margin-top: 20px;
    }

    .service-features li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .service-features li:before {
        content: "✓";
        color: #66eaa4;
        font-weight: bold;
        margin-right: 10px;
    }

    /* Section avantages */
    .avantages-section {
        background: white;
        border-radius: 15px;
        padding: 50px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 50px;
    }

    .avantages-section h2 {
        text-align: center;
        color: #764ba2;
        margin-bottom: 40px;
        font-size: 2.5rem;
    }

    .avantages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
    }

    .avantage-item {
        text-align: center;
        padding: 20px;
    }

    .avantage-icon {
        font-size: 2.5rem;
        color: #66eaa4;
        margin-bottom: 15px;
    }

    .avantage-item h3 {
        color: #333;
        margin-bottom: 10px;
    }

    /* Section processus */
    .processus-section {
        margin-bottom: 50px;
    }

    .processus-section h2 {
        text-align: center;
        color: #764ba2;
        margin-bottom: 40px;
        font-size: 2.5rem;
    }

    .processus-steps {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        position: relative;
    }

    .processus-steps:before {
        content: '';
        position: absolute;
        top: 40px;
        left: 10%;
        right: 10%;
        height: 3px;
        background: #764ba2;
        z-index: 1;
    }

    .step {
        flex: 1;
        min-width: 200px;
        text-align: center;
        position: relative;
        z-index: 2;
        padding: 0 15px;
        margin-bottom: 30px;
    }

    .step-number {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0 auto 20px;
    }

    .step h3 {
        color: #333;
        margin-bottom: 10px;
    }

    /* Section FAQ */
    .faq-section {
        background: white;
        border-radius: 15px;
        padding: 50px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .faq-section h2 {
        text-align: center;
        color: #764ba2;
        margin-bottom: 40px;
        font-size: 2.5rem;
    }

    .faq-item {
        margin-bottom: 20px;
        border: 1px solid #eee;
        border-radius: 10px;
        overflow: hidden;
    }

    .faq-question {
        padding: 20px;
        background: #f8f9fa;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        color: #333;
    }

    .faq-answer {
        padding: 0 20px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }

    .faq-item.active .faq-answer {
        padding: 20px;
        max-height: 500px;
    }

    /* Footer */
    footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 30px 0;
        margin-top: 50px;
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

        .hero h1 {
            font-size: 2rem;
        }

        .hero p {
            font-size: 1rem;
        }

        .processus-steps:before {
            display: none;
        }

        .processus-steps {
            flex-direction: column;
        }

        .step {
            margin-bottom: 30px;
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

    .service-card, .avantage-item, .step {
        animation: fadeInUp 0.6s ease-out;
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
        <li><a href="produits.php">Produits</a></li>
        
        <li><a href="connexion.php">Connexion</a></li>
        <li><a href="contact.php">Contact</a></li>
		
      </ul>
    </nav>
    </header>

    <div class="container">
        <!-- Section Hero -->
        <section class="hero">
            <h1>Nos Services</h1>
            <p>Découvrez l'ensemble des services que nous mettons à votre disposition pour une expérience d'achat exceptionnelle</p>
        </section>

        <!-- Section Services Principaux -->
        <section class="services-section">
            <h2>Nos Services Premium</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">🚚</div>
                    <h3>Livraison Express</h3>
                    <p>Recevez vos produits en 24h chrono partout en France métropolitaine</p>
                    <ul class="service-features">
                        <li>Livraison gratuite dès 50€ d'achat</li>
                        <li>Suivi en temps réel</li>
                        <li>Livraison le weekend</li>
                        <li>Points relais disponibles</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">↩️</div>
                    <h3>Retours Faciles</h3>
                    <p>30 jours pour changer d'avis, retours et remboursements simplifiés</p>
                    <ul class="service-features">
                        <li>Retours gratuits sous 30 jours</li>
                        <li>Remboursement express</li>
                        <li>Échange immédiat possible</li>
                        <li>Procédure en ligne simple</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">💎</div>
                    <h3>Conseil Personnalisé</h3>
                    <p>Nos experts vous accompagnent dans vos choix pour trouver le produit parfait</p>
                    <ul class="service-features">
                        <li>Conseils par téléphone et chat</li>
                        <li>Guide d'achat personnalisé</li>
                        <li>Comparaison de produits</li>
                        <li>Service disponible 7j/7</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🛡️</div>
                    <h3>Garantie Étendue</h3>
                    <p>Profitez d'une garantie prolongée sur tous nos produits avec assistance premium</p>
                    <ul class="service-features">
                        <li>Garantie jusqu'à 3 ans</li>
                        <li>Assistance technique incluse</li>
                        <li>Réparation express</li>
                        <li>Prêt de produit si nécessaire</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🎁</div>
                    <h3>Emballage Cadeau</h3>
                    <p>Offrez vos produits dans un emballage cadeau élégant avec message personnalisé</p>
                    <ul class="service-features">
                        <li>Emballage premium gratuit</li>
                        <li>Carte personnalisée</li>
                        <li>Service surprise</li>
                        <li>Plusieurs styles disponibles</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🔒</div>
                    <h3>Paiement Sécurisé</h3>
                    <p>Transactions 100% sécurisées avec multiples options de paiement</p>
                    <ul class="service-features">
                        <li>Paiement en 3x sans frais</li>
                        <li>Cryptage SSL avancé</li>
                        <li>Multiple moyens de paiement</li>
                        <li>Protection anti-fraude</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Section Avantages -->
        <section class="avantages-section">
            <h2>Pourquoi Nous Choisir ?</h2>
            <div class="avantages-grid">
                <div class="avantage-item">
                    <div class="avantage-icon">⭐</div>
                    <h3>Qualité Premium</h3>
                    <p>Tous nos produits sont rigoureusement sélectionnés pour leur excellence</p>
                </div>
                <div class="avantage-item">
                    <div class="avantage-icon">💰</div>
                    <h3>Prix Compétitifs</h3>
                    <p>Les meilleurs prix garantis avec des offres exclusives régulières</p>
                </div>
                <div class="avantage-item">
                    <div class="avantage-icon">👥</div>
                    <h3>Service Client</h3>
                    <p>Une équipe dédiée à votre écoute 7j/7 pour répondre à vos besoins</p>
                </div>
                <div class="avantage-item">
                    <div class="avantage-icon">🌱</div>
                    <h3>Éco-responsable</h3>
                    <p>Engagés dans une démarche durable et respectueuse de l'environnement</p>
                </div>
            </div>
        </section>

        <!-- Section Processus -->
        <section class="processus-section">
            <h2>Comment ça marche ?</h2>
            <div class="processus-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Choisissez</h3>
                    <p>Parcourez notre catalogue et sélectionnez vos produits préférés</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Commandez</h3>
                    <p>Passez votre commande en quelques clics, c'est simple et rapide</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Recevez</h3>
                    <p>Livraison express à domicile ou en point relais selon votre choix</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Profitez</h3>
                    <p>Découvrez vos produits et bénéficiez de notre accompagnement</p>
                </div>
            </div>
        </section>

        <!-- Section FAQ -->
        <section class="faq-section">
            <h2>Questions Fréquentes</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Quels sont les délais de livraison ?</span>
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Nous proposons la livraison express sous 24h pour la France métropolitaine. Pour les zones rurales, comptez 48h maximum. Les livraisons internationales prennent généralement 3 à 7 jours ouvrés.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Comment fonctionnent les retours ?</span>
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Vous disposez de 30 jours pour retourner un produit non utilisé et dans son emballage d'origine. Les retours sont gratuits et le remboursement est effectué sous 48h après réception du colis.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Proposez-vous l'installation des produits ?</span>
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Oui, nous proposons un service d'installation à domicile pour la plupart de nos produits. Ce service est disponible en option lors de votre commande. Nos techniciens certifiés interviennent dans un délai de 48h à 72h.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Quelles sont vos méthodes de paiement ?</span>
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Nous acceptons les cartes bancaires (Visa, Mastercard, American Express), PayPal, virements bancaires, et le paiement en 3 ou 4 fois sans frais à partir de 100€ d'achat.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Comment contacter le service client ?</span>
                        <span>+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Notre service client est disponible par téléphone au 01 23 45 67 89 du lundi au samedi de 8h à 20h, par chat en direct sur notre site, ou par email à support@boutiquepro.fr. Nous répondons sous 2 heures maximum.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer>
        <p>&copy; 2023 BoutiquePro - Tous droits réservés</p>
        <p>Service Client : 01 23 45 67 89 | Email : contact@boutiquepro.fr</p>
    </footer>

    <script>
    // Script pour la FAQ
    document.addEventListener('DOMContentLoaded', function() {
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            
            question.addEventListener('click', () => {
                // Fermer tous les autres items
                faqItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // Basculer l'item actuel
                item.classList.toggle('active');
            });
        });

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
        document.querySelectorAll('.service-card, .avantage-item, .step').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });
    });
    </script>
</body>
</html>