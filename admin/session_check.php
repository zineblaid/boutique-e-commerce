<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    empty($_SESSION['admin_id']) ||
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../admin/login.php");
    exit();
}