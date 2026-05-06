<?php
session_start();
require_once 'config/config.php';
require_once 'config/produits_query.php';

// Récupérer l'ID depuis l'URL ex: produit.php?id=3
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Récupérer le produit depuis la BDD
$produit = getProduitById($pdo, $id);
if (!$produit) {
    header('Location: index.php');
    exit;
}

// ✅ FIX : getProduits() n'existe pas → utilise getProduitsByCategorie()
$similaires = getProduitsByCategorie($pdo, $produit['categorie_slug']);
$similaires = array_filter($similaires, fn($p) => $p['id'] != $id);
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
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="index.php">Accueil</a>
    <i class="fas fa-chevron-right" style="font-size:.7rem;"></i>
    <a href="index.php">Boutique</a>
    <i class="fas fa-chevron-right" style="font-size:.7rem;"></i>
    <!-- ✅ FIX : 'categorie' → 'categorie_slug' et 'categorie_nom' -->
    <a href="index.php?categorie=<?= urlencode($produit['categorie_slug']) ?>">
        <?= htmlspecialchars($produit['categorie_nom']) ?>
    </a>
    <i class="fas fa-chevron-right" style="font-size:.7rem;"></i>
    <span><?= htmlspecialchars($produit['nom']) ?></span>
</div>

<!-- DÉTAIL PRODUIT -->
<section class="product-detail-wrap">
    <div class="container">
        <div class="product-detail-grid">

            <!-- IMAGE PRINCIPALE -->
            <div class="product-detail-img">
                <img id="main-img"
                     src="<?= htmlspecialchars($produit['image']) ?>"
                     alt="<?= htmlspecialchars($produit['nom']) ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600'">
            </div>

            <!-- INFOS PRODUIT -->
            <div class="product-detail-info">

                <!-- ✅ FIX : 'categorie' → 'categorie_nom' -->
                <span class="product-badge" style="display:inline-block;margin-bottom:16px;font-size:.8rem;">
                    <i class="fas fa-tag"></i> <?= htmlspecialchars($produit['categorie_nom']) ?>
                </span>

                <h1><?= htmlspecialchars($produit['nom']) ?></h1>
                <div class="price-big"><?= number_format($produit['prix'], 2) ?> DA</div>
                <p><?= nl2br(htmlspecialchars($produit['description'])) ?></p>

                <!-- QUANTITÉ -->
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
                    <span style="font-weight:600;font-size:.95rem;">Quantité :</span>
                    <div style="display:flex;align-items:center;gap:12px;background:var(--dark2);border-radius:10px;padding:8px 16px;border:1px solid rgba(255,255,255,0.1);">
                        <button class="qty-btn" onclick="changeQty(-1)">−</button>
                        <span class="qty-display" id="qty-val">1</span>
                        <button class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <!-- BOUTONS -->
                <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:32px;">
                    <a href="cart/ajouter_panier.php?id=<?= $produit['id'] ?>&qty=1"
                       class="btn-primary" id="add-to-cart">
                        <i class="fas fa-cart-plus"></i> Ajouter au panier
                    </a>
                    <a href="index.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>

                <!-- GARANTIES -->
                <div style="display:flex;flex-direction:column;gap:12px;padding:20px;background:var(--dark2);border-radius:var(--radius);border:1px solid rgba(255,255,255,0.07);">
                    <div style="display:flex;gap:12px;align-items:center;font-size:.9rem;color:var(--gray);">
                        <i class="fas fa-truck" style="color:var(--teal);width:18px;"></i>
                        Livraison gratuite dès 5 000 DA
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;font-size:.9rem;color:var(--gray);">
                        <i class="fas fa-shield-alt" style="color:var(--teal);width:18px;"></i>
                        Produits authentiques garantis
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;font-size:.9rem;color:var(--gray);">
                        <i class="fas fa-undo" style="color:var(--teal);width:18px;"></i>
                        Retour accepté sous 7 jours
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;font-size:.9rem;color:var(--gray);">
                        <i class="fas fa-lock" style="color:var(--teal);width:18px;"></i>
                        Paiement sécurisé à la livraison
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- PRODUITS SIMILAIRES -->
<?php if (!empty($similaires)): ?>
<section class="featured-products" style="padding-top:0;">
    <div class="container">
        <h2 class="section-title">Produits similaires</h2>
        <div class="products-grid">
            <?php foreach (array_slice($similaires, 0, 4) as $p): ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <img src="<?= htmlspecialchars($p['image']) ?>"
                         alt="<?= htmlspecialchars($p['nom']) ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=400'">
                    <!-- ✅ FIX : 'categorie' → 'categorie_nom' -->
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
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>