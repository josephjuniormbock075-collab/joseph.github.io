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
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    
    // Vérifier les identifiants
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    
    if ($client && password_verify($mot_de_passe, $client['mot_de_passe'])) {
        // Démarrer la session et stocker les informations utilisateur
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['email'] = $client['email'];
        $_SESSION['nom'] = $client['nom'];
        $_SESSION['prenom'] = $client['prenom'];
        $_SESSION['role'] = $client['role'];
        
        // Redirection vers le dashboard approprié selon le rôle
        if ($client['role'] === 'admin') {
            header('Location: dashboard_admin.php');
            exit();
        } else {
            header('Location: dashboard_client.php');
            exit();
        }
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | OVD Boutique</title>
    
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
            max-width: 400px;
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
        <h2>Connexion</h2>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            
            <button type="submit" class="btn-auth">Se connecter</button>
        </form>
        
        <div class="auth-links">
            <p>Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 OVD_Boutique - Tous droits réservés.</p>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>