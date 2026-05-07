<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/produits_query.php';

// Récupérer l'ID depuis l'URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si pas d'ID valide → retour boutique
if ($id <= 0) {
    header('Location: boutique.php');
    exit;
}

// Récupérer le produit
$produit = getProduitById($pdo, $id);

// Produit introuvable → retour boutique
if (!$produit) {
    header('Location: boutique.php');
    exit;
}

// Produits similaires (même catégorie, sauf ce produit)
$similaires = getProduitsByCategorie($pdo, $produit['categorie_slug']);
$similaires = array_filter($similaires, fn($p) => $p['id'] !== $produit['id']);
$similaires = array_slice(array_values($similaires), 0, 4);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produit['nom']) ?> — FitZone</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', -apple-system, sans-serif;
            background: #f8f9fa;
            color: #1a1a1a;
        }

        /* ── NAV ── */
        nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 40px; background: #fff;
            border-bottom: 1px solid #e9ecef; position: sticky; top: 0; z-index: 100;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; color: #333; font-size: 1.15rem; font-weight: 700;
        }
        .nav-logo {
            width: 38px; height: 38px; background: #0bbcd4; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 0.85rem;
        }
        .nav-links { display: flex; gap: 28px; list-style: none; }
        .nav-links a { text-decoration: none; color: #555; font-size: .95rem; transition: color .2s; }
        .nav-links a:hover, .nav-links a.active { color: #0bbcd4; font-weight: 600; }
        .nav-actions { display: flex; align-items: center; gap: 18px; }
        .nav-actions a { text-decoration: none; font-size: .95rem; color: #555; }
        .nav-actions a.btn-signup {
            background: #0bbcd4; color: #fff !important;
            padding: 9px 22px; border-radius: 8px; font-weight: 600;
        }
        .cart-icon { font-size: 1.2rem; cursor: pointer; }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            padding: 16px 40px;
            font-size: .85rem; color: #aaa;
            display: flex; align-items: center; gap: 8px;
        }
        .breadcrumb a { color: #0bbcd4; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { color: #ccc; }

        /* ── MAIN PRODUCT SECTION ── */
        .product-detail {
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 24px 48px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: start;
        }

        /* Image */
        .product-gallery {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e9ecef;
            overflow: hidden;
            position: relative;
        }
        .product-gallery img {
            width: 100%; height: 420px;
            object-fit: cover;
            display: block;
            transition: transform .4s;
        }
        .product-gallery:hover img { transform: scale(1.03); }
        .product-gallery .badge {
            position: absolute; top: 16px; left: 16px;
            background: #0bbcd4; color: #fff;
            padding: 5px 14px; border-radius: 20px;
            font-size: .8rem; font-weight: 600;
        }
        .stock-badge {
            position: absolute; top: 16px; right: 16px;
            padding: 5px 14px; border-radius: 20px;
            font-size: .8rem; font-weight: 600;
        }
        .stock-badge.in  { background: #d1fae5; color: #065f46; }
        .stock-badge.out { background: #fee2e2; color: #991b1b; }

        /* Info */
        .product-info { display: flex; flex-direction: column; gap: 20px; }

        .product-category {
            font-size: .82rem; font-weight: 600;
            color: #0bbcd4; text-transform: uppercase; letter-spacing: .06em;
        }

        .product-title {
            font-size: 1.9rem; font-weight: 700;
            line-height: 1.2; color: #1a1a1a;
        }

        .product-price {
            font-size: 2rem; font-weight: 700;
            color: #0bbcd4;
        }

        .product-desc {
            font-size: .95rem; color: #555;
            line-height: 1.7;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
            padding: 18px 0;
        }

        /* Stock info */
        .stock-info {
            display: flex; align-items: center; gap: 8px;
            font-size: .9rem;
        }
        .stock-dot {
            width: 10px; height: 10px; border-radius: 50%;
        }
        .stock-dot.in  { background: #10b981; }
        .stock-dot.out { background: #ef4444; }

        /* Quantity selector */
        .qty-wrap {
            display: flex; align-items: center; gap: 12px;
        }
        .qty-wrap label { font-size: .9rem; font-weight: 600; color: #444; }
        .qty-selector {
            display: flex; align-items: center; gap: 0;
            border: 1.5px solid #e9ecef; border-radius: 10px; overflow: hidden;
        }
        .qty-btn {
            background: #f8f9fa; border: none; cursor: pointer;
            width: 38px; height: 38px;
            font-size: 1.1rem; color: #555;
            transition: background .2s;
        }
        .qty-btn:hover { background: #e9ecef; }
        .qty-input {
            width: 50px; text-align: center;
            border: none; border-left: 1.5px solid #e9ecef; border-right: 1.5px solid #e9ecef;
            font-size: .95rem; font-weight: 600; font-family: 'Outfit', sans-serif;
            height: 38px; outline: none; background: #fff;
        }

        /* CTA buttons */
        .cta-buttons {
            display: flex; gap: 12px; flex-wrap: wrap;
        }
        .btn-add-cart {
            flex: 1; min-width: 160px;
            padding: 14px 24px;
            background: #0bbcd4; color: #fff;
            border: none; border-radius: 10px;
            font-size: 1rem; font-weight: 700;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none;
            transition: background .2s, transform .15s;
        }
        .btn-add-cart:hover { background: #09a8be; transform: translateY(-1px); }
        .btn-back {
            padding: 14px 22px;
            background: #fff; color: #555;
            border: 1.5px solid #e9ecef; border-radius: 10px;
            font-size: .95rem; font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer; text-decoration: none;
            display: flex; align-items: center; gap: 8px;
            transition: border-color .2s, color .2s;
        }
        .btn-back:hover { border-color: #0bbcd4; color: #0bbcd4; }

        /* ── SIMILAR PRODUCTS ── */
        .similar-section {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px 60px;
        }
        .similar-section h2 {
            font-size: 1.4rem; font-weight: 700;
            margin-bottom: 20px; color: #1a1a1a;
        }
        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e9ecef; overflow: hidden;
            transition: box-shadow .25s, transform .25s;
        }
        .product-card:hover {
            box-shadow: 0 10px 32px rgba(0,0,0,.1);
            transform: translateY(-3px);
        }
        .product-img-wrap { position: relative; height: 180px; overflow: hidden; }
        .product-img-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .35s;
        }
        .product-card:hover .product-img-wrap img { transform: scale(1.05); }
        .product-badge-card {
            position: absolute; top: 10px; left: 10px;
            background: #0bbcd4; color: #fff;
            padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600;
        }
        .card-info { padding: 14px; }
        .card-info h3 { font-size: .93rem; font-weight: 700; margin-bottom: 6px; }
        .card-info .price { font-weight: 700; color: #0bbcd4; font-size: .95rem; }
        .card-footer {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 10px;
        }
        .btn-detail {
            padding: 6px 14px; background: #0bbcd4; color: #fff;
            border-radius: 7px; text-decoration: none;
            font-size: .82rem; font-weight: 600;
            transition: background .2s;
        }
        .btn-detail:hover { background: #09a8be; }
        .btn-cart {
            width: 32px; height: 32px;
            background: #f0fdff; border: 1.5px solid #0bbcd4;
            border-radius: 7px; color: #0bbcd4;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: .85rem;
            transition: background .2s, color .2s;
        }
        .btn-cart:hover { background: #0bbcd4; color: #fff; }

        /* Responsive */
        @media (max-width: 768px) {
            nav { padding: 12px 20px; }
            .nav-links { display: none; }
            .breadcrumb { padding: 12px 20px; }
            .product-detail {
                grid-template-columns: 1fr;
                padding: 10px 16px 40px;
                gap: 24px;
            }
            .product-gallery img { height: 300px; }
            .product-title { font-size: 1.5rem; }
            .similar-section { padding: 0 16px 40px; }
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-brand">
        <div class="nav-logo">FZ</div>
        FitZone
    </a>
    <ul class="nav-links">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="boutique.php" class="active">Boutique</a></li>
        <li><a href="#">À propos</a></li>
        <li><a href="#">Contact</a></li>
    </ul>
    <div class="nav-actions">
        <a href="cart/panier.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="auth/logout.php">Déconnexion</a>
        <?php else: ?>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php" class="btn-signup">Sign up</a>
        <?php endif; ?>
    </div>
</nav>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="boutique.php"><i class="fas fa-store"></i> Boutique</a>
    <span>›</span>
    <a href="boutique.php?categorie=<?= htmlspecialchars($produit['categorie_slug']) ?>">
        <?= htmlspecialchars($produit['categorie_nom']) ?>
    </a>
    <span>›</span>
    <?= htmlspecialchars($produit['nom']) ?>
</div>

<!-- PRODUCT DETAIL -->
<div class="product-detail">

    <!-- Image -->
    <div class="product-gallery">
        <img src="<?= htmlspecialchars($produit['image']) ?>"
             alt="<?= htmlspecialchars($produit['nom']) ?>"
             onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600'">
        <span class="badge"><?= htmlspecialchars($produit['categorie_nom']) ?></span>
        <span class="stock-badge <?= $produit['stock'] > 0 ? 'in' : 'out' ?>">
            <?= $produit['stock'] > 0 ? 'En stock' : 'Rupture' ?>
        </span>
    </div>

    <!-- Info -->
    <div class="product-info">

        <div class="product-category"><?= htmlspecialchars($produit['categorie_nom']) ?></div>

        <h1 class="product-title"><?= htmlspecialchars($produit['nom']) ?></h1>

        <div class="product-price"><?= number_format($produit['prix'], 2) ?> DA</div>

        <p class="product-desc"><?= nl2br(htmlspecialchars($produit['description'])) ?></p>

        <!-- Stock -->
        <div class="stock-info">
            <div class="stock-dot <?= $produit['stock'] > 0 ? 'in' : 'out' ?>"></div>
            <?php if ($produit['stock'] > 0): ?>
                <span style="color:#065f46; font-weight:600;">
                    En stock — <?= (int)$produit['stock'] ?> disponible<?= $produit['stock'] > 1 ? 's' : '' ?>
                </span>
            <?php else: ?>
                <span style="color:#991b1b; font-weight:600;">Rupture de stock</span>
            <?php endif; ?>
        </div>

        <!-- Quantity -->
        <?php if ($produit['stock'] > 0): ?>
        <div class="qty-wrap">
            <label for="qty">Quantité :</label>
            <div class="qty-selector">
                <button class="qty-btn" onclick="changeQty(-1)">−</button>
                <input type="number" id="qty" class="qty-input" value="1" min="1" max="<?= (int)$produit['stock'] ?>">
                <button class="qty-btn" onclick="changeQty(1)">+</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="cta-buttons">
            <?php if ($produit['stock'] > 0): ?>
            <a id="btn-panier"
               href="cart/ajouter_panier.php?id=<?= $produit['id'] ?>&qty=1"
               class="btn-add-cart">
                <i class="fas fa-cart-plus"></i> Ajouter au panier
            </a>
            <?php else: ?>
            <button class="btn-add-cart" disabled style="background:#ccc;cursor:not-allowed;">
                <i class="fas fa-times"></i> Indisponible
            </button>
            <?php endif; ?>
            <a href="boutique.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

    </div>
</div>

<!-- SIMILAR PRODUCTS -->
<?php if (!empty($similaires)): ?>
<div class="similar-section">
    <h2>Produits similaires</h2>
    <div class="similar-grid">
        <?php foreach ($similaires as $p): ?>
        <div class="product-card">
            <div class="product-img-wrap">
                <img src="<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['nom']) ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400'">
                <span class="product-badge-card"><?= htmlspecialchars($p['categorie_nom']) ?></span>
            </div>
            <div class="card-info">
                <h3><?= htmlspecialchars($p['nom']) ?></h3>
                <div class="card-footer">
                    <span class="price"><?= number_format($p['prix'], 2) ?> DA</span>
                    <div style="display:flex;gap:6px;">
                        <a href="produit.php?id=<?= $p['id'] ?>" class="btn-detail">Voir</a>
                        <a href="cart/ajouter_panier.php?id=<?= $p['id'] ?>" class="btn-cart">
                            <i class="fas fa-cart-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function changeQty(delta) {
    const input = document.getElementById('qty');
    if (!input) return;
    const max = parseInt(input.max) || 99;
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
    // Met à jour le lien du bouton panier
    const btn = document.getElementById('btn-panier');
    if (btn) {
        btn.href = `cart/ajouter_panier.php?id=<?= $produit['id'] ?>&qty=${val}`;
    }
}
// Sync manuel de l'input
document.getElementById('qty')?.addEventListener('input', function() {
    const max = parseInt(this.max) || 99;
    let val = parseInt(this.value) || 1;
    if (val < 1) val = 1;
    if (val > max) val = max;
    this.value = val;
    const btn = document.getElementById('btn-panier');
    if (btn) btn.href = `cart/ajouter_panier.php?id=<?= $produit['id'] ?>&qty=${val}`;
});
</script>

</body>
</html>