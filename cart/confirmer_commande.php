<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// ── Panier vide → retour boutique ──────────────────────────────────────
$panier = $_SESSION['panier'] ?? [];
if (empty($panier)) {
    header('Location: panier.php');
    exit;
}

// ── Calcul totaux ───────────────────────────────────────────────────────
$sous_total  = 0;
$nb_articles = 0;
foreach ($panier as $item) {
    $sous_total  += $item['prix'] * $item['quantite'];
    $nb_articles += $item['quantite'];
}
$livraison = ($sous_total >= 5000) ? 0 : 500;
$total     = $sous_total + $livraison;

// ── Wilayas d'Algérie ───────────────────────────────────────────────────
$wilayas = [
    '01 - Adrar','02 - Chlef','03 - Laghouat','04 - Oum El Bouaghi',
    '05 - Batna','06 - Béjaïa','07 - Biskra','08 - Béchar',
    '09 - Blida','10 - Bouira','11 - Tamanrasset','12 - Tébessa',
    '13 - Tlemcen','14 - Tiaret','15 - Tizi Ouzou','16 - Alger',
    '17 - Djelfa','18 - Jijel','19 - Sétif','20 - Saïda',
    '21 - Skikda','22 - Sidi Bel Abbès','23 - Annaba','24 - Guelma',
    '25 - Constantine','26 - Médéa','27 - Mostaganem','28 - M\'Sila',
    '29 - Mascara','30 - Ouargla','31 - Oran','32 - El Bayadh',
    '33 - Illizi','34 - Bordj Bou Arréridj','35 - Boumerdès',
    '36 - El Tarf','37 - Tindouf','38 - Tissemsilt','39 - El Oued',
    '40 - Khenchela','41 - Souk Ahras','42 - Tipaza','43 - Mila',
    '44 - Aïn Defla','45 - Naâma','46 - Aïn Témouchent','47 - Ghardaïa',
    '48 - Relizane','49 - El M\'Ghair','50 - El Menia','51 - Ouled Djellal',
    '52 - Bordj Baji Mokhtar','53 - Béni Abbès','54 - Timimoun',
    '55 - Touggourt','56 - Djanet','57 - In Salah','58 - In Guezzam',
];

// ── Traitement POST ─────────────────────────────────────────────────────
$errors  = [];
$success = false;
$commande_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = trim($_POST['nom'] ?? '');
    $wilaya         = trim($_POST['wilaya'] ?? '');
    $telephone      = trim($_POST['telephone'] ?? '');
    $type_livraison = $_POST['type_livraison'] ?? 'domicile';

    // Validation
    if (strlen($nom) < 2)            $errors[] = "Le nom complet est obligatoire (min. 2 caractères).";
    if (empty($wilaya))              $errors[] = "Veuillez sélectionner votre wilaya.";
    if (!preg_match('/^[0-9+\s\-]{9,15}$/', $telephone))
                                     $errors[] = "Numéro de téléphone invalide (9 à 15 chiffres).";
    if (!in_array($type_livraison, ['domicile','bureau']))
                                     $errors[] = "Type de livraison invalide.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insérer la commande
            $stmt = $pdo->prepare("
                INSERT INTO commandes
                    (user_id, nom, wilaya, telephone, type_livraison, statut, total)
                VALUES
                    (:user_id, :nom, :wilaya, :telephone, :type_livraison, 'en_attente', :total)
            ");
            $stmt->execute([
                ':user_id'        => $_SESSION['user_id'] ?? null,
                ':nom'            => $nom,
                ':wilaya'         => $wilaya,
                ':telephone'      => $telephone,
                ':type_livraison' => $type_livraison,
                ':total'          => $total,
            ]);
            $commande_id = (int)$pdo->lastInsertId();

            // 2. Insérer les items
            $stmt_item = $pdo->prepare("
                INSERT INTO commande_items
                    (commande_id, produit_id, nom_produit, prix_unitaire, quantite)
                VALUES
                    (:commande_id, :produit_id, :nom_produit, :prix_unitaire, :quantite)
            ");
            foreach ($panier as $prod_id => $item) {
                $stmt_item->execute([
                    ':commande_id'  => $commande_id,
                    ':produit_id'   => $prod_id,
                    ':nom_produit'  => $item['nom'],
                    ':prix_unitaire'=> $item['prix'],
                    ':quantite'     => $item['quantite'],
                ]);
            }

            $pdo->commit();

            // 3. Vider le panier
            unset($_SESSION['panier']);
            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Erreur lors de l'enregistrement. Veuillez réessayer.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'Commande confirmée' : 'Confirmer la commande' ?> — FitZone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f4f6f8; padding-top: 64px; min-height: 100vh; }

        /* ── NAVBAR ── */
        .top-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: #fff; border-bottom: 1px solid #e2e6ea;
            height: 64px; display: flex; align-items: center;
            justify-content: space-between; padding: 0 32px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #1a1a2e; font-weight: 700; font-size: 1.15rem; }
        .nav-logo  { background: #00BCD4; color: #fff; font-weight: 800; font-size: .85rem; padding: 6px 9px; border-radius: 8px; }
        .nav-right { display: flex; align-items: center; gap: 18px; }
        .nav-right a { text-decoration: none; color: #555e6d; font-size: .9rem; transition: color .2s; }
        .nav-right a:hover { color: #00BCD4; }
        .btn-nav-signup { background: #00BCD4; color: #fff !important; padding: 8px 20px; border-radius: 8px; font-weight: 600; }

        /* ── PAGE LAYOUT ── */
        .page-wrap { max-width: 1060px; margin: 0 auto; padding: 40px 20px 60px; }

        /* ── ÉTAPES ── */
        .steps {
            display: flex; align-items: center; gap: 0;
            margin-bottom: 40px; background: #fff;
            border-radius: 12px; border: 1px solid #e2e6ea;
            padding: 20px 32px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .step { display: flex; align-items: center; gap: 10px; flex: 1; }
        .step-num {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; flex-shrink: 0;
        }
        .step.done .step-num   { background: #00BCD4; color: #fff; }
        .step.active .step-num { background: #1a1a2e; color: #fff; }
        .step.wait .step-num   { background: #e2e6ea; color: #9ca3af; }
        .step-label { font-size: .85rem; font-weight: 600; }
        .step.done .step-label   { color: #00BCD4; }
        .step.active .step-label { color: #1a1a2e; }
        .step.wait .step-label   { color: #9ca3af; }
        .step-sep { flex: 0; width: 40px; height: 2px; background: #e2e6ea; margin: 0 8px; }
        .step-sep.done { background: #00BCD4; }

        /* ── LAYOUT PRINCIPAL ── */
        .checkout-grid {
            display: grid; grid-template-columns: 1fr 360px;
            gap: 24px; align-items: start;
        }

        /* ── FORMULAIRE ── */
        .form-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e6ea; padding: 32px 36px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .form-card h2 {
            font-family: 'Bebas Neue', cursive; font-size: 1.6rem;
            letter-spacing: 1px; color: #1a1a2e; margin-bottom: 24px;
            padding-bottom: 14px; border-bottom: 2px solid #f0f0f0;
            display: flex; align-items: center; gap: 10px;
        }
        .form-card h2 i { color: #00BCD4; font-size: 1.2rem; }

        .form-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: .875rem; margin-bottom: 7px; color: #374151; }
        .form-group label .req { color: #00BCD4; }

        .form-group input,
        .form-group select {
            width: 100%; padding: 12px 14px;
            background: #f9fafb; border: 1.5px solid #e2e6ea;
            border-radius: 9px; color: #1a1a2e;
            font-family: 'Outfit', sans-serif; font-size: .93rem;
            outline: none; transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #00BCD4; background: #fff;
            box-shadow: 0 0 0 3px rgba(0,188,212,.1);
        }
        .form-group input.invalid { border-color: #e53935; }

        /* Livraison radio cards */
        .livraison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .livraison-card {
            border: 2px solid #e2e6ea; border-radius: 10px;
            padding: 16px; cursor: pointer; transition: all .2s;
            position: relative;
        }
        .livraison-card input[type="radio"] {
            position: absolute; opacity: 0; width: 0; height: 0;
        }
        .livraison-card:has(input:checked) {
            border-color: #00BCD4; background: rgba(0,188,212,.05);
        }
        .livraison-card .icon {
            font-size: 1.5rem; margin-bottom: 8px; color: #555e6d;
        }
        .livraison-card:has(input:checked) .icon { color: #00BCD4; }
        .livraison-card .lbl-title { font-weight: 700; font-size: .9rem; color: #1a1a2e; margin-bottom: 3px; }
        .livraison-card .lbl-sub   { font-size: .78rem; color: #6b7280; }
        .checkmark {
            position: absolute; top: 12px; right: 12px;
            width: 20px; height: 20px; border-radius: 50%;
            border: 2px solid #e2e6ea; display: flex;
            align-items: center; justify-content: center;
            font-size: .7rem; color: #fff;
            transition: all .2s;
        }
        .livraison-card:has(input:checked) .checkmark {
            background: #00BCD4; border-color: #00BCD4;
        }

        /* Erreurs */
        .errors-box {
            background: rgba(229,57,53,.07); border: 1px solid rgba(229,57,53,.25);
            border-radius: 9px; padding: 14px 18px; margin-bottom: 22px;
        }
        .errors-box p { color: #c62828; font-size: .88rem; font-weight: 600; margin-bottom: 6px; }
        .errors-box ul { padding-left: 18px; }
        .errors-box li { color: #c62828; font-size: .84rem; margin-bottom: 3px; }

        /* Bouton submit */
        .btn-submit {
            width: 100%; padding: 14px; margin-top: 6px;
            background: #00BCD4; color: #fff; border: none;
            border-radius: 9px; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: background .2s, transform .2s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-family: 'Outfit', sans-serif;
        }
        .btn-submit:hover { background: #0097A7; transform: translateY(-1px); }

        /* ── RÉCAP COMMANDE ── */
        .recap-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e6ea; padding: 24px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
            position: sticky; top: 84px;
        }
        .recap-card h3 {
            font-family: 'Bebas Neue', cursive; font-size: 1.3rem;
            letter-spacing: 1px; color: #1a1a2e; margin-bottom: 18px;
            padding-bottom: 12px; border-bottom: 2px solid #f0f0f0;
        }
        .recap-items { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
        .recap-item  { display: flex; gap: 12px; align-items: center; }
        .recap-item img { width: 52px; height: 52px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .recap-item-info { flex: 1; }
        .recap-item-info .r-nom  { font-weight: 600; font-size: .88rem; color: #1a1a2e; line-height: 1.3; }
        .recap-item-info .r-qty  { font-size: .78rem; color: #6b7280; }
        .recap-item-price { font-weight: 700; color: #00BCD4; font-size: .88rem; flex-shrink: 0; }

        .recap-divider { height: 1px; background: #f0f0f0; margin: 14px 0; }
        .recap-row { display: flex; justify-content: space-between; font-size: .88rem; color: #6b7280; padding: 5px 0; }
        .recap-row.total { color: #1a1a2e; font-weight: 700; font-size: .98rem; border-top: 1px solid #e2e6ea; padding-top: 12px; margin-top: 4px; }
        .free-tag { color: #43a047; font-weight: 700; }

        .security-note {
            display: flex; align-items: center; gap: 8px;
            font-size: .78rem; color: #9ca3af; margin-top: 16px;
            padding-top: 12px; border-top: 1px solid #f0f0f0;
        }
        .security-note i { color: #00BCD4; }

        /* ── PAGE SUCCÈS ── */
        .success-wrap {
            max-width: 580px; margin: 0 auto;
            text-align: center; padding: 60px 20px;
        }
        .success-icon-wrap {
            width: 90px; height: 90px; border-radius: 50%;
            background: rgba(67,160,71,.12);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px; font-size: 2.4rem; color: #43a047;
        }
        .success-wrap h1 { font-family: 'Bebas Neue',cursive; font-size: 2.4rem; letter-spacing: 1px; color: #1a1a2e; margin-bottom: 10px; }
        .success-wrap p  { color: #6b7280; font-size: .95rem; line-height: 1.7; margin-bottom: 6px; }
        .commande-num { display: inline-block; background: rgba(0,188,212,.1); color: #00838f; font-weight: 700; font-size: 1rem; padding: 8px 22px; border-radius: 8px; margin: 14px 0 24px; }
        .success-details {
            background: #fff; border-radius: 12px; border: 1px solid #e2e6ea;
            padding: 22px 28px; text-align: left; margin-bottom: 32px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .detail-row { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f0f0f0; font-size: .9rem; }
        .detail-row:last-child { border: none; }
        .detail-row i   { color: #00BCD4; width: 18px; flex-shrink: 0; margin-top: 2px; }
        .detail-row .d-label { color: #6b7280; min-width: 130px; }
        .detail-row .d-val   { color: #1a1a2e; font-weight: 600; }
        .btn-home {
            display: inline-flex; align-items: center; gap: 8px;
            background: #00BCD4; color: #fff; padding: 13px 32px;
            border-radius: 9px; font-weight: 700; font-size: .95rem;
            text-decoration: none; transition: background .2s;
        }
        .btn-home:hover { background: #0097A7; }

        @media(max-width:860px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .recap-card { position: static; }
            .form-row { grid-template-columns: 1fr; }
        }
        @media(max-width:500px) {
            .livraison-grid { grid-template-columns: 1fr; }
            .form-card { padding: 22px 16px; }
            .steps { padding: 16px 12px; }
            .step-label { font-size: .75rem; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="top-nav">
    <a href="../index.php" class="nav-brand">
        <div class="nav-logo">FZ</div> FitZone
    </a>
    <div class="nav-right">
        <a href="../boutique.php"><i class="fas fa-store"></i> Boutique</a>
        <a href="panier.php"><i class="fas fa-shopping-cart"></i> Panier</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        <?php else: ?>
            <a href="../auth/login.php">Login</a>
            <a href="../auth/register.php" class="btn-nav-signup">Sign up</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page-wrap">

<?php if ($success): ?>
<!-- ════════════════════════════════════════════════
     PAGE SUCCÈS
════════════════════════════════════════════════ -->
<div class="success-wrap">
    <div class="success-icon-wrap">
        <i class="fas fa-check"></i>
    </div>
    <h1>Commande confirmée !</h1>
    <p>Merci pour votre commande. Notre équipe vous contactera sous peu pour confirmer la livraison.</p>
    <div class="commande-num">
        <i class="fas fa-hashtag"></i> Commande N° <?= str_pad($commande_id, 5, '0', STR_PAD_LEFT) ?>
    </div>

    <div class="success-details">
        <div class="detail-row">
            <i class="fas fa-user"></i>
            <span class="d-label">Nom :</span>
            <span class="d-val"><?= htmlspecialchars($_POST['nom'] ?? '') ?></span>
        </div>
        <div class="detail-row">
            <i class="fas fa-map-marker-alt"></i>
            <span class="d-label">Wilaya :</span>
            <span class="d-val"><?= htmlspecialchars($_POST['wilaya'] ?? '') ?></span>
        </div>
        <div class="detail-row">
            <i class="fas fa-phone"></i>
            <span class="d-label">Téléphone :</span>
            <span class="d-val"><?= htmlspecialchars($_POST['telephone'] ?? '') ?></span>
        </div>
        <div class="detail-row">
            <i class="fas fa-truck"></i>
            <span class="d-label">Mode de livraison :</span>
            <span class="d-val"><?= ($_POST['type_livraison'] ?? '') === 'bureau' ? '📦 Livraison au bureau' : '🏠 Livraison à domicile' ?></span>
        </div>
        <div class="detail-row">
            <i class="fas fa-money-bill-wave"></i>
            <span class="d-label">Total à payer :</span>
            <span class="d-val" style="color:#00BCD4;"><?= number_format($total, 2) ?> DA</span>
        </div>
        <div class="detail-row">
            <i class="fas fa-info-circle"></i>
            <span class="d-label">Statut :</span>
            <span class="d-val"><span style="background:rgba(251,140,0,.12);color:#fb8c00;padding:2px 10px;border-radius:20px;font-size:.82rem;">⏳ En attente</span></span>
        </div>
    </div>

    <a href="../index.php" class="btn-home">
        <i class="fas fa-home"></i> Retour à l'accueil
    </a>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════
     FORMULAIRE COMMANDE
════════════════════════════════════════════════ -->

<!-- Étapes -->
<div class="steps">
    <div class="step done">
        <div class="step-num"><i class="fas fa-check" style="font-size:.7rem;"></i></div>
        <span class="step-label">Panier</span>
    </div>
    <div class="step-sep done"></div>
    <div class="step active">
        <div class="step-num">2</div>
        <span class="step-label">Livraison</span>
    </div>
    <div class="step-sep"></div>
    <div class="step wait">
        <div class="step-num">3</div>
        <span class="step-label">Confirmation</span>
    </div>
</div>

<div class="checkout-grid">

    <!-- ── FORMULAIRE ── -->
    <div class="form-card">
        <h2><i class="fas fa-truck"></i> Informations de livraison</h2>

        <?php if (!empty($errors)): ?>
        <div class="errors-box">
            <p><i class="fas fa-exclamation-triangle"></i> Veuillez corriger les erreurs suivantes :</p>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="checkout-form" novalidate>

            <!-- Nom complet -->
            <div class="form-group">
                <label for="nom">Nom complet <span class="req">*</span></label>
                <input type="text" id="nom" name="nom" required
                       placeholder="Ex : Houda Benali"
                       value="<?= htmlspecialchars($_POST['nom'] ?? ($_SESSION['user_nom'] ?? '')) ?>">
            </div>

            <!-- Wilaya + Téléphone -->
            <div class="form-row">
                <div class="form-group">
                    <label for="wilaya">Wilaya <span class="req">*</span></label>
                    <select id="wilaya" name="wilaya" required>
                        <option value="">— Choisir la wilaya —</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= htmlspecialchars($w) ?>"
                                <?= (($_POST['wilaya'] ?? '') === $w) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($w) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="telephone">Numéro de téléphone <span class="req">*</span></label>
                    <input type="tel" id="telephone" name="telephone" required
                           placeholder="Ex : 0555 00 00 00"
                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                </div>
            </div>

            <!-- Type de livraison -->
            <div class="form-group">
                <label>Mode de livraison <span class="req">*</span></label>
                <div class="livraison-grid">

                    <label class="livraison-card">
                        <input type="radio" name="type_livraison" value="domicile"
                               <?= (($_POST['type_livraison'] ?? 'domicile') === 'domicile') ? 'checked' : '' ?>>
                        <div class="checkmark"><i class="fas fa-check" style="font-size:.6rem;"></i></div>
                        <div class="icon"><i class="fas fa-home"></i></div>
                        <div class="lbl-title">À domicile</div>
                        <div class="lbl-sub">Livré directement chez vous</div>
                    </label>

                    <label class="livraison-card">
                        <input type="radio" name="type_livraison" value="bureau"
                               <?= (($_POST['type_livraison'] ?? '') === 'bureau') ? 'checked' : '' ?>>
                        <div class="checkmark"><i class="fas fa-check" style="font-size:.6rem;"></i></div>
                        <div class="icon"><i class="fas fa-store"></i></div>
                        <div class="lbl-title">Au bureau</div>
                        <div class="lbl-sub">Retrait au point relais le plus proche</div>
                    </label>

                </div>
            </div>

            <!-- Paiement -->
            <div class="form-group" style="margin-bottom:28px;">
                <label>Mode de paiement</label>
                <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:#f9fafb;border:1.5px solid #e2e6ea;border-radius:9px;">
                    <i class="fas fa-money-bill-wave" style="color:#43a047;font-size:1.1rem;"></i>
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:#1a1a2e;">Paiement à la livraison</div>
                        <div style="font-size:.78rem;color:#6b7280;">Vous payez en cash à la réception</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-check-circle"></i>
                Confirmer ma commande — <?= number_format($total, 2) ?> DA
            </button>

        </form>
    </div>

    <!-- ── RÉCAP PANIER ── -->
    <aside class="recap-card">
        <h3>Récap. commande</h3>

        <div class="recap-items">
            <?php foreach ($panier as $item): ?>
            <div class="recap-item">
                <img src="<?= htmlspecialchars($item['image']) ?>"
                     alt="<?= htmlspecialchars($item['nom']) ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=100'">
                <div class="recap-item-info">
                    <div class="r-nom"><?= htmlspecialchars($item['nom']) ?></div>
                    <div class="r-qty">Qté : <?= (int)$item['quantite'] ?></div>
                </div>
                <div class="recap-item-price"><?= number_format($item['prix'] * $item['quantite'], 2) ?> DA</div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="recap-divider"></div>

        <div class="recap-row">
            <span>Sous-total (<?= $nb_articles ?> article<?= $nb_articles > 1 ? 's' : '' ?>)</span>
            <span><?= number_format($sous_total, 2) ?> DA</span>
        </div>
        <div class="recap-row">
            <span>Livraison</span>
            <?php if ($livraison == 0): ?>
                <span class="free-tag"><i class="fas fa-check"></i> Gratuite</span>
            <?php else: ?>
                <span><?= number_format($livraison, 2) ?> DA</span>
            <?php endif; ?>
        </div>
        <div class="recap-row total">
            <span>Total à payer</span>
            <span><?= number_format($total, 2) ?> DA</span>
        </div>

        <div class="security-note">
            <i class="fas fa-lock"></i>
            Paiement sécurisé à la livraison — aucune CB requise
        </div>
    </aside>

</div>
<?php endif; ?>
</div>

<script>
// Validation légère côté client
document.getElementById('checkout-form')?.addEventListener('submit', function(e) {
    let ok = true;
    this.querySelectorAll('[required]').forEach(el => {
        el.classList.remove('invalid');
        if (!el.value.trim()) { el.classList.add('invalid'); ok = false; }
    });
    if (!ok) { e.preventDefault(); window.scrollTo({top:0,behavior:'smooth'}); }
});
</script>
</body>
</html>
