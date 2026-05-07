<?php
session_start();
require_once 'config/config.php';
require_once 'config/produits_query.php';
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
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- ══ HERO ══ -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">Gear up for your<br><span>best workout yet</span></h1>
        <p class="hero-sub">Premium fitness apparel, accessories, and nutrition to help you reach your goals</p>
        <div class="hero-btns">
            <a href="boutique.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Shop now &rarr;</a>
            <a href="#categories" class="btn-secondary">Browse categories</a>
        </div>
    </div>
</section>

<!-- ══ CATÉGORIES — slugs identiques à la BDD ══ -->
<section class="categories" id="categories">
    <div class="container">
        <h2 class="section-title">Nos Catégories</h2>
        <div class="cat-grid">

            <a href="boutique.php?categorie=vetement-homme" class="cat-card">
                <img src="assets/images/vetement_homme.jpg" alt="Vêtement Homme"
                     onerror="this.src='https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400'">
                <div class="cat-label"><i class="fas fa-mars"></i> Vêtement Homme</div>
            </a>

            <a href="boutique.php?categorie=vetement-femme" class="cat-card">
                <img src="assets/images/vetement_femme.jpg" alt="Vêtement Femme"
                     onerror="this.src='https://images.unsplash.com/photo-1518611012118-696072aa579a?w=400'">
                <div class="cat-label"><i class="fas fa-venus"></i> Vêtement Femme</div>
            </a>

            <a href="boutique.php?categorie=nutrition" class="cat-card">
                <img src="assets/images/nutrition.jpg" alt="Nutrition"
                     onerror="this.src='https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=400'">
                <div class="cat-label"><i class="fas fa-blender"></i> Nutrition</div>
            </a>

            <a href="boutique.php?categorie=materiel" class="cat-card">
                <img src="assets/images/materiel.jpg" alt="Matériel"
                     onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400'">
                <div class="cat-label"><i class="fas fa-dumbbell"></i> Matériel</div>
            </a>

        </div>
    </div>
</section>

<!-- ══ PRODUITS VEDETTES ══ -->
<section class="featured-products">
    <div class="container">
        <h2 class="section-title">Produits Populaires</h2>
        <div class="products-grid">
            <?php
            $produits = getProduits($pdo, '', '', 8);
            foreach ($produits as $p):
            ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <img src="<?= htmlspecialchars($p['image']) ?>"
                         alt="<?= htmlspecialchars($p['nom']) ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400'">
                    <span class="product-badge"><?= htmlspecialchars($p['categorie']) ?></span>
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

<!-- ══ BANNIÈRE PROMO ══ -->
<section class="promo-banner">
    <div class="promo-content">
        <h2>Livraison gratuite dès 5 000 DA 🚀</h2>
        <p>Sur toute la wilaya d'Alger — commandez maintenant !</p>
        <a href="boutique.php" class="btn-primary">Profiter de l'offre</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
