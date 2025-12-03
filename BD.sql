-- Création de la base de données
CREATE DATABASE IF NOT EXISTS boutique_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE boutique_pro;

-- Table des clients
-- Table des clients
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    adresse TEXT NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('actif', 'inactif') DEFAULT 'actif'
);

-- Table des administrateurs
CREATE TABLE administrateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    niveau_acces ENUM('superadmin', 'admin', 'moderateur') DEFAULT 'admin'
);
-- Table des catégories de produits
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255)
);

-- Table des produits
CREATE TABLE produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categorie_id INT,
    image VARCHAR(255),
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('disponible', 'indisponible') DEFAULT 'disponible',
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table des commandes
CREATE TABLE commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'confirmee', 'expediee', 'livree', 'annulee') DEFAULT 'en_attente',
    total DECIMAL(10,2) NOT NULL,
    adresse_livraison TEXT NOT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Table des détails de commande
CREATE TABLE details_commande (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Table des paiements
CREATE TABLE paiements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    methode ENUM('carte', 'paypal', 'virement', 'especes') NOT NULL,
    statut ENUM('en_attente', 'valide', 'echec') DEFAULT 'en_attente',
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(255),
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
);

-- Insertion de données exemple
INSERT INTO categories (nom, description) VALUES 
('Cosmétiques', 'Produits de beauté et soins de la peau'),
('Vêtements', 'Vêtements pour hommes, femmes et enfants'),
('Chaussures', 'Chaussures de toutes tailles et styles');

INSERT INTO produits (nom, description, prix, stock, categorie_id) VALUES 
('Crème hydratante', 'Crème hydratante pour le visage', 25.99, 50, 1),
('Rouge à lèvres', 'Rouge à lèvres longue tenue', 15.50, 100, 1),
('T-shirt homme', 'T-shirt coton 100%', 19.99, 30, 2),
('Robe été', 'Robe légère pour l''été', 45.00, 25, 2),
('Baskets sport', 'Baskets confortables pour le sport', 79.99, 40, 3),
('Sandales femme', 'Sandales élégantes pour l''été', 35.50, 35, 3);

-- Création d'un utilisateur admin (optionnel)
CREATE USER IF NOT EXISTS 'admin_boutique'@'localhost' IDENTIFIED BY 'admin123';
GRANT ALL PRIVILEGES ON boutique_pro.* TO 'admin_boutique'@'localhost';
FLUSH PRIVILEGES;



CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('question', 'probleme', 'satisfaction', 'reclamation') NOT NULL,
    statut ENUM('non_lu', 'lu', 'repondu') DEFAULT 'non_lu',
    reponse TEXT NULL,
    date_envoi DATETIME NOT NULL,
    date_reponse DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_messages_client_id ON messages(client_id);
CREATE INDEX idx_messages_statut ON messages(statut);
CREATE INDEX idx_messages_date_envoi ON messages(date_envoi);

ALTER TABLE clients 
ADD COLUMN role ENUM('client', 'admin') DEFAULT 'client' AFTER mot_de_passe;

-- Ajoutez ceci à votre fichier BD.sql existant :

-- Table des paramètres du site
CREATE TABLE IF NOT EXISTS parametres_site (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_site VARCHAR(255) NOT NULL DEFAULT 'OVD BOUTIQUE',
    couleur_principale VARCHAR(7) NOT NULL DEFAULT '#764ba2',
    couleur_secondaire VARCHAR(7) NOT NULL DEFAULT '#667eea',
    image_fond VARCHAR(255),
    logo VARCHAR(255) DEFAULT 'image/ovd.png',
    actif BOOLEAN DEFAULT TRUE
);

-- Table des médias promotionnels
CREATE TABLE IF NOT EXISTS medias_promotion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    chemin VARCHAR(255) NOT NULL,
    type ENUM('image', 'video') NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer des paramètres par défaut
INSERT INTO parametres_site (nom_site, couleur_principale, couleur_secondaire) 
VALUES ('OVD BOUTIQUE', '#764ba2', '#667eea');

-- Insérer des médias promotionnels par défaut
INSERT INTO medias_promotion (titre, description, chemin, type) VALUES
('Boutique en ligne', 'Découvrez notre large gamme de produits', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', 'image'),
('Promotions', 'Profitez de nos offres spéciales', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', 'image');

-- Insertion d'un enregistrement par défaut
INSERT INTO parametres_site (nom_site, couleur_principale, image_fond, video_promotion, logo) 
VALUES ('OVD BOUTIQUE', '#764ba2', NULL, NULL, 'image/ovd.png');

-- Table des messages privés
CREATE TABLE IF NOT EXISTS messages_prives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expediteur_id INT NOT NULL,
    expediteur_type ENUM('client', 'admin') NOT NULL,
    destinataire_id INT NOT NULL,
    destinataire_type ENUM('client', 'admin') NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date_envoi DATETIME NOT NULL,
    date_lecture DATETIME NULL,
    statut ENUM('non_lu', 'lu') DEFAULT 'non_lu',
    FOREIGN KEY (expediteur_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES administrateurs(id) ON DELETE CASCADE
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_messages_prives_expediteur ON messages_prives(expediteur_id, expediteur_type);
CREATE INDEX idx_messages_prives_destinataire ON messages_prives(destinataire_id, destinataire_type);
CREATE INDEX idx_messages_prives_statut ON messages_prives(statut);
CREATE INDEX idx_messages_prives_date ON messages_prives(date_envoi);