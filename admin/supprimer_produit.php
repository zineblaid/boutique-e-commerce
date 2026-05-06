<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/produits_query.php';

// Protection admin
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier l'ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash_error'] = "Produit invalide.";
    header('Location: admin.php');
    exit;
}

// Récupérer le produit avant suppression
$produit = getProduitById($pdo, $id);

if (!$produit) {
    $_SESSION['flash_error'] = "Produit introuvable.";
    header('Location: admin.php');
    exit;
}

// Supprimer
if (deleteProduit($pdo, $id)) {
    $_SESSION['flash_success'] = "Produit « " . htmlspecialchars($produit['nom']) . " » supprimé avec succès.";
} else {
    $_SESSION['flash_error'] = "Erreur lors de la suppression.";
}

header('Location: admin.php');
exit;
?>