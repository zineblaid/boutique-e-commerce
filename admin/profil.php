<?php
session_start();

require_once __DIR__ . '/../config/config.php';

/* =========================
   VERIFICATION CONNEXION
========================= */
if (!isset($_SESSION['user_id']) || empty($_SESSION['admin_id']) || empty($_SESSION['is_admin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$admin_id = (int) $_SESSION['admin_id'];

/* =========================
   RECUPERATION ADMIN
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    AND role = 'admin'
");

$stmt->execute([$admin_id]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* =========================
   MESSAGES
========================= */
$success_email = '';
$success_pass  = '';

$error_email = '';
$error_pass  = '';

/* =========================
   CHANGER EMAIL
========================= */
if (
    isset($_POST['action']) &&
    $_POST['action'] === 'change_email'
) {

    $new_email = trim($_POST['new_email'] ?? '');
    $confirm_pass = trim($_POST['confirm_password_email'] ?? '');

    if (empty($new_email) || empty($confirm_pass)) {

        $error_email = "Tous les champs sont obligatoires.";

    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {

        $error_email = "Adresse email invalide.";

    } elseif (!password_verify($confirm_pass, $admin['password'])) {

        $error_email = "Mot de passe incorrect.";

    } else {

        // vérifier si email existe
        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
        ");

        $check->execute([$new_email, $admin_id]);

        if ($check->fetch()) {

            $error_email = "Cet email existe déjà.";

        } else {

            $update = $pdo->prepare("
                UPDATE users
                SET email = ?
                WHERE id = ?
            ");

            $update->execute([$new_email, $admin_id]);

            $_SESSION['admin_email'] = $new_email;

            $admin['email'] = $new_email;

            $success_email = "Email modifié avec succès.";
        }
    }
}

/* =========================
   CHANGER MOT DE PASSE
========================= */
if (
    isset($_POST['action']) &&
    $_POST['action'] === 'change_password'
) {

    $current_password = trim($_POST['current_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (
        empty($current_password) ||
        empty($new_password) ||
        empty($confirm_password)
    ) {

        $error_pass = "Tous les champs sont obligatoires.";

    } elseif (!password_verify($current_password, $admin['password'])) {

        $error_pass = "Mot de passe actuel incorrect.";

    } elseif (strlen($new_password) < 8) {

        $error_pass = "Minimum 8 caractères.";

    } elseif (!preg_match('/[A-Z]/', $new_password)) {

        $error_pass = "Le mot de passe doit contenir une majuscule.";

    } elseif (!preg_match('/[0-9]/', $new_password)) {

        $error_pass = "Le mot de passe doit contenir un chiffre.";

    } elseif ($new_password !== $confirm_password) {

        $error_pass = "Les mots de passe ne correspondent pas.";

    } elseif (password_verify($new_password, $admin['password'])) {

        $error_pass = "Le nouveau mot de passe doit être différent.";

    } else {

        $hashed_password = password_hash(
            $new_password,
            PASSWORD_BCRYPT,
            ['cost' => 12]
        );

        $update = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $update->execute([
            $hashed_password,
            $admin_id
        ]);

        $admin['password'] = $hashed_password;

        $success_pass = "Mot de passe changé avec succès.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Admin — FitZone</title>
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
            --text: #1a1d2e;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --danger: #ef4444;
            --success: #10b981;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.05);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* SIDEBAR (same as admin.php) */
        .sidebar {
            width: var(--sidebar-w); background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
        }
        .sidebar-logo {
            padding: 20px 20px 16px; display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; color: #fff; flex-shrink: 0;
        }
        .logo-text { font-family: 'Bebas Neue', cursive; font-size: 1.3rem; letter-spacing: 2px; color: #fff; }
        .logo-sub  { font-size: .68rem; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: 1px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-label { font-size: .68rem; font-weight: 600; color: rgba(255,255,255,.25); text-transform: uppercase; letter-spacing: 1.2px; padding: 12px 8px 6px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            color: rgba(255,255,255,.6); font-size: .875rem; font-weight: 500;
            text-decoration: none; margin-bottom: 2px; transition: all .2s;
            border: none; background: none; width: 100%; text-align: left; cursor: pointer;
        }
        .nav-item i { width: 18px; text-align: center; font-size: .85rem; }
        .nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nav-item.active { background: rgba(0,188,212,.15); color: var(--primary); }
        .sidebar-footer { padding: 14px 12px; border-top: 1px solid rgba(255,255,255,.07); }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }
        .topbar {
            height: var(--topbar-h); background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 28px; gap: 16px;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-breadcrumb { font-size: .8rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .topbar-breadcrumb span { color: var(--text); font-weight: 600; }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
        .admin-avatar {
            display: flex; align-items: center; gap: 10px;
            padding: 5px 12px 5px 5px; background: var(--bg);
            border: 1px solid var(--border); border-radius: 30px;
        }
        .avatar-img {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; color: #fff;
        }
        .admin-avatar span { font-size: .82rem; font-weight: 600; }

        /* PAGE */
        .page-content { padding: 28px; flex: 1; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-family: 'Bebas Neue', cursive; font-size: 2rem; letter-spacing: 1.5px; }
        .page-header p { color: var(--text-muted); font-size: .875rem; margin-top: 2px; }

        /* Profile card top */
        .profile-hero {
            background: linear-gradient(135deg, #1a1d2e 0%, #0d2040 100%);
            border-radius: var(--radius); padding: 28px 32px;
            display: flex; align-items: center; gap: 24px;
            margin-bottom: 28px; box-shadow: var(--shadow);
        }
        .profile-avatar-big {
            width: 72px; height: 72px; border-radius: 18px;
            background: linear-gradient(135deg, var(--primary), #0097A7);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 700; color: #fff; flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(0,188,212,.35);
        }
        .profile-info h2 { font-family: 'Bebas Neue', cursive; font-size: 1.6rem; letter-spacing: 1px; color: #fff; }
        .profile-info p  { font-size: .875rem; color: rgba(255,255,255,.5); margin-top: 3px; }
        .profile-badge {
            margin-left: auto; background: rgba(0,188,212,.2); color: #00BCD4;
            border: 1px solid rgba(0,188,212,.3); padding: 6px 16px;
            border-radius: 20px; font-size: .8rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
        }

        /* Cards grid */
        .cards-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .card {
            background: var(--white); border-radius: var(--radius);
            border: 1px solid var(--border); box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-header {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .card-header-icon {
            width: 36px; height: 36px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; font-size: .9rem;
        }
        .ic-teal   { background: rgba(0,188,212,.1);  color: var(--primary); }
        .ic-purple { background: rgba(139,92,246,.1); color: #8b5cf6; }
        .card-header h3 { font-size: 1rem; font-weight: 700; color: var(--text); }
        .card-header p  { font-size: .78rem; color: var(--text-muted); }
        .card-body { padding: 24px; }

        /* Form elements */
        .form-group { margin-bottom: 18px; }
        .form-group:last-of-type { margin-bottom: 0; }
        .form-group label {
            display: block; font-size: .75rem; font-weight: 700;
            color: #374151; text-transform: uppercase;
            letter-spacing: .7px; margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-wrap .i-left {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: .82rem; pointer-events: none; transition: color .2s;
        }
        .input-wrap input {
            width: 100%; padding: 10px 40px;
            background: #f9fafb; border: 1.5px solid var(--border);
            border-radius: 9px; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: .9rem;
            outline: none; transition: all .2s;
        }
        .input-wrap input:focus {
            border-color: var(--primary); background: #fff;
            box-shadow: 0 0 0 3px rgba(0,188,212,.1);
        }
        .input-wrap input:focus ~ .i-left { color: var(--primary); }
        .input-wrap input::placeholder { color: #c4c9d4; }
        .input-wrap input[readonly] { background: #f3f4f6; color: var(--text-muted); cursor: not-allowed; }

        .eye-btn {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: .82rem; padding: 4px; transition: color .2s;
        }
        .eye-btn:hover { color: var(--primary); }

        /* Password strength */
        .strength-bar {
            height: 4px; border-radius: 2px; background: #e5e7eb;
            margin-top: 8px; overflow: hidden;
        }
        .strength-fill {
            height: 100%; border-radius: 2px;
            transition: width .3s, background .3s;
            width: 0;
        }
        .strength-text { font-size: .72rem; color: var(--text-muted); margin-top: 4px; }

        /* Requirement checklist */
        .requirements { margin-top: 10px; }
        .req {
            display: flex; align-items: center; gap: 7px;
            font-size: .78rem; color: var(--text-muted);
            margin-bottom: 4px; transition: color .2s;
        }
        .req i { font-size: .7rem; width: 14px; transition: color .2s; }
        .req.ok { color: var(--success); }
        .req.ok i { color: var(--success); }

        /* Alerts */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            border-radius: 9px; padding: 11px 14px;
            font-size: .875rem; margin-bottom: 16px;
            animation: fadeIn .3s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
        .alert-success { background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); color: #065f46; }
        .alert-error   { background: rgba(239,68,68,.08);  border: 1px solid rgba(239,68,68,.2);  color: #991b1b; }

        /* Submit button */
        .btn-save {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff; padding: 10px 22px;
            border-radius: 9px; font-weight: 600; font-size: .875rem;
            border: none; cursor: pointer;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 3px 12px rgba(0,188,212,.3);
            margin-top: 20px; width: 100%; justify-content: center;
        }
        .btn-save:hover { opacity: .92; box-shadow: 0 5px 18px rgba(0,188,212,.4); }
        .btn-save:active { transform: scale(.98); }

        @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .main { margin-left: 0; } .profile-badge { display: none; } }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-dumbbell"></i></div>
        <div>
            <div class="logo-text">FitZone</div>
            <div class="logo-sub">Admin Panel</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="admin.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
        <div class="nav-label">Catalogue</div>
        <a href="ajouter_produit.php" class="nav-item"><i class="fas fa-plus-circle"></i> Ajouter un produit</a>
        <a href="admin.php" class="nav-item"><i class="fas fa-boxes"></i> Inventaire</a>
        <div class="nav-label">Compte</div>
        <a href="profil.php" class="nav-item active"><i class="fas fa-user-cog"></i> Mon profil</a>
        <a href="../index.php" class="nav-item"><i class="fas fa-store"></i> Voir la boutique</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item" style="color:#ef4444;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <header class="topbar">
        <div class="topbar-breadcrumb">
            <a href="admin.php" style="color:var(--text-muted);text-decoration:none;">Dashboard</a>
            <i class="fas fa-chevron-right" style="font-size:.6rem"></i>
            <span>Mon Profil</span>
        </div>
        <div class="topbar-right">
            <div class="admin-avatar">
                <div class="avatar-img"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
                <span><?= htmlspecialchars($admin['name']) ?></span>
            </div>
        </div>
    </header>

    <div class="page-content">
        <div class="page-header">
            <h1>Mon Profil</h1>
            <p>Gérez vos informations de connexion et votre mot de passe.</p>
        </div>

        <!-- PROFILE HERO -->
        <div class="profile-hero">
            <div class="profile-avatar-big"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($admin['name']) ?></h2>
                <p><?= htmlspecialchars($admin['email']) ?> · Membre depuis <?= date('d/m/Y', strtotime($admin['created_at'])) ?></p>
            </div>
            <div class="profile-badge"><i class="fas fa-shield-alt"></i> Administrateur</div>
        </div>

        <div class="cards-grid">

            <!-- ══ CHANGER EMAIL ══ -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon ic-teal"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h3>Adresse e-mail</h3>
                        <p>Modifier votre e-mail de connexion</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($success_email): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_email) ?></div>
                    <?php endif; ?>
                    <?php if ($error_email): ?>
                        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_email) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_email">

                        <div class="form-group">
                            <label>E-mail actuel</label>
                            <div class="input-wrap">
                                <input type="email" value="<?= htmlspecialchars($admin['email']) ?>" readonly>
                                <i class="fas fa-envelope i-left"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nouvel e-mail *</label>
                            <div class="input-wrap">
                                <input type="email" name="new_email" required
                                       placeholder="nouveau@email.dz"
                                       value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>">
                                <i class="fas fa-at i-left"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirmer avec votre mot de passe *</label>
                            <div class="input-wrap">
                                <input type="password" name="confirm_password_email"
                                       required placeholder="Votre mot de passe actuel">
                                <i class="fas fa-lock i-left"></i>
                                <button type="button" class="eye-btn" onclick="togglePwd(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Mettre à jour l'e-mail
                        </button>
                    </form>
                </div>
            </div>

            <!-- ══ CHANGER MOT DE PASSE ══ -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon ic-purple"><i class="fas fa-key"></i></div>
                    <div>
                        <h3>Mot de passe</h3>
                        <p>Choisissez un mot de passe sécurisé</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($success_pass): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_pass) ?></div>
                    <?php endif; ?>
                    <?php if ($error_pass): ?>
                        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_pass) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label>Mot de passe actuel *</label>
                            <div class="input-wrap">
                                <input type="password" name="current_password"
                                       required placeholder="Votre mot de passe actuel">
                                <i class="fas fa-lock i-left"></i>
                                <button type="button" class="eye-btn" onclick="togglePwd(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nouveau mot de passe *</label>
                            <div class="input-wrap">
                                <input type="password" name="new_password" id="newPass"
                                       required placeholder="Min. 8 caractères"
                                       oninput="checkStrength(this.value)">
                                <i class="fas fa-lock i-left"></i>
                                <button type="button" class="eye-btn" onclick="togglePwd(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <!-- Barre de force -->
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            <div class="strength-text" id="strengthText"></div>
                            <!-- Requirements -->
                            <div class="requirements">
                                <div class="req" id="req-len"><i class="fas fa-circle"></i> Au moins 8 caractères</div>
                                <div class="req" id="req-upper"><i class="fas fa-circle"></i> Une lettre majuscule</div>
                                <div class="req" id="req-num"><i class="fas fa-circle"></i> Un chiffre</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirmer le nouveau mot de passe *</label>
                            <div class="input-wrap">
                                <input type="password" name="confirm_password"
                                       id="confirmPass" required placeholder="Répéter le mot de passe"
                                       oninput="checkMatch()">
                                <i class="fas fa-lock i-left"></i>
                                <button type="button" class="eye-btn" onclick="togglePwd(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="req" id="req-match" style="margin-top:6px"><i class="fas fa-circle"></i> Les mots de passe correspondent</div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-key"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>

        </div><!-- /cards-grid -->
    </div><!-- /page-content -->
</div><!-- /main -->

<script>
function togglePwd(btn) {
    const input = btn.closest('.input-wrap').querySelector('input');
    const icon  = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const text  = document.getElementById('strengthText');
    const reqLen   = document.getElementById('req-len');
    const reqUpper = document.getElementById('req-upper');
    const reqNum   = document.getElementById('req-num');

    const hasLen   = val.length >= 8;
    const hasUpper = /[A-Z]/.test(val);
    const hasNum   = /[0-9]/.test(val);
    const hasSpec  = /[^A-Za-z0-9]/.test(val);

    reqLen.classList.toggle('ok', hasLen);
    reqUpper.classList.toggle('ok', hasUpper);
    reqNum.classList.toggle('ok', hasNum);

    const score = [hasLen, hasUpper, hasNum, hasSpec, val.length >= 12].filter(Boolean).length;
    const levels = [
        { pct: '20%', color: '#ef4444', label: 'Très faible' },
        { pct: '40%', color: '#f59e0b', label: 'Faible' },
        { pct: '60%', color: '#eab308', label: 'Moyen' },
        { pct: '80%', color: '#10b981', label: 'Fort' },
        { pct: '100%',color: '#059669', label: 'Très fort' },
    ];
    const lvl = levels[Math.max(0, score - 1)] || levels[0];
    fill.style.width    = val ? lvl.pct : '0';
    fill.style.background = lvl.color;
    text.style.color    = lvl.color;
    text.textContent    = val ? lvl.label : '';

    checkMatch();
}

function checkMatch() {
    const np = document.getElementById('newPass').value;
    const cp = document.getElementById('confirmPass').value;
    const el = document.getElementById('req-match');
    if (cp) {
        el.classList.toggle('ok', np === cp);
    } else {
        el.classList.remove('ok');
    }
}
</script>
</body>
</html>