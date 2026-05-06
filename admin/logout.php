<?php
session_start();
$_SESSION = array(); // Vide toutes les variables de session
session_destroy();   // Détruit la session
header('Location: login.php');
exit;