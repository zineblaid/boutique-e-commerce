<?php
session_start();
$allowed = ['fr', 'en', 'ar'];
$lang = $_GET['lang'] ?? 'fr';
if (in_array($lang, $allowed)) {
    $_SESSION['lang'] = $lang;
}
$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header('Location: ' . $referer);
exit;

