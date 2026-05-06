<?php

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