<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/produits_query.php';

// Paramètres de filtre
$slug    = isset($_GET['categorie']) && $_GET['categorie'] !== '' ? $_GET['categorie'] : null;
$motCle  = isset($_GET['q']) && $_GET['q'] !== '' ? trim($_GET['q']) : null;
$prixMax = isset($_GET['prix_max']) && $_GET['prix_max'] !== '' ? (float)$_GET['prix_max'] : null;
$sort    = $_GET['sort'] ?? 'newest';

// Récupérer catégories et produits
$categories = getAllCategories($pdo);
$produits   = filterProduits($pdo, $slug, null, $prixMax, $motCle);

// Tri
if ($sort === 'price_asc')  usort($produits, fn($a,$b) => $a['prix'] <=> $b['prix']);
if ($sort === 'price_desc') usort($produits, fn($a,$b) => $b['prix'] <=> $a['prix']);
if ($sort === 'newest')     usort($produits, fn($a,$b) => strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0'));

// Prix max dynamique
$stmt = $pdo->query("SELECT MAX(prix) FROM produits");
$maxPrixDB = (int)($stmt->fetchColumn() ?: 200);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop — FitZone</title>
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

        /* ── PAGE HEADER ── */
        .page-header {
            padding: 40px 40px 20px;
            background: #fff; border-bottom: 1px solid #e9ecef;
        }
        .page-header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 6px; }
        .page-header p  { color: #777; font-size: .95rem; }

        /* ── LAYOUT ── */
        .shop-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 28px;
            max-width: 1300px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* ── SIDEBAR ── */
        .sidebar { display: flex; flex-direction: column; gap: 20px; }

        .filter-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e9ecef; padding: 24px;
        }
        .filter-card h3 {
            font-size: 1rem; font-weight: 700;
            margin-bottom: 18px; color: #1a1a1a;
        }

        /* Catégories checkboxes */
        .cat-list { display: flex; flex-direction: column; gap: 12px; }
        .cat-item {
            display: flex; align-items: center; gap: 10px;
            font-size: .92rem; color: #444; cursor: pointer;
        }
        .cat-item input[type=checkbox] { display: none; }
        .cat-item .checkbox {
            width: 20px; height: 20px; border-radius: 5px;
            border: 2px solid #dde2e8; background: #fff;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s; flex-shrink: 0;
        }
        .cat-item input:checked + .checkbox {
            background: #0bbcd4; border-color: #0bbcd4;
        }
        .cat-item input:checked + .checkbox::after {
            content: '✓'; color: #fff; font-size: .75rem; font-weight: 700;
        }
        .cat-item label { cursor: pointer; }

        /* Price range */
        .price-range-wrap { display: flex; flex-direction: column; gap: 12px; }
        .price-labels { display: flex; justify-content: space-between; font-size: .85rem; color: #888; }

        input[type=range] {
            -webkit-appearance: none;
            width: 100%; height: 4px;
            background: linear-gradient(to right, #0bbcd4 0%, #0bbcd4 100%);
            border-radius: 4px; outline: none; cursor: pointer;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px; height: 20px; border-radius: 50%;
            background: #fff; border: 3px solid #0bbcd4;
            box-shadow: 0 2px 6px rgba(11,188,212,.3); cursor: pointer;
        }

        /* ── MAIN ── */
        .shop-main { display: flex; flex-direction: column; gap: 20px; }

        /* Toolbar */
        .toolbar {
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .search-wrap {
            flex: 1; min-width: 200px;
            display: flex; align-items: center; gap: 10px;
            background: #fff; border: 1.5px solid #e9ecef;
            border-radius: 10px; padding: 10px 16px;
        }
        .search-wrap i { color: #aab; }
        .search-wrap input {
            border: none; outline: none; width: 100%;
            font-size: .95rem; font-family: 'Outfit', sans-serif;
            background: transparent; color: #333;
        }
        .search-wrap input::placeholder { color: #bbb; }

        .sort-select {
            padding: 10px 16px; border: 1.5px solid #e9ecef;
            border-radius: 10px; font-size: .92rem;
            font-family: 'Outfit', sans-serif;
            background: #fff; color: #333; cursor: pointer; outline: none;
        }

        /* Products grid */
        .products-grid {
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
        .product-img-wrap { position: relative; height: 200px; overflow: hidden; }
        .product-img-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .35s;
        }
        .product-card:hover .product-img-wrap img { transform: scale(1.05); }
        .product-badge {
            position: absolute; top: 10px; left: 10px;
            background: #0bbcd4; color: #fff;
            padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600;
        }
        .product-info { padding: 16px; }
        .product-info h3 { font-size: .97rem; font-weight: 700; margin-bottom: 6px; color: #1a1a1a; }
        .product-desc { font-size: .82rem; color: #888; margin-bottom: 14px; line-height: 1.5; }
        .product-footer {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        .price { font-weight: 700; color: #0bbcd4; font-size: 1rem; flex: 1; }
        .btn-detail {
            padding: 7px 14px; background: #0bbcd4; color: #fff;
            border-radius: 7px; font-size: .82rem; font-weight: 600;
            text-decoration: none; transition: background .2s;
        }
        .btn-detail:hover { background: #09a8be; }
        .btn-cart {
            width: 34px; height: 34px; border-radius: 7px;
            background: #f0f0f0; display: flex; align-items: center; justify-content: center;
            text-decoration: none; color: #555; font-size: .85rem;
            transition: background .2s, color .2s;
        }
        .btn-cart:hover { background: #0bbcd4; color: #fff; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 80px 20px;
            color: #aab; font-size: 1rem; grid-column: 1/-1;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 14px; display: block; opacity: .4; }

        /* Count */
        .results-count { font-size: .88rem; color: #888; padding-bottom: 4px; }
        .results-count strong { color: #1a1a1a; }

        @media(max-width: 900px) {
            .shop-layout { grid-template-columns: 1fr; }
            .sidebar { display: none; }
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
        <li><a href="index.php">Home</a></li>
        <li><a href="boutique.php" class="active">Shop</a></li>
    </ul>
    <div class="nav-actions">
        <span class="cart-icon">🛒</span>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php" class="btn-signup">Sign up</a>
        <?php endif; ?>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <h1>Shop all products</h1>
    <p>Discover our complete range of fitness gear and nutrition</p>
</div>

<!-- SHOP LAYOUT -->
<div class="shop-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <!-- Categories -->
        <div class="filter-card">
            <h3>Categories</h3>
            <div class="cat-list">
                <?php foreach ($categories as $cat): ?>
                <label class="cat-item">
                    <input type="checkbox"
                           class="cat-check"
                           value="<?= htmlspecialchars($cat['slug']) ?>"
                           <?= ($slug === $cat['slug']) ? 'checked' : '' ?>>
                    <span class="checkbox"></span>
                    <span><?= htmlspecialchars($cat['nom']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Price range -->
        <div class="filter-card">
            <h3>Price range</h3>
            <div class="price-range-wrap">
                <input type="range" id="price-range"
                       min="0" max="<?= $maxPrixDB ?>"
                       value="<?= $prixMax ?? $maxPrixDB ?>">
                <div class="price-labels">
                    <span>$0</span>
                    <span id="price-label">$<?= $prixMax ?? $maxPrixDB ?></span>
                </div>
            </div>
        </div>

    </aside>

    <!-- MAIN -->
    <main class="shop-main">

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input"
                       placeholder="Search products..."
                       value="<?= htmlspecialchars($motCle ?? '') ?>">
            </div>
            <select class="sort-select" id="sort-select">
                <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest first</option>
                <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
            </select>
        </div>

        <!-- Count -->
        <div class="results-count">
            <strong><?= count($produits) ?></strong> product<?= count($produits) !== 1 ? 's' : '' ?> found
        </div>

        <!-- Grid -->
        <div class="products-grid" id="products-grid">
            <?php if (empty($produits)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    No products found matching your criteria
                </div>
            <?php else: ?>
                <?php foreach ($produits as $p): ?>
                <div class="product-card"
                     data-name="<?= strtolower(htmlspecialchars($p['nom'])) ?>"
                     data-price="<?= $p['prix'] ?>"
                     data-cat="<?= htmlspecialchars($p['categorie_slug']) ?>">
                    <div class="product-img-wrap">
                        <img src="<?= htmlspecialchars($p['image']) ?>"
                             alt="<?= htmlspecialchars($p['nom']) ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400'">
                        <span class="product-badge"><?= htmlspecialchars($p['categorie_nom']) ?></span>
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($p['nom']) ?></h3>
                        <p class="product-desc"><?= htmlspecialchars(substr($p['description'], 0, 70)) ?>...</p>
                        <div class="product-footer">
                            <span class="price"><?= number_format($p['prix'], 2) ?> DA</span>
                            <a href="produit.php?id=<?= $p['id'] ?>" class="btn-detail">View</a>
                            <a href="cart/ajouter_panier.php?id=<?= $p['id'] ?>" class="btn-cart">
                                <i class="fas fa-cart-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
const cards       = [...document.querySelectorAll('.product-card')];
const grid        = document.getElementById('products-grid');
const searchInput = document.getElementById('search-input');
const sortSelect  = document.getElementById('sort-select');
const priceRange  = document.getElementById('price-range');
const priceLabel  = document.getElementById('price-label');
const catChecks   = [...document.querySelectorAll('.cat-check')];
const countEl     = document.querySelector('.results-count');

function getSelectedCats() {
    return catChecks.filter(c => c.checked).map(c => c.value);
}

function applyFilters() {
    const q       = searchInput.value.toLowerCase().trim();
    const maxP    = parseFloat(priceRange.value);
    const cats    = getSelectedCats();
    const sort    = sortSelect.value;

    let visible = cards.filter(card => {
        const name  = card.dataset.name;
        const price = parseFloat(card.dataset.price);
        const cat   = card.dataset.cat;

        const matchQ   = q === '' || name.includes(q);
        const matchP   = price <= maxP;
        const matchCat = cats.length === 0 || cats.includes(cat);

        return matchQ && matchP && matchCat;
    });

    // Tri
    visible.sort((a, b) => {
        if (sort === 'price_asc')  return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
        if (sort === 'price_desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
        return 0;
    });

    // Rebuild grid
    grid.innerHTML = '';
    if (visible.length === 0) {
        grid.innerHTML = `<div class="empty-state">
            <i class="fas fa-box-open"></i>
            No products found matching your criteria
        </div>`;
    } else {
        visible.forEach(c => grid.appendChild(c));
    }

    // Update count
    countEl.innerHTML = `<strong>${visible.length}</strong> product${visible.length !== 1 ? 's' : ''} found`;
}

// Price slider update
priceRange.addEventListener('input', () => {
    priceLabel.textContent = '$' + priceRange.value;
    applyFilters();
});

// Update slider gradient
function updateSlider() {
    const val = (priceRange.value - priceRange.min) / (priceRange.max - priceRange.min) * 100;
    priceRange.style.background = `linear-gradient(to right, #0bbcd4 ${val}%, #e9ecef ${val}%)`;
}
priceRange.addEventListener('input', updateSlider);
updateSlider();

// Search (debounce)
let timer;
searchInput.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 250);
});

// Sort
sortSelect.addEventListener('change', applyFilters);

// Categories
catChecks.forEach(c => c.addEventListener('change', applyFilters));

// Init
applyFilters();
</script>

</body>
</html>