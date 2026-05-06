<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/produits_query.php';

$id  = isset($_GET['id'])  ? (int)$_GET['id']  : 0;
$qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($id <= 0) {
    header('Location: ../index.php');
    exit;
}

// Vérifier que le produit existe
$produit = getProduitById($pdo, $id);
if (!$produit) {
    header('Location: ../index.php');
    exit;
}

// Initialiser le panier si nécessaire
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$qty = max(1, min(99, $qty));

// Ajouter ou mettre à jour la quantité
if (isset($_SESSION['panier'][$id])) {
    $_SESSION['panier'][$id]['quantite'] = min(99, $_SESSION['panier'][$id]['quantite'] + $qty);
} else {
    $_SESSION['panier'][$id] = [
        'id'       => $produit['id'],
        'nom'      => $produit['nom'],
        'prix'     => $produit['prix'],
        'image'    => $produit['image'],
        'quantite' => $qty,
    ];
}

$_SESSION['flash_success'] = "« {$produit['nom']} » ajouté au panier.";

// Rediriger vers la page précédente ou le panier
$referer = $_SERVER['HTTP_REFERER'] ?? '../cart/panier.php';
header('Location: ' . $referer);
exit;
