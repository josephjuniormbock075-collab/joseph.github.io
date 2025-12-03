<?php
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

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $role = isset($_POST['role']) ? htmlspecialchars($_POST['role']) : 'client'; // Détermine le rôle (client ou admin)
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Cet email est déjà utilisé.";
    } else {
        // Insérer le nouveau client/administrateur
        $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, email, telephone, adresse, mot_de_passe, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $mot_de_passe, $role])) {
            // Récupérer l'ID du nouvel utilisateur
            $user_id = $pdo->lastInsertId();
            
            // Démarrer la session et stocker les informations utilisateur
            session_start();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['nom'] = $nom;
            $_SESSION['prenom'] = $prenom;
            $_SESSION['role'] = $role;
            
            // Redirection vers le dashboard approprié
            if ($role === 'admin') {
                header('Location: dashboard_admin.php');
                exit();
            } else {
                header('Location: dashboard_client.php');
                exit();
            }
        } else {
            $error = "Erreur lors de l'inscription. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | OVD Boutique</title>
    
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.welcome h1 {
  font-size: 3rem;
  margin-bottom: 1.5rem;
  color: #764ba2;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  color: #764ba2;
  margin-bottom: 0.5rem;
}

.price {
  font-size: 1.5rem;
  font-weight: bold;
  color: #667eea;
  margin: 1rem 0;
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

.welcome {
  animation: fadeIn 1s ease-out;
}
        .auth-container {
            max-width: 500px;
            margin: 140px auto 2rem;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .auth-container h2 {
            text-align: center;
            color: #764ba2;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-auth {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn-auth:hover {
            transform: translateY(-2px);
        }
        
        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .auth-links a {
            color: #667eea;
            text-decoration: none;
        }
        
        .message {
            padding: 10px;
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
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../image/ovd.png" alt="Logo">
            <span>OVD BOUTIQUE</span>
        </div>
         <nav>
      <ul>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="commande.php">Commander</a></li>
        <li><a href="inscription.php">Inscription</a></li>
        <li><a href="connexion.php">Connexion</a></li>
        <li><a href="contact.php">Contact</a></li>
		<li><a href="payement.php">paiement</a></li>
      </ul>
    </nav>
    </header>

    <div class="auth-container">
        <h2>Créer un compte</h2>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required>
            </div>
            
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" required>
            </div>
            
            <div class="form-group">
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" required>
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            
            <button type="submit" class="btn-auth">S'inscrire</button>
        </form>
        
        <div class="auth-links">
            <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 OVD_Boutique - Tous droits réservés.</p>
    </footer>

    <script>

    </script>
</body>
</html>