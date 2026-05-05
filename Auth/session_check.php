<?php
// ✅ FIX : on vérifie d'abord si la session est déjà démarrée
// pour éviter l'erreur "session already started" si la page hôte
// a déjà appelé session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    // ✅ FIX : on sauvegarde l'URL demandée pour rediriger après login
    $_SESSION["redirect_after_login"] = $_SERVER["REQUEST_URI"];

    header("Location: ../auth/login.php");
    exit();
}