<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$lang = $_SESSION['lang'] ?? 'fr';

$t = [
    'fr' => ['home'=>'Accueil','boutique'=>'Boutique','panier'=>'Panier','connexion'=>'Connexion','inscription'=>'Inscription','logout'=>'Déconnexion','admin'=>'Administration'],
    'en' => ['home'=>'Home',   'boutique'=>'Shop',    'panier'=>'Cart',  'connexion'=>'Login',    'inscription'=>'Register',   'logout'=>'Logout',        'admin'=>'Admin Panel'],
    'ar' => ['home'=>'الرئيسية','boutique'=>'المتجر', 'panier'=>'السلة','connexion'=>'دخول',     'inscription'=>'تسجيل',      'logout'=>'خروج',          'admin'=>'لوحة الإدارة'],
];
$tx = $t[$lang] ?? $t['fr'];

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

    <!-- NAV DESKTOP -->
    <nav>
        <a href="index.php"    class="<?= $current==='index.php'    ? 'active':'' ?>"><?= $tx['home'] ?></a>
        <a href="boutique.php" class="<?= $current==='boutique.php' ? 'active':'' ?>"><?= $tx['boutique'] ?></a>
        <a href="boutique.php?categorie=homme">Homme</a>
        <a href="boutique.php?categorie=femme">Femme</a>
        <a href="boutique.php?categorie=food">Nutrition</a>
    </nav>

    <!-- ICÔNES DROITE -->
    <div class="header-icons">

        <!-- Langue -->
        <div class="lang-switcher">
            <a href="lang.php?lang=fr" class="<?= $lang==='fr'?'active':'' ?>">FR</a>
            <a href="lang.php?lang=en" class="<?= $lang==='en'?'active':'' ?>">EN</a>
            <a href="lang.php?lang=ar" class="<?= $lang==='ar'?'active':'' ?>">AR</a>
        </div>

        <!-- Panier -->
        <a href="cart/panier.php" title="<?= $tx['panier'] ?>">
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
                    <p><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur') ?></p>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="admin/admin.php">
                            <i class="fas fa-cog"></i> <?= $tx['admin'] ?>
                        </a>
                    <?php endif; ?>
                    <a href="auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> <?= $tx['logout'] ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="auth/login.php" title="<?= $tx['connexion'] ?>">
                <i class="fas fa-user"></i>
            </a>
        <?php endif; ?>

        <!-- Burger -->
        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Menu">
            <i class="fas fa-bars" id="burger-icon"></i>
        </button>

    </div>
</header>

<!-- NAV MOBILE -->
<div class="mobile-nav" id="mobileNav">
    <a href="index.php"    onclick="closeMenu()"><?= $tx['home'] ?></a>
    <a href="boutique.php" onclick="closeMenu()"><?= $tx['boutique'] ?></a>
    <a href="boutique.php?categorie=homme" onclick="closeMenu()">Homme</a>
    <a href="boutique.php?categorie=femme" onclick="closeMenu()">Femme</a>
    <a href="boutique.php?categorie=food"  onclick="closeMenu()">Nutrition</a>
    <a href="cart/panier.php" onclick="closeMenu()">
        <?= $tx['panier'] ?><?= $cart_count > 0 ? " ($cart_count)" : '' ?>
    </a>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <a href="admin/admin.php"><?= $tx['admin'] ?></a>
        <?php endif; ?>
        <a href="auth/logout.php"><?= $tx['logout'] ?></a>
    <?php else: ?>
        <a href="auth/login.php"><?= $tx['connexion'] ?></a>
        <a href="auth/register.php"><?= $tx['inscription'] ?></a>
    <?php endif; ?>
    <!-- Langue mobile -->
    <div style="display:flex;gap:8px;padding:14px 0;">
        <a href="lang.php?lang=fr" style="padding:4px 10px;border:1px solid rgba(255,255,255,0.2);border-radius:20px;font-size:.8rem;color:var(--gray);">FR</a>
        <a href="lang.php?lang=en" style="padding:4px 10px;border:1px solid rgba(255,255,255,0.2);border-radius:20px;font-size:.8rem;color:var(--gray);">EN</a>
        <a href="lang.php?lang=ar" style="padding:4px 10px;border:1px solid rgba(255,255,255,0.2);border-radius:20px;font-size:.8rem;color:var(--gray);">AR</a>
    </div>
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
// Fermer si clic extérieur
document.addEventListener('click', function(e){
    const nav    = document.getElementById('mobileNav');
    const toggle = document.querySelector('.menu-toggle');
    if (nav && nav.classList.contains('open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
        closeMenu();
    }
});
</script>

