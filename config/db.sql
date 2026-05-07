
--  Tables  : utilisateurs, categories, produits, panier


CREATE DATABASE IF NOT EXISTS ecommerce
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecommerce;


-- TABLE : utilisateurs

CREATE TABLE IF NOT EXISTS utilisateurs (
    id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- mot de passe hashé (password_hash)
    role       ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- TABLE : categories

CREATE TABLE IF NOT EXISTS categories (
    id   INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom  VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE          -- ex: vetement-homme
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- TABLE : produits

CREATE TABLE IF NOT EXISTS produits (
    id           INT            NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(150)   NOT NULL,
    description  TEXT           NOT NULL,
    prix         DECIMAL(10,2)  NOT NULL,
    stock        INT            NOT NULL DEFAULT 0,
    image        VARCHAR(255)   NOT NULL DEFAULT 'images/default.jpg',
    categorie_id INT            NOT NULL,
    created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- TABLE : panier

CREATE TABLE IF NOT EXISTS panier (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    produit_id  INT NOT NULL,
    quantite    INT NOT NULL DEFAULT 1,
    ajout_le    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)     ON DELETE CASCADE,
    UNIQUE KEY unique_panier (user_id, produit_id)       -- évite les doublons
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--  DONNÉES INITIALES


-- ---- Catégories ----
INSERT INTO categories (nom, slug) VALUES
    ('Vêtement Homme',  'vetement-homme'),
    ('Vêtement Femme',  'vetement-femme'),
    ('Nutrition',       'nutrition'),
    ('Matériel',        'materiel');

-- ---- Utilisateurs (admin + user test) ----
-- Mot de passe admin  : Admin1234   (hashé avec password_hash)
-- Mot de passe user   : User1234
INSERT INTO utilisateurs (username, email, password, role) VALUES
    ('admin',
     'admin@ecommerce.dz',
     '$2y$12$XgBpj7ZwPsEi8mBgVwB5EulG3kJJ8vTkqOPF2xVz7pVFQbY9Rkepu',
     'admin'),
    ('testuser',
     'user@ecommerce.dz',
     '$2y$12$Q1K8K4eF5Z5zXYwO0C5RB.IcnGHlGVHb2vjfVLwjpzDdlqBzb7AYO',
     'user');

-- ---- Produits – Vêtement Homme (categorie_id = 1) ----
INSERT INTO produits (nom, description, prix, stock, image, categorie_id) VALUES
    ('T-shirt Sport Homme',
     'T-shirt léger en polyester respirant, idéal pour le sport et les sorties quotidiennes. Disponible en plusieurs tailles.',
     1290.00, 50, 'images/tshirt_homme.jpg', 1),

    ('Jean Slim Homme',
     'Jean slim coupe moderne, tissu denim stretch confortable. Résistant et tendance pour toutes les occasions.',
     3500.00, 30, 'images/jean_homme.jpg', 1),

    ('Veste à Capuche Homme',
     'Sweat à capuche chaud et confortable, idéal pour les soirées fraîches. Poche kangourou pratique.',
     2800.00, 25, 'images/veste_homme.jpg', 1),

    ('Short Running Homme',
     'Short de running léger avec poche latérale zippée. Tissu anti-transpiration pour un confort optimal.',
     1500.00, 40, 'images/short_homme.jpg', 1);

-- ---- Produits – Vêtement Femme (categorie_id = 2) ----
INSERT INTO produits (nom, description, prix, stock, image, categorie_id) VALUES
    ('Robe d\'Été Femme',
     'Robe légère et élégante parfaite pour l\'été. Tissu fluide, motif floral délicat, coupe droite.',
     3200.00, 35, 'images/robe_femme.jpg', 2),

    ('Legging Sport Femme',
     'Legging taille haute gainant, tissu compressif qui soutient les muscles. Parfait pour le yoga et la gym.',
     1800.00, 60, 'images/legging_femme.jpg', 2),

    ('Blouse Élégante Femme',
     'Blouse en satin doux, coupe ajustée, idéale pour le bureau comme pour les sorties.',
     2500.00, 20, 'images/blouse_femme.jpg', 2),

    ('Veste Casual Femme',
     'Veste légère tendance avec fermeture éclair, poches fonctionnelles, style décontracté.',
     3800.00, 15, 'images/veste_femme.jpg', 2);

-- ---- Produits – Nutrition (categorie_id = 3) ----
INSERT INTO produits (nom, description, prix, stock, image, categorie_id) VALUES
    ('Whey Protein 1kg',
     'Protéine de lactosérum de haute qualité, 24g de protéines par portion. Goût chocolat. Idéale post-entraînement.',
     4500.00, 100, 'images/whey_protein.jpg', 3),

    ('BCAA 200 Capsules',
     'Acides aminés ramifiés (L-Leucine, L-Isoleucine, L-Valine) pour favoriser la récupération musculaire.',
     2200.00, 80, 'images/bcaa.jpg', 3),

    ('Créatine Monohydrate 500g',
     'Créatine pure micronisée pour améliorer les performances sportives et augmenter la force musculaire.',
     3000.00, 60, 'images/creatine.jpg', 3),

    ('Barre Protéinée x12',
     'Boîte de 12 barres protéinées, 20g de protéines par barre. Sans sucre ajouté. Goût caramel-chocolat.',
     2800.00, 45, 'images/barre_proteinee.jpg', 3),

    ('Multivitamines Sport 90 cp',
     'Complexe multivitaminé spécialement formulé pour les sportifs. Vitamines A, B, C, D, E + minéraux essentiels.',
     1800.00, 70, 'images/multivitamines.jpg', 3);

-- ---- Produits – Matériel (categorie_id = 4) ----
INSERT INTO produits (nom, description, prix, stock, image, categorie_id) VALUES
    ('Haltères Réglables 20kg',
     'Paire d\'haltères réglables de 2kg à 20kg. Système de serrage rapide, poignée antidérapante.',
     8500.00, 20, 'images/halteres.jpg', 4),

    ('Tapis de Yoga 6mm',
     'Tapis de yoga antidérapant, épaisseur 6mm pour un confort maximal. Matière écologique TPE.',
     1500.00, 50, 'images/tapis_yoga.jpg', 4),

    ('Corde à Sauter Pro',
     'Corde à sauter avec roulements à billes, poignées ergonomiques, longueur réglable. Idéale cardio.',
     900.00, 80, 'images/corde_sauter.jpg', 4),

    ('Bande Élastique de Résistance (Set x5)',
     'Set de 5 bandes élastiques de résistances différentes (XL à XXH). Idéal pour la rééducation et la musculation.',
     1200.00, 65, 'images/bandes_elastiques.jpg', 4),

    ('Gants de Musculation',
     'Gants rembourrés pour protéger les paumes lors des exercices de musculation. Velcro ajustable.',
     850.00, 90, 'images/gants_muscu.jpg', 4),

    ('Banc de Musculation Pliant',
     'Banc multifonction inclinable/déclinable, structure acier robuste, charge max 150kg. Compact et facile à ranger.',
     15000.00, 10, 'images/banc_muscu.jpg', 4);