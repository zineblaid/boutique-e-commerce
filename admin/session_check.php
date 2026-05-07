<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de la session admin
if (empty($_SESSION['admin_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Si pas de session valide, direction la page de login (même dossier)
    header("Location: login.php");
    exit();
}