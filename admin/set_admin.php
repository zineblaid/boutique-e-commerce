<?php
// ⚠️  FICHIER DE MAINTENANCE — À SUPPRIMER APRÈS USAGE
// Accès restreint à localhost uniquement
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Accès interdit.');
}

require_once __DIR__ . '/../config/config.php';

$newPassword = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE users
    SET password = ?
    WHERE email = ?
");

$stmt->execute([$newPassword, 'admin@ecommerce.dz']);

echo "Mot de passe admin mis à jour avec succès.";
?>