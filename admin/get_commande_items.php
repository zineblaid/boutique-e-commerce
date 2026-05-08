<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Protection admin
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("
    SELECT ci.nom_produit, ci.prix_unitaire, ci.quantite, ci.produit_id
    FROM commande_items ci
    WHERE ci.commande_id = :id
    ORDER BY ci.id ASC
");
$stmt->execute([':id' => $id]);
echo json_encode($stmt->fetchAll());
