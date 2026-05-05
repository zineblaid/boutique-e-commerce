<?php

//  config.php – Connexion à la base de données MySQL


// ---- Paramètres de connexion 
define('DB_NAME',    'ecommerce');
define('DB_USER',    'root');       // ← changer selon votre config
define('DB_PASS',    '');           // ← changer selon votre config
define('DB_CHARSET', 'utf8mb4');

// ---- Connexion PDO 
    $dsn = "mysql:host=" . DB_HOST
         . ";dbname=" . DB_NAME
         . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // affiche les erreurs SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // résultats en tableau associatif
        PDO::ATTR_EMULATE_PREPARES   => false,                   // requêtes préparées réelles
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // En production : ne pas afficher le message détaillé
    error_log("Erreur BD : " . $e->getMessage());
    die("Connexion à la base de données impossible. Veuillez réessayer plus tard.");
}

