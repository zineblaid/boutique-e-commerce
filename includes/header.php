<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Compteur panier
$cart_count = 0;
if (!empty($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $cart_count += (int)($item['quantite'] ?? 1);
    }
}

$current = basename($_SERVER['PHP_SELF']);
?>
<header>

    <!-- LOGO -->
    <a href="index.php" class="header-logo">
        <span class="logo-box">FZ</span> FitZone
    </a>

    <!-- NAV DESKTOP : Home + Shop à gauche -->
    <nav>
        <a href="index.php"    class="<?= $current==='index.php'    ? 'active':'' ?>">Home</a>
        <a href="boutique.php" class="<?= $current==='boutique.php' ? 'active':'' ?>">Shop</a>
    </nav>

    <!-- ICÔNES DROITE -->
    <div class="header-icons">

        <!-- Panier -->
        <a href="cart/panier.php" title="Panier">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <!-- Compte utilisateur -->
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-dropdown-wrap">
                <a href="#"><i class="fas fa-user-circle"></i></a>
                <div class="user-dropdown">
                    <p><?= htmlspecialchars($_SESSION['user_nom'] ?? $_SESSION['name'] ?? 'Utilisateur') ?></p>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="admin/admin.php">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="auth/login.php" title="Connexion">
                <i class="fas fa-user"></i>
            </a>
        <?php endif; ?>

        <!-- Burger mobile -->
        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Menu">
            <i class="fas fa-bars" id="burger-icon"></i>
        </button>

    </div>
</header>

<!-- NAV MOBILE -->
<div class="mobile-nav" id="mobileNav">
    <a href="index.php"    onclick="closeMenu()">Home</a>
    <a href="boutique.php" onclick="closeMenu()">Shop</a>
    <a href="cart/panier.php" onclick="closeMenu()">
        Panier <?= $cart_count > 0 ? "($cart_count)" : '' ?>
    </a>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <a href="admin/admin.php">Admin</a>
        <?php endif; ?>
        <a href="auth/logout.php">Déconnexion</a>
    <?php else: ?>
        <a href="auth/login.php">Connexion</a>
        <a href="auth/register.php">Inscription</a>
    <?php endif; ?>
</div>

<script>
function toggleMenu() {
    const nav  = document.getElementById('mobileNav');
    const icon = document.getElementById('burger-icon');
    nav.classList.toggle('open');
    icon.className = nav.classList.contains('open') ? 'fas fa-times' : 'fas fa-bars';
}
function closeMenu() {
    document.getElementById('mobileNav').classList.remove('open');
    document.getElementById('burger-icon').className = 'fas fa-bars';
}
document.addEventListener('click', function(e) {
    const nav    = document.getElementById('mobileNav');
    const toggle = document.querySelector('.menu-toggle');
    if (nav && nav.classList.contains('open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
        closeMenu();
    }
});
</script>