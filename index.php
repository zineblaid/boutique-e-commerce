<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/produits_query.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitZone - Boutique Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── OVERRIDE : arrière-plan blanc ── */
        body {
            background: #ffffff !important;
            color: #1a1a1a !important;
        }
        .categories,
        .featured-products {
            background: #ffffff;
        }
        .section-title {
            color: #1a1a1a;
        }
        .product-card {
            background: #f5f5f5 !important;
            border: 1px solid #e0e0e0 !important;
            color: #1a1a1a;
        }
        .product-card:hover {
            box-shadow: 0 14px 38px rgba(0,0,0,0.12) !important;
            border-color: rgba(0,188,212,0.4) !important;
        }
        .product-info h3 { color: #1a1a1a; }
        .product-desc    { color: #666 !important; }
        .cat-card img    { filter: brightness(0.72); }
        .cat-card:hover img { filter: brightness(0.88); }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">Gear up for your<br><span>best workout yet</span></h1>
        <p class="hero-sub">Premium fitness apparel, accessories, and nutrition to help you reach your goals</p>
        <div class="hero-btns">
            <a href="#categories" class="btn-primary"><i class="fas fa-shopping-bag"></i> Shop now &rarr;</a>
            <a href="#categories" class="btn-secondary">Browse categories</a>
        </div>
    </div>
</section>

<!-- CATÉGORIES : 3 seulement -->
<section class="categories" id="categories">
    <div class="container">
        <h2 class="section-title">Nos Catégories</h2>
        <div class="cat-grid" style="grid-template-columns: repeat(3, 1fr);">

            <a href="boutique.php?categorie=vetement" class="cat-card">
                <img src="https://images.unsplash.com/photo-1556906781-9a412961a28d?w=400" alt="Vêtements">
                <div class="cat-label"><i class="fas fa-tshirt"></i> Vêtements</div>
            </a>

            <a href="boutique.php?categorie=materiel" class="cat-card">
                <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400" alt="Matériel">
                <div class="cat-label"><i class="fas fa-dumbbell"></i> Matériel</div>
            </a>

            <a href="boutique.php?categorie=nutrition" class="cat-card">
                <img src="https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=400" alt="Nutrition">
                <div class="cat-label"><i class="fas fa-blender"></i> Nutrition</div>
            </a>

        </div>
    </div>
</section>

<!-- PRODUITS POPULAIRES -->
<section class="featured-products">
    <div class="container">
        <h2 class="section-title">Produits Populaires</h2>
        <div class="products-grid">
            <?php
            $produits = filterProduits($pdo);
            $produits = array_slice($produits, 0, 8);
            foreach ($produits as $p):
            ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <img src="<?= htmlspecialchars($p['image']) ?>"
                         alt="<?= htmlspecialchars($p['nom']) ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400'">
                    <span class="product-badge"><?= htmlspecialchars($p['categorie_nom']) ?></span>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($p['nom']) ?></h3>
                    <p class="product-desc"><?= htmlspecialchars(substr($p['description'], 0, 65)) ?>...</p>
                    <div class="product-footer">
                        <span class="price"><?= number_format($p['prix'], 2) ?> DA</span>
                        <a href="produit.php?id=<?= $p['id'] ?>" class="btn-detail">Voir</a>
                        <a href="cart/ajouter_panier.php?id=<?= $p['id'] ?>" class="btn-cart">
                            <i class="fas fa-cart-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="boutique.php" class="btn-primary">Voir tout &rarr;</a>
        </div>
    </div>
</section>

<!-- BANNIÈRE PROMO -->
<section class="promo-banner">
    <div class="promo-content">
        <h2>Livraison gratuite dès 5 000 DA 🚀</h2>
        <p>Sur toute la wilaya d'Alger — commandez maintenant !</p>
        <a href="#categories" class="btn-primary">Profiter de l'offre</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>