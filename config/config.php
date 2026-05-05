<?php

// config.php – Connexion à la base de données MySQL

// ---- Paramètres de connexion ----
define('DB_HOST',    'localhost');   // ✅ FIX : DB_HOST manquait complètement
define('DB_NAME',    'ecommerce');
define('DB_USER',    'root');
define('DB_PASS',    '');            // XAMPP : mot de passe vide par défaut
define('DB_CHARSET', 'utf8mb4');

// ---- Connexion PDO ----
try {                                // ✅ FIX : try { manquait avant le $dsn

    $dsn = "mysql:host=" . DB_HOST
         . ";dbname=" . DB_NAME
         . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    error_log("Erreur BD : " . $e->getMessage());
    die("Connexion à la base de données impossible. Veuillez réessayer plus tard.");
}