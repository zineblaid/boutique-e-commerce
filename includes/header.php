<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <a href="/FitZone/index.php" class="header-logo">
        <span class="logo-box">FZ</span> FitZone
    </a>

    <nav>
        <a href="/FitZone/index.php"    class="<?= $current==='index.php'    ? 'active':'' ?>">Accueil</a>
        <a href="/FitZone/boutique.php" class="<?= $current==='boutique.php' ? 'active':'' ?>">Boutique</a>
        <a href="/FitZone/apropos.php"  class="<?= $current==='apropos.php'  ? 'active':'' ?>">À propos</a>
        <a href="/FitZone/contact.php"  class="<?= $current==='contact.php'  ? 'active':'' ?>">Contact</a>
    </nav>

    <div class="header-icons">
        <a href="/FitZone/cart/panier.php" title="Panier" style="position:relative;">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-dropdown-wrap">
                <a href="#"><i class="fas fa-user-circle"></i></a>
                <div class="user-dropdown">
                    <p><?= htmlspecialchars($_SESSION['name'] ?? 'Utilisateur') ?></p>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="/FitZone/admin/admin.php">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a href="/FitZone/Auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="/FitZone/Auth/login.php" title="Connexion">Login</a>
            <a href="/FitZone/Auth/register.php" class="btn-signup">Sign up</a>
        <?php endif; ?>

        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Menu">
            <i class="fas fa-bars" id="burger-icon"></i>
        </button>
    </div>
</header>

<div class="mobile-nav" id="mobileNav">
    <a href="/FitZone/index.php"    onclick="closeMenu()">Accueil</a>
    <a href="/FitZone/boutique.php" onclick="closeMenu()">Boutique</a>
    <a href="/FitZone/apropos.php"  onclick="closeMenu()">À propos</a>
    <a href="/FitZone/contact.php"  onclick="closeMenu()">Contact</a>
    <a href="/FitZone/cart/panier.php" onclick="closeMenu()">
        Panier <?= $cart_count > 0 ? "($cart_count)" : '' ?>
    </a>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="/FitZone/Auth/logout.php">Déconnexion</a>
    <?php else: ?>
        <a href="/FitZone/Auth/login.php">Connexion</a>
        <a href="/FitZone/Auth/register.php">Inscription</a>
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
</script>