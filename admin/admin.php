<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/produits_query.php';

// Protection admin
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

$produits   = getAllProduits($pdo);
$categories = getAllCategories($pdo);
$flash      = $_SESSION['flash_success'] ?? null;
$flash_err  = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — FitZone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f4f6f8; padding-top: 64px; }

        /* ── TOPBAR ── */
        .admin-topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: #1a1a2e; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px;
        }
        .admin-topbar .brand { color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
        .admin-topbar .brand span { background: #00BCD4; padding: 4px 8px; border-radius: 6px; font-size: .8rem; }
        .admin-topbar .topbar-links { display: flex; gap: 20px; align-items: center; }
        .admin-topbar .topbar-links a { color: rgba(255,255,255,.7); font-size: .9rem; text-decoration: none; transition: color .2s; }
        .admin-topbar .topbar-links a:hover { color: #00BCD4; }

        /* ── LAYOUT ── */
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 36px 24px; }

        /* ── STATS ── */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 36px; }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 22px 24px;
            border: 1px solid #e2e6ea; box-shadow: 0 1px 4px rgba(0,0,0,.06);
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .stat-icon.teal    { background: rgba(0,188,212,.12); color: #00BCD4; }
        .stat-icon.green   { background: rgba(67,160,71,.12);  color: #43a047; }
        .stat-icon.orange  { background: rgba(251,140,0,.12);  color: #fb8c00; }
        .stat-icon.purple  { background: rgba(103,58,183,.12); color: #7c3aed; }
        .stat-info h4 { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
        .stat-info p  { font-size: .82rem; color: #6b7280; }

        /* ── SECTION HEADER ── */
        .section-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px;
        }
        .section-head h2 { font-family: 'Bebas Neue', cursive; font-size: 1.7rem; letter-spacing: 1px; color: #1a1a2e; }
        .btn-add {
            display: inline-flex; align-items: center; gap: 8px;
            background: #00BCD4; color: #fff; padding: 10px 22px;
            border-radius: 8px; font-weight: 600; font-size: .9rem;
            text-decoration: none; transition: background .2s;
        }
        .btn-add:hover { background: #0097A7; }

        /* ── TABLE ── */
        .table-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e6ea; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f4f6f8; padding: 12px 16px; font-size: .78rem; text-transform: uppercase; letter-spacing: .8px; color: #6b7280; text-align: left; border-bottom: 1px solid #e2e6ea; }
        tbody td { padding: 14px 16px; border-bottom: 1px solid #f0f0f0; font-size: .88rem; color: #1a1a2e; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f9fafb; }
        tbody td img { width: 46px; height: 46px; object-fit: cover; border-radius: 8px; }
        .badge-cat { background: rgba(0,188,212,.1); color: #00838f; padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600; white-space: nowrap; }

        /* ── ACTIONS ── */
        .action-btns { display: flex; gap: 8px; }
        .btn-edit {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(251,140,0,.1); color: #fb8c00;
            padding: 6px 12px; border-radius: 6px;
            font-size: .8rem; font-weight: 600; text-decoration: none;
            transition: background .2s;
        }
        .btn-edit:hover { background: rgba(251,140,0,.2); }
        .btn-del-admin {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(229,57,53,.08); color: #e53935;
            padding: 6px 12px; border-radius: 6px;
            font-size: .8rem; font-weight: 600;
            border: none; cursor: pointer; transition: background .2s;
        }
        .btn-del-admin:hover { background: rgba(229,57,53,.18); }

        @media(max-width:900px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:600px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- TOPBAR ADMIN -->
<div class="admin-topbar">
    <div class="brand">
        <span>ADMIN</span> FitZone Dashboard
    </div>
    <div class="topbar-links">
        <a href="../index.php"><i class="fas fa-store"></i> Voir la boutique</a>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="admin-container">

    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom:20px;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash_err): ?>
        <div class="alert alert-error" style="margin-bottom:20px;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($flash_err) ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-box"></i></div>
            <div class="stat-info">
                <h4><?= count($produits) ?></h4>
                <p>Produits total</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-tags"></i></div>
            <div class="stat-info">
                <h4><?= count($categories) ?></h4>
                <p>Catégories</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-boxes"></i></div>
            <div class="stat-info">
                <h4><?= array_sum(array_column($produits, 'stock')) ?></h4>
                <p>Articles en stock</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h4><?= number_format(array_sum(array_map(fn($p) => $p['prix'], $produits)) / max(1,count($produits)), 0, ',', ' ') ?> DA</h4>
                <p>Prix moyen</p>
            </div>
        </div>
    </div>

    <!-- TABLE PRODUITS -->
    <div class="section-head">
        <h2>Gestion des Produits</h2>
        <a href="ajouter_produit.php" class="btn-add"><i class="fas fa-plus"></i> Ajouter un produit</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produits)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">Aucun produit trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($produits as $p): ?>
                    <tr>
                        <td>
                            <img src="../<?= htmlspecialchars($p['image']) ?>"
                                 alt="<?= htmlspecialchars($p['nom']) ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=100'">
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($p['nom']) ?></td>
                        <td><span class="badge-cat"><?= htmlspecialchars($p['categorie_nom']) ?></span></td>
                        <td style="font-weight:700;color:#00BCD4;"><?= number_format($p['prix'], 2) ?> DA</td>
                        <td>
                            <span style="color:<?= $p['stock'] > 10 ? '#43a047' : ($p['stock'] > 0 ? '#fb8c00' : '#e53935') ?>;font-weight:600;">
                                <?= (int)$p['stock'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="ajouter_produit.php?edit=<?= $p['id'] ?>" class="btn-edit">
                                    <i class="fas fa-pencil-alt"></i> Modifier
                                </a>
                                <form method="POST" action="supprimer_produit.php" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($p['nom'])) ?> » ?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-del-admin">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
