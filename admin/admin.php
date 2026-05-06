<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/produits_query.php';

// Protection admin
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$produits   = getAllProduits($pdo);
$categories = getAllCategories($pdo);
$flash      = $_SESSION['flash_success'] ?? null;
$flash_err  = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Statistiques
$total_produits = count($produits);
$total_cats     = count($categories);
$total_stock    = array_sum(array_column($produits, 'stock'));
$stock_faible   = count(array_filter($produits, fn($p) => $p['stock'] <= 5));
$prix_moyen     = $total_produits > 0
    ? array_sum(array_column($produits, 'prix')) / $total_produits
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — FitZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --sidebar-w: 240px;
            --topbar-h: 60px;
            --bg: #f0f2f7;
            --white: #ffffff;
            --primary: #00BCD4;
            --primary-dark: #0097A7;
            --sidebar-bg: #1a1d2e;
            --sidebar-text: rgba(255,255,255,.6);
            --sidebar-active: rgba(0,188,212,.15);
            --text: #1a1d2e;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --purple: #8b5cf6;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.05);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ══════════════════════════════
           SIDEBAR
        ══════════════════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform .3s;
        }

        .sidebar-logo {
            padding: 20px 20px 16px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; color: #fff;
            box-shadow: 0 4px 12px rgba(0,188,212,.35);
            flex-shrink: 0;
        }
        .logo-text {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.3rem; letter-spacing: 2px; color: #fff;
            line-height: 1;
        }
        .logo-sub {
            font-size: .68rem; color: rgba(255,255,255,.35);
            text-transform: uppercase; letter-spacing: 1px;
        }

        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }

        .nav-label {
            font-size: .68rem; font-weight: 600;
            color: rgba(255,255,255,.25); text-transform: uppercase;
            letter-spacing: 1.2px; padding: 12px 8px 6px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            color: var(--sidebar-text); font-size: .875rem; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: all .2s; margin-bottom: 2px;
            border: none; background: none; width: 100%; text-align: left;
        }
        .nav-item i { width: 18px; text-align: center; font-size: .85rem; }
        .nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nav-item.active {
            background: var(--sidebar-active);
            color: var(--primary);
        }
        .nav-item .badge {
            margin-left: auto; background: var(--danger);
            color: #fff; font-size: .68rem; font-weight: 700;
            padding: 2px 7px; border-radius: 20px;
        }

        .sidebar-footer {
            padding: 14px 12px;
            border-top: 1px solid rgba(255,255,255,.07);
        }

        /* ══════════════════════════════
           MAIN CONTENT
        ══════════════════════════════ */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            height: var(--topbar-h);
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 28px;
            gap: 16px;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-breadcrumb {
            font-size: .8rem; color: var(--text-muted);
            display: flex; align-items: center; gap: 6px;
        }
        .topbar-breadcrumb span { color: var(--text); font-weight: 600; }
        .topbar-search {
            flex: 1; max-width: 320px; margin-left: 24px;
            display: flex; align-items: center;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 14px; gap: 8px;
        }
        .topbar-search i { color: var(--text-muted); font-size: .85rem; }
        .topbar-search input {
            border: none; background: none; outline: none;
            font-family: 'DM Sans', sans-serif; font-size: .875rem;
            color: var(--text); width: 100%;
        }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 16px; }
        .topbar-icon-btn {
            width: 36px; height: 36px; border-radius: 8px;
            background: var(--bg); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); cursor: pointer; font-size: .9rem;
            text-decoration: none; transition: all .2s; position: relative;
        }
        .topbar-icon-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px; background: var(--danger);
            border-radius: 50%; border: 1.5px solid var(--white);
        }
        .admin-avatar {
            display: flex; align-items: center; gap: 10px;
            padding: 5px 12px 5px 5px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 30px; cursor: pointer;
        }
        .avatar-img {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; color: #fff;
        }
        .admin-avatar span { font-size: .82rem; font-weight: 600; }

        /* PAGE CONTENT */
        .page-content { padding: 28px; flex: 1; }

        .page-header { margin-bottom: 24px; }
        .page-header h1 {
            font-family: 'Bebas Neue', cursive;
            font-size: 2rem; letter-spacing: 1.5px; color: var(--text);
        }
        .page-header p { color: var(--text-muted); font-size: .875rem; margin-top: 2px; }

        /* FLASH MESSAGES */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            display: flex; align-items: center; gap: 10px;
            font-size: .875rem; font-weight: 500; margin-bottom: 20px;
            animation: fadeIn .3s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .alert-success { background: rgba(16,185,129,.1); color: #065f46; border: 1px solid rgba(16,185,129,.25); }
        .alert-error   { background: rgba(239,68,68,.1);  color: #991b1b; border: 1px solid rgba(239,68,68,.25); }

        /* ── STAT CARDS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin-bottom: 28px;
        }
        .stat-card {
            background: var(--white); border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 20px 22px;
            box-shadow: var(--shadow);
            display: flex; align-items: flex-start; justify-content: space-between;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,.1); }
        .stat-label { font-size: .78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .7px; margin-bottom: 8px; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: var(--text); line-height: 1; }
        .stat-sub { font-size: .75rem; color: var(--text-muted); margin-top: 5px; }
        .stat-trend { font-size: .75rem; font-weight: 600; }
        .stat-trend.up   { color: var(--success); }
        .stat-trend.down { color: var(--danger); }
        .stat-icon-wrap {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .ic-teal   { background: rgba(0,188,212,.1);   color: var(--primary); }
        .ic-green  { background: rgba(16,185,129,.1);  color: var(--success); }
        .ic-orange { background: rgba(245,158,11,.1);  color: var(--warning); }
        .ic-red    { background: rgba(239,68,68,.1);   color: var(--danger); }
        .ic-purple { background: rgba(139,92,246,.1);  color: var(--purple); }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px; gap: 12px; flex-wrap: wrap;
        }
        .toolbar-left { display: flex; align-items: center; gap: 10px; }
        .toolbar h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.4rem; letter-spacing: 1px;
        }
        .filter-select {
            padding: 8px 12px; border: 1px solid var(--border);
            border-radius: 8px; background: var(--white);
            font-family: 'DM Sans', sans-serif; font-size: .82rem;
            color: var(--text); outline: none; cursor: pointer;
        }
        .btn-add {
            display: inline-flex; align-items: center; gap: 7px;
            background: var(--primary); color: #fff;
            padding: 9px 20px; border-radius: 8px;
            font-weight: 600; font-size: .875rem; text-decoration: none;
            transition: background .2s, transform .1s;
            box-shadow: 0 2px 8px rgba(0,188,212,.3);
        }
        .btn-add:hover { background: var(--primary-dark); transform: translateY(-1px); }

        /* ── TABLE ── */
        .table-card {
            background: var(--white); border-radius: var(--radius);
            border: 1px solid var(--border); overflow: hidden;
            box-shadow: var(--shadow);
        }
        .table-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .table-header-title { font-weight: 600; font-size: .9rem; }
        .table-count {
            font-size: .78rem; background: var(--bg);
            color: var(--text-muted); padding: 3px 10px;
            border-radius: 20px; border: 1px solid var(--border);
        }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 11px 16px;
            font-size: .72rem; text-transform: uppercase;
            letter-spacing: .9px; color: var(--text-muted);
            font-weight: 600; text-align: left;
            background: #fafafa;
            border-bottom: 1px solid var(--border);
        }
        tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid #f5f5f5;
            font-size: .875rem; color: var(--text);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background .15s; }
        tbody tr:hover td { background: #fafbff; }

        /* Product image in table */
        .prod-img {
            width: 44px; height: 44px; object-fit: cover;
            border-radius: 8px; border: 1px solid var(--border);
            display: block;
        }
        .prod-name { font-weight: 600; color: var(--text); }
        .prod-desc { font-size: .78rem; color: var(--text-muted); margin-top: 2px; }

        .badge-cat {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(0,188,212,.08); color: #006d7a;
            padding: 3px 10px; border-radius: 20px;
            font-size: .75rem; font-weight: 600;
        }
        .stock-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-weight: 600; font-size: .82rem;
        }
        .stock-dot {
            width: 7px; height: 7px; border-radius: 50%;
        }
        .price-cell { font-weight: 700; color: var(--primary); }

        /* Action buttons */
        .action-btns { display: flex; gap: 6px; }
        .btn-icon {
            width: 32px; height: 32px; border-radius: 7px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .8rem; text-decoration: none;
            border: 1px solid transparent; cursor: pointer;
            transition: all .15s;
        }
        .btn-icon-edit  { background: rgba(245,158,11,.1); color: var(--warning); border-color: rgba(245,158,11,.2); }
        .btn-icon-edit:hover  { background: var(--warning); color: #fff; }
        .btn-icon-del   { background: rgba(239,68,68,.08); color: var(--danger); border-color: rgba(239,68,68,.15); }
        .btn-icon-del:hover   { background: var(--danger); color: #fff; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--text-muted);
        }
        .empty-state i { font-size: 2.5rem; opacity: .3; margin-bottom: 12px; }
        .empty-state p { font-size: .9rem; }

        /* Pagination stub */
        .table-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            font-size: .8rem; color: var(--text-muted);
        }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 520px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ═══════════════════════════ SIDEBAR ═══════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-dumbbell"></i></div>
        <div>
            <div class="logo-text">FitZone</div>
            <div class="logo-sub">Admin Panel</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="admin.php" class="nav-item active">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <div class="nav-label">Catalogue</div>
        <a href="ajouter_produit.php" class="nav-item">
            <i class="fas fa-plus-circle"></i> Ajouter un produit
        </a>
        <a href="admin.php" class="nav-item">
            <i class="fas fa-boxes"></i> Inventaire
            <?php if ($stock_faible > 0): ?>
                <span class="badge"><?= $stock_faible ?></span>
            <?php endif; ?>
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-tags"></i> Catégories
        </a>

        <div class="nav-label">Compte</div>
        <a href="../index.php" class="nav-item">
            <i class="fas fa-store"></i> Voir la boutique
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../Auth/logout.php" class="nav-item" style="color:#ef4444;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>

<!-- ═══════════════════════════ MAIN ═══════════════════════════ -->
<div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-breadcrumb">
            Dashboard <i class="fas fa-chevron-right" style="font-size:.6rem"></i>
            <span>Vue d'ensemble</span>
        </div>

        <div class="topbar-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Rechercher un produit..." id="searchInput">
        </div>

        <div class="topbar-right">
            <a href="#" class="topbar-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($stock_faible > 0): ?>
                    <span class="notif-dot"></span>
                <?php endif; ?>
            </a>
            <div class="admin-avatar">
                <div class="avatar-img">A</div>
                <span>Admin</span>
            </div>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="page-content">

        <div class="page-header">
            <h1>Dashboard Overview</h1>
            <p>Bienvenue dans votre espace d'administration FitZone.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <?php if ($flash_err): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($flash_err) ?></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Produits</div>
                    <div class="stat-value"><?= $total_produits ?></div>
                    <div class="stat-sub"><?= $total_cats ?> catégories</div>
                </div>
                <div class="stat-icon-wrap ic-teal"><i class="fas fa-box-open"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Articles en stock</div>
                    <div class="stat-value"><?= number_format($total_stock) ?></div>
                    <div class="stat-sub stat-trend up"><i class="fas fa-arrow-up"></i> Inventaire actif</div>
                </div>
                <div class="stat-icon-wrap ic-green"><i class="fas fa-cubes"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Prix moyen</div>
                    <div class="stat-value"><?= number_format($prix_moyen, 0, ',', ' ') ?></div>
                    <div class="stat-sub">DA par produit</div>
                </div>
                <div class="stat-icon-wrap ic-orange"><i class="fas fa-chart-line"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Stock faible</div>
                    <div class="stat-value"><?= $stock_faible ?></div>
                    <div class="stat-sub stat-trend <?= $stock_faible > 0 ? 'down' : 'up' ?>">
                        <?= $stock_faible > 0 ? '<i class="fas fa-exclamation-triangle"></i> À réapprovisionner' : '<i class="fas fa-check"></i> Tout va bien' ?>
                    </div>
                </div>
                <div class="stat-icon-wrap ic-red"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="toolbar">
            <div class="toolbar-left">
                <h2>Gestion des Produits</h2>
                <select class="filter-select" id="filterCat" onchange="filterTable()">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['nom']) ?>">
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="ajouter_produit.php" class="btn-add">
                <i class="fas fa-plus"></i> Ajouter un produit
            </a>
        </div>

        <div class="table-card">
            <div class="table-header">
                <span class="table-header-title">Liste des produits</span>
                <span class="table-count" id="prodCount"><?= $total_produits ?> produits</span>
            </div>

            <table id="produitsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th style="text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php if (empty($produits)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>Aucun produit trouvé.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produits as $p):
                        $imgSrc = $p['image'];
                        // Normalise le chemin : retire un éventuel préfixe "images/" ou "assets/images/"
                        // L'image est stockée en DB comme "images/xxx.jpg"
                        // Depuis admin/ on remonte d'un niveau → "../images/xxx.jpg"
                        $imgPath = '../' . ltrim($imgSrc, '/');
                        $stockColor = $p['stock'] > 10 ? '#10b981' : ($p['stock'] > 0 ? '#f59e0b' : '#ef4444');
                        $stockLabel = $p['stock'] > 10 ? 'En stock' : ($p['stock'] > 0 ? 'Faible' : 'Rupture');
                    ?>
                    <tr data-cat="<?= htmlspecialchars($p['categorie_nom']) ?>" data-nom="<?= htmlspecialchars(strtolower($p['nom'])) ?>">
                        <td>
                            <img class="prod-img"
                                 src="<?= htmlspecialchars($imgPath) ?>"
                                 alt="<?= htmlspecialchars($p['nom']) ?>"
                                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=100&q=80'">
                        </td>
                        <td>
                            <div class="prod-name"><?= htmlspecialchars($p['nom']) ?></div>
                            <div class="prod-desc"><?= htmlspecialchars(mb_substr($p['description'], 0, 50)) ?>…</div>
                        </td>
                        <td><span class="badge-cat"><?= htmlspecialchars($p['categorie_nom']) ?></span></td>
                        <td class="price-cell"><?= number_format($p['prix'], 2, ',', ' ') ?> DA</td>
                        <td>
                            <span class="stock-badge">
                                <span class="stock-dot" style="background:<?= $stockColor ?>"></span>
                                <span style="color:<?= $stockColor ?>"><?= (int)$p['stock'] ?></span>
                                <span style="color:var(--text-muted);font-size:.72rem">(<?= $stockLabel ?>)</span>
                            </span>
                        </td>
                        <td style="text-align:center">
                            <div class="action-btns" style="justify-content:center">
                                <a href="ajouter_produit.php?edit=<?= $p['id'] ?>" class="btn-icon btn-icon-edit" title="Modifier">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <form method="POST" action="supprimer_produit.php" style="display:inline"
                                      onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($p['nom'])) ?> » ?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-icon btn-icon-del" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <div class="table-footer">
                <span id="footerCount"><?= $total_produits ?> produit(s) affiché(s)</span>
                <span style="color:var(--text-muted)">FitZone Admin v1.0</span>
            </div>
        </div>

    </div><!-- /page-content -->
</div><!-- /main -->

<script>
// ── Recherche + filtre catégorie ──
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const cat    = document.getElementById('filterCat').value;
    const rows   = document.querySelectorAll('#tableBody tr[data-nom]');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.nom.includes(search);
        const matchCat    = !cat    || row.dataset.cat === cat;
        const show = matchSearch && matchCat;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('prodCount').textContent  = visible + ' produits';
    document.getElementById('footerCount').textContent = visible + ' produit(s) affiché(s)';
}

document.getElementById('searchInput').addEventListener('input', filterTable);
</script>
</body>
</html>