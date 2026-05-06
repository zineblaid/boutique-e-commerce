<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/produits_query.php';

// Protection admin
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

// Accepter POST uniquement (le formulaire dans admin.php envoie en POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash_error'] = "Produit invalide.";
    header('Location: admin.php');
    exit;
}

$produit = getProduitById($pdo, $id);

if (!$produit) {
    $_SESSION['flash_error'] = "Produit introuvable.";
    header('Location: admin.php');
    exit;
}

if (deleteProduit($pdo, $id)) {
    $_SESSION['flash_success'] = "Produit « " . htmlspecialchars($produit['nom']) . " » supprimé avec succès.";
} else {
    $_SESSION['flash_error'] = "Erreur lors de la suppression.";
}

header('Location: admin.php');
exit;