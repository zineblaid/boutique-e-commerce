<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Préfixe de chemin automatique selon le sous-dossier
$depth = '';
$path  = $_SERVER['PHP_SELF'] ?? '';
if (strpos($path, '/cart/')  !== false) $depth = '../';
if (strpos($path, '/admin/') !== false) $depth = '../';
if (strpos($path, '/auth/')  !== false) $depth = '../';

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
    <a href="<?= $depth ?>index.php" class="header-logo">
        <span class="logo-box">FZ</span> FitZone
    </a>
    <nav>
        <a href="<?= $depth ?>index.php"    class="<?= $current==='index.php'    ? 'active':'' ?>">Accueil</a>
        <a href="<?= $depth ?>Boutique.php" class="<?= $current==='Boutique.php' ? 'active':'' ?>">Boutique</a>
        <span class="nav-text">À propos</span>
        <span class="nav-text">Contact</span>
    </nav>
    <div class="header-icons">

        <!-- Panier -->
        <a href="<?= $depth ?>cart/panier.php" title="Panier" style="position:relative;">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <!-- Icône compte (toujours visible) -->
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-dropdown-wrap">
                <a href="#"><i class="fas fa-user-circle"></i></a>
                <div class="user-dropdown">
                    <p><?= htmlspecialchars($_SESSION['user_nom'] ?? $_SESSION['name'] ?? 'Utilisateur') ?></p>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="<?= $depth ?>admin/admin.php">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a href="<?= $depth ?>auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?= $depth ?>auth/login.php" title="Connexion">
                <i class="fas fa-user"></i>
            </a>
        <?php endif; ?>

        <!-- Icône paramètres → admin/login.php (TOUJOURS visible) -->
        <a href="<?= $depth ?>admin/login.php" title="Administration" style="position:relative;">
            <i class="fas fa-cog"></i>
        </a>

        <!-- Burger mobile -->
        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Menu">
            <i class="fas fa-bars" id="burger-icon"></i>
        </button>
    </div>
</header>

<!-- NAV MOBILE -->
<div class="mobile-nav" id="mobileNav">
    <a href="<?= $depth ?>index.php"    onclick="closeMenu()">Accueil</a>
    <a href="<?= $depth ?>boutique.php" onclick="closeMenu()">Boutique</a>
    <span class="nav-text" style="padding:12px 24px;display:block;color:rgba(255,255,255,.5);font-size:.95rem;">À propos</span>
    <span class="nav-text" style="padding:12px 24px;display:block;color:rgba(255,255,255,.5);font-size:.95rem;">Contact</span>
    <a href="<?= $depth ?>cart/panier.php" onclick="closeMenu()">
        Panier <?= $cart_count > 0 ? "($cart_count)" : '' ?>
    </a>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="<?= $depth ?>auth/logout.php" onclick="closeMenu()">Déconnexion</a>
    <?php else: ?>
        <a href="<?= $depth ?>auth/login.php"    onclick="closeMenu()">Connexion</a>
        <a href="<?= $depth ?>auth/register.php" onclick="closeMenu()">Inscription</a>
    <?php endif; ?>
    <a href="<?= $depth ?>admin/login.php" onclick="closeMenu()">
        <i class="fas fa-cog"></i> Administration
    </a>
</div>

<style>
/* Texte simple sans lien dans la nav */
.nav-text {
    color: rgba(82, 72, 72, 0.75);
    font-size: .95rem;
    cursor: default;
    user-select: none;
}
</style>

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
