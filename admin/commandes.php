<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Protection admin
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// ── Changement de statut (AJAX ou POST) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $cmd_id = (int)($_POST['commande_id'] ?? 0);
    $statut = $_POST['statut'] ?? '';

    if ($cmd_id > 0 && in_array($statut, ['en_attente','confirmee','annulee'])) {
        $stmt = $pdo->prepare("UPDATE commandes SET statut = :s WHERE id = :id");
        $stmt->execute([':s' => $statut, ':id' => $cmd_id]);
    }

    // Réponse JSON si appel AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
    header('Location: commandes.php?updated=1');
    exit;
}

// ── Filtres ─────────────────────────────────────────────────────────────
$filtre_statut = $_GET['statut'] ?? 'tous';
$search        = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filtre_statut !== 'tous') {
    $where[]         = 'c.statut = :statut';
    $params[':statut'] = $filtre_statut;
}
if ($search !== '') {
    $where[]      = '(c.nom LIKE :q OR c.telephone LIKE :q OR c.wilaya LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

$sql = "SELECT c.*, COUNT(ci.id) AS nb_items
        FROM commandes c
        LEFT JOIN commande_items ci ON ci.commande_id = c.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY c.id
        ORDER BY c.date_commande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// ── Stats rapides ────────────────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(statut='en_attente')  AS attente,
        SUM(statut='confirmee')   AS confirmees,
        SUM(statut='annulee')     AS annulees,
        SUM(CASE WHEN statut='confirmee' THEN total ELSE 0 END) AS ca
    FROM commandes
")->fetch();

$flash = $_GET['updated'] ?? false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes — FitZone Admin</title>
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
        .brand { color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand span { background: #00BCD4; padding: 4px 8px; border-radius: 6px; font-size: .8rem; }
        .topbar-links a { color: rgba(255,255,255,.7); font-size: .88rem; text-decoration: none; margin-left: 20px; transition: color .2s; }
        .topbar-links a:hover { color: #00BCD4; }
        .topbar-links a.active-link { color: #00BCD4; font-weight: 600; }

        /* ── CONTENU ── */
        .admin-main { max-width: 1280px; margin: 0 auto; padding: 36px 24px 60px; }

        /* ── STATS ── */
        .stats-row { display: grid; grid-template-columns: repeat(5,1fr); gap: 16px; margin-bottom: 32px; }
        .stat-box {
            background: #fff; border-radius: 12px; border: 1px solid #e2e6ea;
            padding: 20px 20px 16px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .stat-box .s-icon { font-size: 1.1rem; margin-bottom: 10px; }
        .stat-box .s-val  { font-size: 1.6rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
        .stat-box .s-lbl  { font-size: .78rem; color: #9ca3af; margin-top: 4px; }
        .stat-box.orange .s-val { color: #fb8c00; }
        .stat-box.green  .s-val { color: #43a047; }
        .stat-box.red    .s-val { color: #e53935; }
        .stat-box.teal   .s-val { color: #00BCD4; }

        /* ── FILTRES ── */
        .filters-bar {
            display: flex; gap: 14px; align-items: center; flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .filter-tabs { display: flex; gap: 6px; }
        .f-tab {
            padding: 8px 16px; border-radius: 20px; font-size: .84rem;
            font-weight: 600; text-decoration: none; transition: all .2s;
            border: 1.5px solid #e2e6ea; color: #6b7280; background: #fff;
        }
        .f-tab:hover, .f-tab.active { background: #00BCD4; color: #fff; border-color: #00BCD4; }
        .f-tab.att:hover, .f-tab.att.active { background: #fb8c00; border-color: #fb8c00; }
        .f-tab.conf:hover, .f-tab.conf.active { background: #43a047; border-color: #43a047; }
        .f-tab.ann:hover, .f-tab.ann.active { background: #e53935; border-color: #e53935; }

        .search-input-wrap { flex: 1; max-width: 320px; position: relative; }
        .search-input-wrap input {
            width: 100%; padding: 9px 14px 9px 38px;
            border: 1.5px solid #e2e6ea; border-radius: 8px;
            font-size: .88rem; outline: none; background: #fff;
            font-family: 'Outfit', sans-serif; color: #1a1a2e;
            transition: border-color .2s;
        }
        .search-input-wrap input:focus { border-color: #00BCD4; }
        .search-input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        /* ── TABLE ── */
        .table-card { background: #fff; border-radius: 14px; border: 1px solid #e2e6ea; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.06); }
        .table-card table { width: 100%; border-collapse: collapse; }
        thead th { background: #f4f6f8; padding: 11px 16px; font-size: .75rem; text-transform: uppercase; letter-spacing: .8px; color: #6b7280; text-align: left; border-bottom: 1px solid #e2e6ea; white-space: nowrap; }
        tbody td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; font-size: .875rem; color: #1a1a2e; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafafa; }

        /* Badges statut */
        .badge-statut {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            font-size: .78rem; font-weight: 700; white-space: nowrap;
        }
        .badge-statut.attente   { background: rgba(251,140,0,.12); color: #e65100; }
        .badge-statut.confirmee { background: rgba(67,160,71,.12);  color: #2e7d32; }
        .badge-statut.annulee   { background: rgba(229,57,53,.1);   color: #c62828; }

        /* Boutons livraison */
        .badge-livraison {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f4f6f8; border-radius: 6px; padding: 3px 10px;
            font-size: .78rem; font-weight: 600; color: #555e6d;
        }

        /* Select statut */
        .select-statut {
            padding: 6px 10px; border-radius: 7px; font-size: .82rem;
            border: 1.5px solid #e2e6ea; background: #f9fafb;
            cursor: pointer; outline: none; font-family: 'Outfit', sans-serif;
            transition: border-color .2s; color: #1a1a2e;
        }
        .select-statut:focus { border-color: #00BCD4; }

        /* Btn détails */
        .btn-detail-cmd {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(0,188,212,.08); color: #00838f;
            border: none; padding: 6px 12px; border-radius: 7px;
            font-size: .8rem; font-weight: 600; cursor: pointer;
            transition: background .2s; text-decoration: none;
        }
        .btn-detail-cmd:hover { background: rgba(0,188,212,.18); }

        /* ── MODAL DÉTAIL ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 2000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; width: 100%; max-width: 600px;
            max-height: 85vh; overflow-y: auto; margin: 20px;
            box-shadow: 0 16px 60px rgba(0,0,0,.18);
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 28px 18px; border-bottom: 1px solid #e2e6ea;
        }
        .modal-header h3 { font-family: 'Bebas Neue',cursive; font-size: 1.5rem; letter-spacing: 1px; color: #1a1a2e; }
        .modal-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #9ca3af; padding: 4px 8px; border-radius: 6px; transition: all .2s; }
        .modal-close:hover { background: #f4f6f8; color: #1a1a2e; }
        .modal-body { padding: 24px 28px; }
        .modal-section { margin-bottom: 22px; }
        .modal-section h4 { font-weight: 700; font-size: .85rem; text-transform: uppercase; letter-spacing: .6px; color: #9ca3af; margin-bottom: 12px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-item { background: #f9fafb; border-radius: 8px; padding: 10px 14px; }
        .info-item .i-label { font-size: .75rem; color: #9ca3af; margin-bottom: 3px; }
        .info-item .i-val   { font-weight: 700; font-size: .9rem; color: #1a1a2e; }
        .items-list { display: flex; flex-direction: column; gap: 8px; }
        .order-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f9fafb; border-radius: 8px; font-size: .875rem; }
        .order-item .oi-name { font-weight: 600; color: #1a1a2e; }
        .order-item .oi-qty  { color: #6b7280; font-size: .82rem; }
        .order-item .oi-price { font-weight: 700; color: #00BCD4; }
        .order-total { display: flex; justify-content: space-between; padding: 12px 14px; font-weight: 800; font-size: 1rem; color: #1a1a2e; border-top: 2px solid #e2e6ea; margin-top: 8px; }

        /* ── TOAST ── */
        .toast {
            position: fixed; bottom: 28px; right: 28px; z-index: 3000;
            background: #1a1a2e; color: #fff; padding: 13px 22px;
            border-radius: 10px; font-size: .88rem; font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
            transform: translateY(80px); opacity: 0;
            transition: all .3s; display: flex; align-items: center; gap: 10px;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast i { color: #00BCD4; }

        /* ── VIDE ── */
        .empty-table { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-table i { font-size: 2.5rem; color: #d1d5db; margin-bottom: 14px; display: block; }
        .empty-table p { font-size: .9rem; }

        @media(max-width:1000px){ .stats-row { grid-template-columns: repeat(3,1fr); } }
        @media(max-width:700px) { .stats-row { grid-template-columns: 1fr 1fr; } .filters-bar { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="admin-topbar">
    <a href="admin.php" class="brand"><span>ADMIN</span> FitZone</a>
    <div class="topbar-links">
        <a href="admin.php"><i class="fas fa-box"></i> Produits</a>
        <a href="commandes.php" class="active-link"><i class="fas fa-list-alt"></i> Commandes</a>
        <a href="../index.php"><i class="fas fa-store"></i> Boutique</a>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="admin-main">

    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">
            <i class="fas fa-check-circle"></i> Statut mis à jour avec succès.
        </div>
    <?php endif; ?>

    <!-- ── TITRE ── -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
        <div>
            <h1 style="font-family:'Bebas Neue',cursive;font-size:2rem;letter-spacing:1px;color:#1a1a2e;margin-bottom:2px;">Gestion des Commandes</h1>
            <p style="color:#6b7280;font-size:.88rem;"><?= $stats['total'] ?> commande<?= $stats['total'] > 1 ? 's' : '' ?> au total</p>
        </div>
    </div>

    <!-- ── STATS ── -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="s-icon">📦</div>
            <div class="s-val"><?= $stats['total'] ?></div>
            <div class="s-lbl">Total commandes</div>
        </div>
        <div class="stat-box orange">
            <div class="s-icon">⏳</div>
            <div class="s-val"><?= $stats['attente'] ?></div>
            <div class="s-lbl">En attente</div>
        </div>
        <div class="stat-box green">
            <div class="s-icon">✅</div>
            <div class="s-val"><?= $stats['confirmees'] ?></div>
            <div class="s-lbl">Confirmées</div>
        </div>
        <div class="stat-box red">
            <div class="s-icon">❌</div>
            <div class="s-val"><?= $stats['annulees'] ?></div>
            <div class="s-lbl">Annulées</div>
        </div>
        <div class="stat-box teal">
            <div class="s-icon">💰</div>
            <div class="s-val"><?= number_format($stats['ca'] ?? 0, 0, ',', ' ') ?></div>
            <div class="s-lbl">CA confirmé (DA)</div>
        </div>
    </div>

    <!-- ── FILTRES ── -->
    <form method="GET" action="" class="filters-bar">
        <div class="filter-tabs">
            <a href="?statut=tous" class="f-tab <?= $filtre_statut==='tous' ? 'active' : '' ?>">Tous (<?= $stats['total'] ?>)</a>
            <a href="?statut=en_attente" class="f-tab att <?= $filtre_statut==='en_attente' ? 'active' : '' ?>">⏳ Attente (<?= $stats['attente'] ?>)</a>
            <a href="?statut=confirmee"  class="f-tab conf <?= $filtre_statut==='confirmee'  ? 'active' : '' ?>">✅ Confirmées (<?= $stats['confirmees'] ?>)</a>
            <a href="?statut=annulee"    class="f-tab ann <?= $filtre_statut==='annulee'    ? 'active' : '' ?>">❌ Annulées (<?= $stats['annulees'] ?>)</a>
        </div>
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, téléphone, wilaya…" oninput="this.form.submit()">
            <input type="hidden" name="statut" value="<?= htmlspecialchars($filtre_statut) ?>">
        </div>
    </form>

    <!-- ── TABLE ── -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Wilaya</th>
                    <th>Téléphone</th>
                    <th>Livraison</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($commandes)): ?>
                <tr>
                    <td colspan="10">
                        <div class="empty-table">
                            <i class="fas fa-inbox"></i>
                            <p>Aucune commande trouvée.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($commandes as $cmd): ?>
                <tr>
                    <td style="font-weight:700;color:#9ca3af;font-size:.82rem;">#<?= str_pad($cmd['id'],5,'0',STR_PAD_LEFT) ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($cmd['nom']) ?></td>
                    <td><?= htmlspecialchars($cmd['wilaya']) ?></td>
                    <td><?= htmlspecialchars($cmd['telephone']) ?></td>
                    <td>
                        <span class="badge-livraison">
                            <?php if ($cmd['type_livraison']==='bureau'): ?>
                                <i class="fas fa-store"></i> Bureau
                            <?php else: ?>
                                <i class="fas fa-home"></i> Domicile
                            <?php endif; ?>
                        </span>
                    </td>
                    <td style="color:#6b7280;font-size:.82rem;"><?= $cmd['nb_items'] ?> article<?= $cmd['nb_items'] > 1 ? 's' : '' ?></td>
                    <td style="font-weight:700;color:#00BCD4;"><?= number_format($cmd['total'],2) ?> DA</td>
                    <td style="color:#6b7280;font-size:.82rem;white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                    <td>
                        <?php
                        $s = $cmd['statut'];
                        $cls = $s==='confirmee' ? 'confirmee' : ($s==='annulee' ? 'annulee' : 'attente');
                        $icons = ['en_attente'=>'⏳','confirmee'=>'✅','annulee'=>'❌'];
                        $labels = ['en_attente'=>'En attente','confirmee'=>'Confirmée','annulee'=>'Annulée'];
                        ?>
                        <span class="badge-statut <?= $cls ?>">
                            <?= $icons[$s] ?> <?= $labels[$s] ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <!-- Changer statut -->
                            <select class="select-statut" data-id="<?= $cmd['id'] ?>" onchange="changerStatut(this)">
                                <option value="en_attente" <?= $cmd['statut']==='en_attente' ? 'selected' : '' ?>>⏳ En attente</option>
                                <option value="confirmee"  <?= $cmd['statut']==='confirmee'  ? 'selected' : '' ?>>✅ Confirmée</option>
                                <option value="annulee"    <?= $cmd['statut']==='annulee'    ? 'selected' : '' ?>>❌ Annulée</option>
                            </select>
                            <!-- Voir détails -->
                            <button class="btn-detail-cmd" onclick="ouvrirDetail(<?= $cmd['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /admin-main -->

<!-- ── MODAL DÉTAIL ──────────────────────────────────────────── -->
<div class="modal-overlay" id="modalOverlay" onclick="fermerModal(event)">
    <div class="modal-box" id="modalBox">
        <div class="modal-header">
            <h3 id="modal-title">Détail commande</h3>
            <button class="modal-close" onclick="fermerModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="modal-body">
            <div style="text-align:center;padding:40px;color:#9ca3af;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"><i class="fas fa-check"></i> <span id="toast-msg"></span></div>

<!-- Données commandes pour modal (JSON) -->
<script>
const COMMANDES = <?= json_encode(array_column($commandes, null, 'id'), JSON_UNESCAPED_UNICODE) ?>;

// ── Changer statut via fetch ────────────────────────────────────────────
function changerStatut(select) {
    const id     = select.dataset.id;
    const statut = select.value;

    fetch('commandes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: `action=update&commande_id=${id}&statut=${statut}`,
    })
    .then(r => r.json())
    .then(() => {
        // Mettre à jour le badge dans la ligne
        const row = select.closest('tr');
        const badgeMap = {
            en_attente: { cls: 'attente',   label: '⏳ En attente' },
            confirmee:  { cls: 'confirmee', label: '✅ Confirmée' },
            annulee:    { cls: 'annulee',   label: '❌ Annulée' },
        };
        const b = row.querySelector('.badge-statut');
        if (b) {
            b.className = `badge-statut ${badgeMap[statut].cls}`;
            b.textContent = badgeMap[statut].label;
        }
        showToast('Statut mis à jour');
    });
}

// ── Toast ───────────────────────────────────────────────────────────────
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Modal détail ────────────────────────────────────────────────────────
function ouvrirDetail(id) {
    const cmd = COMMANDES[id];
    if (!cmd) return;

    document.getElementById('modal-title').textContent =
        `Commande #${String(id).padStart(5,'0')}`;

    const typeLabel = cmd.type_livraison === 'bureau' ? '📦 Bureau' : '🏠 Domicile';
    const statutLabel = { en_attente:'⏳ En attente', confirmee:'✅ Confirmée', annulee:'❌ Annulée' }[cmd.statut];

    document.getElementById('modal-body').innerHTML = `
        <div class="modal-section">
            <h4>Informations client</h4>
            <div class="info-grid">
                <div class="info-item"><div class="i-label">Nom</div><div class="i-val">${cmd.nom}</div></div>
                <div class="info-item"><div class="i-label">Téléphone</div><div class="i-val">${cmd.telephone}</div></div>
                <div class="info-item"><div class="i-label">Wilaya</div><div class="i-val">${cmd.wilaya}</div></div>
                <div class="info-item"><div class="i-label">Livraison</div><div class="i-val">${typeLabel}</div></div>
                <div class="info-item"><div class="i-label">Date</div><div class="i-val">${new Date(cmd.date_commande).toLocaleString('fr-DZ')}</div></div>
                <div class="info-item"><div class="i-label">Statut</div><div class="i-val">${statutLabel}</div></div>
            </div>
        </div>
        <div class="modal-section">
            <h4>Articles commandés</h4>
            <div id="modal-items-${id}" class="items-list">
                <div style="text-align:center;padding:20px;color:#9ca3af;"><i class="fas fa-spinner fa-spin"></i> Chargement…</div>
            </div>
            <div class="order-total"><span>Total</span><span style="color:#00BCD4;">${parseFloat(cmd.total).toFixed(2)} DA</span></div>
        </div>
    `;

    // Charger les items de la commande
    fetch(`get_commande_items.php?id=${id}`)
        .then(r => r.json())
        .then(items => {
            const container = document.getElementById(`modal-items-${id}`);
            if (!items.length) { container.innerHTML = '<p style="color:#9ca3af;font-size:.88rem;">Aucun article trouvé.</p>'; return; }
            container.innerHTML = items.map(it => `
                <div class="order-item">
                    <div><div class="oi-name">${it.nom_produit}</div><div class="oi-qty">Qté : ${it.quantite}</div></div>
                    <div class="oi-price">${(it.prix_unitaire * it.quantite).toFixed(2)} DA</div>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById(`modal-items-${id}`).innerHTML = '<p style="color:#e53935;font-size:.88rem;">Impossible de charger les articles.</p>';
        });

    document.getElementById('modalOverlay').classList.add('open');
}

function fermerModal(e) {
    if (!e || e.target === document.getElementById('modalOverlay') || e.currentTarget.classList.contains('modal-close')) {
        document.getElementById('modalOverlay').classList.remove('open');
    }
}
document.addEventListener('keydown', e => { if (e.key==='Escape') fermerModal(); });
</script>
</body>
</html>
