<?php
session_start();

// Connexion à la base de données
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

// Variables
$couleur_principale = $parametres['couleur_principale'];
$couleur_secondaire = $parametres['couleur_secondaire'] ?? '#667eea';
$nom_site = $parametres['nom_site'];
$logo = $parametres['logo'];
$image_fond = $parametres['image_fond'];

// Variables pour le formulaire
$nom = $email = $sujet = $message = $type = '';
$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'question';
    $client_id = $_SESSION['user_id'] ?? null;

    // Validation
    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    } elseif (strlen($nom) < 2) {
        $errors[] = "Le nom doit contenir au moins 2 caractères";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }

    if (empty($sujet)) {
        $errors[] = "Le sujet est requis";
    } elseif (strlen($sujet) < 5) {
        $errors[] = "Le sujet doit contenir au moins 5 caractères";
    }

    if (empty($message)) {
        $errors[] = "Le message est requis";
    } elseif (strlen($message) < 10) {
        $errors[] = "Le message doit contenir au moins 10 caractères";
    }

    // Si pas d'erreurs, sauvegarde en base de données
    if (empty($errors)) {
        try {
            // Récupérer l'ID d'un administrateur actif (par exemple le premier admin trouvé)
            $stmt_admin = $pdo->query("SELECT id FROM administrateurs WHERE statut = 'actif' AND niveau_acces IN ('superadmin', 'admin') LIMIT 1");
            $admin = $stmt_admin->fetch();
            
            if ($admin) {
                // Si l'utilisateur est connecté, utiliser son ID, sinon mettre NULL
                $expediteur_id = $client_id ?? 0;
                $expediteur_type = $client_id ? 'client' : 'visiteur';
                
                // Pour un visiteur non connecté, on va d'abord créer/mettre à jour un client
                if (!$client_id) {
                    // Vérifier si un client existe déjà avec cet email
                    $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
                    $stmt_client->execute([$email]);
                    $existing_client = $stmt_client->fetch();
                    
                    if ($existing_client) {
                        $expediteur_id = $existing_client['id'];
                        $expediteur_type = 'client';
                        
                        // Mettre à jour le nom du client si nécessaire
                        $stmt_update = $pdo->prepare("UPDATE clients SET nom = ?, prenom = ? WHERE id = ?");
                        $noms = explode(' ', $nom, 2);
                        $prenom_client = isset($noms[0]) ? $noms[0] : $nom;
                        $nom_client = isset($noms[1]) ? $noms[1] : $nom;
                        $stmt_update->execute([$nom_client, $prenom_client, $expediteur_id]);
                    } else {
                        // Créer un client temporaire pour le visiteur
                        $stmt_new_client = $pdo->prepare("
                            INSERT INTO clients (nom, prenom, email, telephone, adresse, mot_de_passe, statut) 
                            VALUES (:nom, :prenom, :email, :telephone, :adresse, :mot_de_passe, 'inactif')
                        ");
                        
                        // Diviser le nom complet en nom et prénom
                        $noms = explode(' ', $nom, 2);
                        $prenom_client = isset($noms[0]) ? $noms[0] : $nom;
                        $nom_client = isset($noms[1]) ? $noms[1] : $nom;
                        
                        $stmt_new_client->execute([
                            ':nom' => $nom_client,
                            ':prenom' => $prenom_client,
                            ':email' => $email,
                            ':telephone' => 'Non fourni',
                            ':adresse' => 'Adresse non fournie',
                            ':mot_de_passe' => password_hash(uniqid(), PASSWORD_DEFAULT)
                        ]);
                        
                        $expediteur_id = $pdo->lastInsertId();
                        $expediteur_type = 'client';
                    }
                }
                
                // Insérer le message dans messages_prives
                $stmt = $pdo->prepare("
                    INSERT INTO messages_prives 
                    (expediteur_id, expediteur_type, destinataire_id, destinataire_type, sujet, message, date_envoi, statut) 
                    VALUES (:expediteur_id, 'client', :destinataire_id, 'admin', :sujet, :message, NOW(), 'non_lu')
                ");
                
                $stmt->execute([
                    ':expediteur_id' => $expediteur_id,
                    ':destinataire_id' => $admin['id'],
                    ':sujet' => $type . ' - ' . $sujet,
                    ':message' => "Email: $email\n\nMessage:\n$message"
                ]);
                
                $success = true;
                
                // Réinitialiser les champs
                $nom = $email = $sujet = $message = '';
                $type = 'question';
                
            } else {
                $errors[] = "Aucun administrateur disponible pour recevoir votre message.";
            }
            
        } catch(PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | <?php echo htmlspecialchars($nom_site); ?></title>
    <style>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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

        /* Main Content */
        main {
            margin-top: 140px;
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .contact-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--couleur-principale);
            font-size: 2.5rem;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Contact Info */
        .contact-info {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-info h2 {
            color: var(--couleur-principale);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .contact-info p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .info-item:hover {
            transform: translateX(5px);
            background: #e9ecef;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: var(--couleur-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.2rem;
        }

        .info-content h3 {
            color: var(--couleur-principale);
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
        }

        .info-content p {
            margin: 0;
            color: #666;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-form h2 {
            color: var(--couleur-principale);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--couleur-principale);
            background: white;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .radio-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-label input {
            margin-right: 0.5rem;
        }

        .radio-label span {
            padding: 5px 15px;
            background: #f8f9fa;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .radio-label input:checked + span {
            background: var(--couleur-gradient);
            color: white;
        }

        .btn {
            display: inline-block;
            background: var(--couleur-gradient);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border: none;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .btn-full {
            width: 100%;
        }

        /* Messages d'erreur/succès */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            animation: slideIn 0.3s ease;
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

        .alert ul {
            margin: 0.5rem 0 0 1.5rem;
        }

        .alert li {
            margin-bottom: 0.3rem;
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 4rem;
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .faq-section h2 {
            text-align: center;
            color: var(--couleur-principale);
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .faq-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--couleur-principale);
        }

        .faq-item h3 {
            color: var(--couleur-principale);
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
        }

        .faq-item p {
            color: #666;
            line-height: 1.6;
        }

        /* Map Section */
        .map-section {
            margin-top: 4rem;
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .map-section h2 {
            text-align: center;
            color: var(--couleur-principale);
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            height: 400px;
            border: 2px solid #e0e0e0;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        .contact-info, .contact-form, .faq-section, .map-section {
            animation: fadeIn 0.6s ease-out;
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
            
            .page-title {
                font-size: 2rem;
            }
            
            main {
                margin-top: 180px;
                padding: 1rem;
            }
            
            .contact-info, .contact-form, .faq-section, .map-section {
                padding: 1.5rem;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Menu Toggle Styles */
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
        
        /* Ajout pour le succès du message */
        .info-note {
            background: #e7f3ff;
            border-left: 4px solid #1890ff;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .info-note strong {
            color: #1890ff;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="<?php echo $logo; ?>" alt="Logo">
            <span><?php echo htmlspecialchars($nom_site); ?></span>
        </div>
        <nav>
            <button class="menu-toggle">☰</button>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="produits.php">Produits</a></li>
                <li><a href="commande.php">Commander</a></li>
                <li><a href="panier.php">Panier</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
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
        <div class="contact-container">
            <h1 class="page-title">Contactez-nous</h1>
            
            <!-- Messages d'erreur/succès -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ Votre message a été envoyé avec succès !<br>
                    <small>Le message a été transmis à notre équipe d'administration et apparaîtra dans leur messagerie.</small>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Veuillez corriger les erreurs suivantes :</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="contact-grid">
                <!-- Informations de contact -->
                <div class="contact-info">
                    <h2>Informations de contact</h2>
                    <p>Nous sommes à votre disposition pour répondre à toutes vos questions. N'hésitez pas à nous contacter par téléphone, email ou via le formulaire.</p>
                    
                    <div class="info-item">
                        <div class="info-icon">📍</div>
                        <div class="info-content">
                            <h3>Adresse</h3>
                            <p>123 Avenue du Commerce<br>75001 Paris, France</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div class="info-content">
                            <h3>Téléphone</h3>
                            <p>+33 1 23 45 67 89<br>Lundi - Vendredi : 9h-18h</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">✉️</div>
                        <div class="info-content">
                            <h3>Email</h3>
                            <p>contact@<?php echo strtolower(str_replace(' ', '', $nom_site)); ?>.com<br>support@<?php echo strtolower(str_replace(' ', '', $nom_site)); ?>.com</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">⏰</div>
                        <div class="info-content">
                            <h3>Horaires d'ouverture</h3>
                            <p>Lundi - Vendredi : 9h-18h<br>Samedi : 10h-17h<br>Dimanche : Fermé</p>
                        </div>
                    </div>
                    
                    <div class="info-note">
                        <strong>💡 Note importante :</strong><br>
                        Les messages envoyés via ce formulaire sont directement transmis à notre équipe d'administration. 
                        Si vous avez un compte, vous pourrez consulter la réponse dans votre messagerie personnelle.
                    </div>
                </div>
                
                <!-- Formulaire de contact -->
                <div class="contact-form">
                    <h2>Envoyez-nous un message</h2>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom complet *</label>
                                <input type="text" id="nom" name="nom" class="form-control" 
                                       value="<?php echo htmlspecialchars($nom); ?>" 
                                       required minlength="2">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Adresse email *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($email); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sujet">Sujet *</label>
                            <input type="text" id="sujet" name="sujet" class="form-control" 
                                   value="<?php echo htmlspecialchars($sujet); ?>" 
                                   required minlength="5" placeholder="Objet de votre message">
                        </div>
                        
                        <div class="form-group">
                            <label>Type de message *</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="type" value="question" 
                                           <?php echo ($type === 'question' || $type === '') ? 'checked' : ''; ?>>
                                    <span>Question</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="type" value="probleme" 
                                           <?php echo $type === 'probleme' ? 'checked' : ''; ?>>
                                    <span>Problème</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="type" value="satisfaction" 
                                           <?php echo $type === 'satisfaction' ? 'checked' : ''; ?>>
                                    <span>Satisfaction</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="type" value="reclamation" 
                                           <?php echo $type === 'reclamation' ? 'checked' : ''; ?>>
                                    <span>Réclamation</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" class="form-control" 
                                      required minlength="10" 
                                      placeholder="Votre message..."><?php echo htmlspecialchars($message); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-full">Envoyer le message</button>
                    </form>
                </div>
            </div>
            
            <!-- FAQ Section -->
            <div class="faq-section">
                <h2>Questions fréquentes</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>Quels sont les délais de livraison ?</h3>
                        <p>Les délais de livraison varient entre 2 et 5 jours ouvrés selon votre localisation. Vous recevrez un email de confirmation avec un numéro de suivi.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Puis-je retourner un produit ?</h3>
                        <p>Oui, vous disposez de 30 jours pour retourner un produit non utilisé, dans son emballage d'origine. Les frais de retour sont à votre charge.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Quels modes de paiement acceptez-vous ?</h3>
                        <p>Nous acceptons les cartes bancaires (Visa, Mastercard), PayPal, et les virements bancaires. Tous les paiements sont sécurisés.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Comment suivre ma commande ?</h3>
                        <p>Une fois votre commande expédiée, vous recevrez un email avec un numéro de suivi. Vous pouvez également suivre votre commande depuis votre compte.</p>
                    </div>
                </div>
            </div>
            
            <!-- Map Section -->
            <div class="map-section">
                <h2>Notre emplacement</h2>
                <div class="map-container">
                    <!-- Google Maps Embed -->
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15923.230116698936!2d11.51129659622905!3d3.8514503379568836!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x108bcf8a3283cf39%3A0x37e2a13024988b75!2sMvog%20Mbi%2C%20Yaound%C3%A9!5e0!3m2!1sfr!2scm!4v1764677168021!5m2!1sfr!2scm" 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($nom_site); ?> - Tous droits réservés.</p>
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">
            Adresse : 123 Avenue du Commerce, 75001 Paris | 
            Tél : +33 1 23 45 67 89 | 
            Email : contact@<?php echo strtolower(str_replace(' ', '', $nom_site)); ?>.com
        </p>
    </footer>

    <script>
        // Gestion du menu responsive
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navUl = document.querySelector('nav ul');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navUl.classList.toggle('show');
                });
            }
            
            // Fermer le menu quand on clique sur un lien
            document.querySelectorAll('nav ul li a').forEach(link => {
                link.addEventListener('click', function() {
                    navUl.classList.remove('show');
                });
            });
            
            // Validation du formulaire en temps réel
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    let valid = true;
                    
                    // Validation du nom
                    const nom = document.getElementById('nom');
                    if (nom.value.length < 2) {
                        showError(nom, 'Le nom doit contenir au moins 2 caractères');
                        valid = false;
                    } else {
                        clearError(nom);
                    }
                    
                    // Validation de l'email
                    const email = document.getElementById('email');
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email.value)) {
                        showError(email, 'Veuillez entrer une adresse email valide');
                        valid = false;
                    } else {
                        clearError(email);
                    }
                    
                    // Validation du sujet
                    const sujet = document.getElementById('sujet');
                    if (sujet.value.length < 5) {
                        showError(sujet, 'Le sujet doit contenir au moins 5 caractères');
                        valid = false;
                    } else {
                        clearError(sujet);
                    }
                    
                    // Validation du message
                    const message = document.getElementById('message');
                    if (message.value.length < 10) {
                        showError(message, 'Le message doit contenir au moins 10 caractères');
                        valid = false;
                    } else {
                        clearError(message);
                    }
                    
                    if (!valid) {
                        event.preventDefault();
                        showMessage('Veuillez corriger les erreurs dans le formulaire', 'error');
                    }
                });
                
                // Validation en temps réel
                const inputs = form.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    
                    input.addEventListener('input', function() {
                        clearError(this);
                    });
                });
                
                function validateField(field) {
                    const value = field.value.trim();
                    
                    if (field.type === 'text' || field.name === 'nom' || field.name === 'sujet') {
                        if (value.length < (field.name === 'nom' ? 2 : 5)) {
                            showError(field, `${field.name === 'nom' ? 'Le nom' : 'Le sujet'} doit contenir au moins ${field.name === 'nom' ? 2 : 5} caractères`);
                            return false;
                        }
                    }
                    
                    if (field.type === 'email') {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailPattern.test(value)) {
                            showError(field, 'Veuillez entrer une adresse email valide');
                            return false;
                        }
                    }
                    
                    if (field.name === 'message') {
                        if (value.length < 10) {
                            showError(field, 'Le message doit contenir au moins 10 caractères');
                            return false;
                        }
                    }
                    
                    clearError(field);
                    return true;
                }
                
                function showError(field, message) {
                    clearError(field);
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'field-error';
                    errorDiv.textContent = message;
                    errorDiv.style.cssText = `
                        color: #dc3545;
                        font-size: 0.85rem;
                        margin-top: 0.3rem;
                    `;
                    
                    field.parentNode.appendChild(errorDiv);
                    field.style.borderColor = '#dc3545';
                }
                
                function clearError(field) {
                    const errorDiv = field.parentNode.querySelector('.field-error');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                    field.style.borderColor = '#e0e0e0';
                }
            }
            
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
        });
    </script>
</body>
</html>