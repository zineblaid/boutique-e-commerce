<?php
session_start();

$id     = isset($_GET['id'])     ? (int)$_GET['id']     : 0;
$action = $_GET['action'] ?? 'remove'; // 'remove' | 'decrease' | 'increase'

if ($id > 0 && isset($_SESSION['panier'][$id])) {
    switch ($action) {
        case 'increase':
            $_SESSION['panier'][$id]['quantite'] = min(99, $_SESSION['panier'][$id]['quantite'] + 1);
            break;
        case 'decrease':
            $_SESSION['panier'][$id]['quantite']--;
            if ($_SESSION['panier'][$id]['quantite'] <= 0) {
                unset($_SESSION['panier'][$id]);
            }
            break;
        case 'remove':
        default:
            unset($_SESSION['panier'][$id]);
            break;
    }
}

header('Location: panier.php');
exit;
