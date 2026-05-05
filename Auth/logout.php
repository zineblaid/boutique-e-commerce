<?php
// ✅ FIX : vérification session_status pour éviter le double démarrage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ FIX : protection CSRF — logout uniquement via POST avec token valide
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!empty($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token)) {
        session_unset();
        session_destroy();

        // Supprime le cookie de session côté navigateur
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        header("Location: ../index.php");
        exit();
    }
}

// ✅ Si accès direct (GET) ou token invalide → redirection sans déconnexion
header("Location: ../index.php");
exit();
?>