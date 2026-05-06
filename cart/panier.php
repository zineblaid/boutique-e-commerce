<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$panier = $_SESSION['panier'] ?? [];
$flash  = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

// Calcul totaux
$sous_total = 0;
foreach ($panier as $item) {
    $sous_total += $item['prix'] * $item['quantite'];
}
$livraison  = ($sous_total >= 5000 && $sous_total > 0) ? 0 : ($sous_total > 0 ? 500 : 0);
$total      = $sous_total + $livraison;
$nb_articles = array_sum(array_column($panier, 'quantite'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier — FitZone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { padding-top: 64px; background: #f8f9fa; }

        /* ── NAVBAR (identique aux screenshots) ── */
        .top-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: #fff; border-bottom: 1px solid #e9ecef;
            height: 64px; display: flex; align-items: center;
            justify-content: space-between; padding: 0 32px;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #1a1a2e; font-weight: 700; font-size: 1.2rem; }
        .nav-logo  { background: #00BCD4; color: #fff; font-weight: 800; font-size: .85rem; padding: 6px 9px; border-radius: 8px; }
        .nav-links { display: flex; gap: 28px; list-style: none; }
        .nav-links a { text-decoration: none; color: #555e6d; font-size: .95rem; }
        .nav-links a:hover { color: #00BCD4; }
        .nav-actions { display: flex; align-items: center; gap: 18px; }
        .nav-actions a { text-decoration: none; font-size: .95rem; color: #555e6d; }
        .nav-actions a.active { color: #00BCD4; font-weight: 700; }
        .btn-signup { background: #00BCD4; color: #fff !important; padding: 9px 22px; border-radius: 8px; font-weight: 600; transition: background .2s; }
        .btn-signup:hover { background: #0097A7; }
        .cart-icon { position: relative; font-size: 1.2rem; color: #555e6d; }
        .cart-icon .badge { position: absolute; top: -7px; right: -8px; background: #00BCD4; color: #fff; font-size: .58rem; font-weight: 800; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* ── PAGE ── */
        .page-wrap { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }
        .page-title { font-size: 1.9rem; font-weight: 700; margin-bottom: 4px; color: #1a1a2e; }
        .page-sub   { color: #666; margin-bottom: 32px; font-size: .95rem; }

        /* ── EMPTY STATE ── */
        .empty-cart { text-align: center; padding: 80px 20px; }
        .empty-cart .icon-wrap {
            width: 90px; height: 90px; border-radius: 16px;
            border: 2px solid #d0d5db; display: flex;
            align-items: center; justify-content: center;
            margin: 0 auto 24px; color: #9ca3af; font-size: 2.2rem;
        }
        .empty-cart h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 8px; color: #1a1a2e; }
        .empty-cart p  { color: #6b7280; margin-bottom: 28px; }
        .btn-browse {
            display: inline-flex; align-items: center; gap: 8px;
            background: #00BCD4; color: #fff;
            padding: 13px 32px; border-radius: 8px;
            font-weight: 600; font-size: .95rem;
            transition: background .2s; text-decoration: none;
        }
        .btn-browse:hover { background: #0097A7; }

        /* ── LAYOUT PANIER ── */
        .cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start; }

        /* ── ITEMS ── */
        .cart-items-wrap { display: flex; flex-direction: column; gap: 14px; }
        .cart-item {
            background: #fff; border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 18px 20px; display: flex;
            align-items: center; gap: 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .cart-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .cart-item-info { flex: 1; }
        .cart-item-info h4 { font-weight: 700; color: #1a1a2e; margin-bottom: 4px; font-size: .95rem; }
        .cart-item-info .item-price { color: #00BCD4; font-weight: 700; font-size: .95rem; }
        .cart-item-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        .qty-ctrl { display: flex; align-items: center; gap: 8px; background: #f4f6f8; border-radius: 8px; padding: 6px 10px; }
        .qty-ctrl button {
            background: #fff; border: 1px solid #e2e6ea; color: #1a1a2e;
            width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
            font-size: .95rem; display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .qty-ctrl button:hover { background: #00BCD4; color: #fff; border-color: #00BCD4; }
        .qty-ctrl span { font-weight: 700; min-width: 22px; text-align: center; font-size: .95rem; }

        .btn-del {
            background: rgba(229,57,53,.08); border: none;
            color: #e53935; padding: 7px 13px; border-radius: 8px;
            cursor: pointer; font-size: .82rem; font-weight: 600;
            transition: background .2s; display: flex; align-items: center; gap: 5px;
        }
        .btn-del:hover { background: rgba(229,57,53,.17); }

        /* ── SUMMARY ── */
        .cart-summary {
            background: #fff; border-radius: 12px;
            border: 1px solid #e5e7eb; padding: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
            position: sticky; top: 84px;
        }
        .cart-summary h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; color: #1a1a2e; }
        .summary-row {
            display: flex; justify-content: space-between;
            padding: 10px 0; font-size: .9rem; color: #6b7280;
            border-bottom: 1px solid #f0f0f0;
        }
        .summary-row.total-row { color: #1a1a2e; font-weight: 700; font-size: 1rem; border: none; margin-top: 4px; }
        .summary-row .free { color: #43a047; font-weight: 600; }
        .btn-checkout {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 13px; margin-top: 18px;
            background: #00BCD4; color: #fff; border: none;
            border-radius: 8px; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .2s; text-decoration: none;
        }
        .btn-checkout:hover { background: #0097A7; }
        .btn-continue {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%; padding: 11px; margin-top: 10px;
            background: transparent; color: #6b7280;
            border: 1.5px solid #e2e6ea; border-radius: 8px;
            font-size: .88rem; font-weight: 600; cursor: pointer;
            text-decoration: none; transition: all .2s;
        }
        .btn-continue:hover { border-color: #00BCD4; color: #00BCD4; }

        .promo-note { display: flex; gap: 8px; align-items: center; font-size: .82rem; color: #43a047; margin-top: 14px; }

        /* ── FLASH ── */
        .flash { background: rgba(67,160,71,.1); color: #2e7d32; border: 1px solid rgba(67,160,71,.25); border-radius: 8px; padding: 11px 16px; margin-bottom: 20px; font-weight: 600; font-size: .9rem; display: flex; align-items: center; gap: 8px; }

        @media(max-width:800px){
            .cart-layout { grid-template-columns: 1fr; }
            .cart-summary { position: static; }
            .nav-links, .nav-actions .btn-signup { display: none; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="top-nav">
    <a href="../index.php" class="nav-brand">
        <div class="nav-logo">FZ</div> FitZone
    </a>
    <ul class="nav-links">
        <li><a href="../index.php">Home</a></li>
        <li><a href="../boutique.php">Shop</a></li>
    </ul>
    <div class="nav-actions">
        <a href="panier.php" class="cart-icon" title="Panier">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($nb_articles > 0): ?>
                <span class="badge"><?= $nb_articles ?></span>
            <?php endif; ?>
        </a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="../auth/logout.php"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_nom'] ?? $_SESSION['name'] ?? '') ?></a>
        <?php else: ?>
            <a href="../auth/login.php">Login</a>
            <a href="../auth/register.php" class="btn-signup">Sign up</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page-wrap">

    <?php if ($flash): ?>
        <div class="flash"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if (empty($panier)): ?>
    <!-- ══ PANIER VIDE ══ -->
    <div class="empty-cart">
        <div class="icon-wrap">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <h2>Your cart is empty</h2>
        <p>Start shopping to add items to your cart</p>
        <a href="../boutique.php" class="btn-browse">Browse products</a>
    </div>

    <?php else: ?>
    <!-- ══ PANIER REMPLI ══ -->
    <h1 class="page-title">Your Cart</h1>
    <p class="page-sub"><?= $nb_articles ?> article<?= $nb_articles > 1 ? 's' : '' ?> dans votre panier</p>

    <div class="cart-layout">

        <!-- ARTICLES -->
        <div class="cart-items-wrap">
            <?php foreach ($panier as $id_p => $item): ?>
            <div class="cart-item">
                <img src="<?= htmlspecialchars($item['image']) ?>"
                     alt="<?= htmlspecialchars($item['nom']) ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=200'">
                <div class="cart-item-info">
                    <h4><?= htmlspecialchars($item['nom']) ?></h4>
                    <div class="item-price"><?= number_format($item['prix'], 2) ?> DA / unité</div>
                </div>
                <div class="cart-item-actions">
                    <!-- Quantité -->
                    <div class="qty-ctrl">
                        <a href="supprimer_panier.php?id=<?= $id_p ?>&action=decrease">
                            <button type="button">−</button>
                        </a>
                        <span><?= (int)$item['quantite'] ?></span>
                        <a href="supprimer_panier.php?id=<?= $id_p ?>&action=increase">
                            <button type="button">+</button>
                        </a>
                    </div>
                    <!-- Sous-total -->
                    <span style="font-weight:700;color:#1a1a2e;min-width:90px;text-align:right;font-size:.92rem;">
                        <?= number_format($item['prix'] * $item['quantite'], 2) ?> DA
                    </span>
                    <!-- Supprimer -->
                    <a href="supprimer_panier.php?id=<?= $id_p ?>&action=remove">
                        <button class="btn-del" type="button"><i class="fas fa-trash-alt"></i> Retirer</button>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- RÉSUMÉ -->
        <aside class="cart-summary">
            <h3>Order Summary</h3>

            <div class="summary-row">
                <span>Sous-total (<?= $nb_articles ?> article<?= $nb_articles > 1 ? 's' : '' ?>)</span>
                <span><?= number_format($sous_total, 2) ?> DA</span>
            </div>
            <div class="summary-row">
                <span>Livraison</span>
                <?php if ($livraison == 0): ?>
                    <span class="free"><i class="fas fa-check"></i> Gratuite</span>
                <?php else: ?>
                    <span><?= number_format($livraison, 2) ?> DA</span>
                <?php endif; ?>
            </div>
            <div class="summary-row total-row">
                <span>Total</span>
                <span><?= number_format($total, 2) ?> DA</span>
            </div>

            <?php if ($livraison > 0): ?>
                <div class="promo-note">
                    <i class="fas fa-truck"></i>
                    Plus que <?= number_format(5000 - $sous_total, 0, ',', ' ') ?> DA pour la livraison gratuite !
                </div>
            <?php endif; ?>

            <a href="#" class="btn-checkout">
                <i class="fas fa-lock"></i> Passer la commande
            </a>
            <a href="../boutique.php" class="btn-continue">
                <i class="fas fa-arrow-left"></i> Continuer mes achats
            </a>
        </aside>

    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>
