<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// 1. Redirection si déjà connecté — vérification stricte pour éviter les boucles
if (
    !empty($_SESSION['admin_id']) &&
    !empty($_SESSION['user_id']) &&
    !empty($_SESSION['is_admin']) &&
    isset($_SESSION['role']) && $_SESSION['role'] === 'admin'
) {
    header('Location: profil.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        
        // --- ÉTAPE CRUCIALE : On récupère l'admin d'abord ---
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC); // C'est ici que la variable $admin est créée !

        // --- Ensuite, on vérifie si $admin existe ET si le mot de passe est bon ---
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);

            // On remplit TOUTES les variables de session pour profil.php et admin.php
            $_SESSION['admin_id']    = $admin['id'];
            $_SESSION['user_id']     = $admin['id'];    // Requis par la ligne 10 de profil.php
            $_SESSION['role']        = 'admin';
            $_SESSION['is_admin']    = true;           // Requis par la ligne 19 de profil.php
            $_SESSION['admin_name']  = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];

            header('Location: profil.php');
            exit();
        } else {
            $error = "Identifiants incorrects ou accès non autorisé.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — FitZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            background: #0b0f1e;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            background: linear-gradient(145deg, #0d1428 0%, #1a2744 50%, #0d2040 100%);
            display: flex; flex-direction: column;
            justify-content: center; align-items: flex-start;
            padding: 60px 56px;
            position: relative; overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(0,188,212,.15) 0%, transparent 50%),
                              radial-gradient(circle at 80% 20%, rgba(139,92,246,.1) 0%, transparent 40%);
        }
        .left-panel::after {
            content: '';
            position: absolute; bottom: -60px; right: -60px;
            width: 300px; height: 300px; border-radius: 50%;
            border: 1px solid rgba(0,188,212,.12);
            box-shadow: inset 0 0 60px rgba(0,188,212,.05);
        }

        .panel-logo {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 56px; position: relative; z-index: 1;
        }
        .panel-logo-icon {
            width: 44px; height: 44px; border-radius: 11px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #fff;
            box-shadow: 0 4px 16px rgba(0,188,212,.4);
        }
        .panel-logo-text {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem; letter-spacing: 3px; color: #fff;
        }

        .panel-content { position: relative; z-index: 1; }
        .panel-content h1 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem; letter-spacing: 2px;
            color: #fff; line-height: 1.1; margin-bottom: 16px;
        }
        .panel-content h1 span { color: #00BCD4; }
        .panel-content p {
            font-size: .95rem; color: rgba(255,255,255,.45);
            line-height: 1.7; max-width: 320px; margin-bottom: 40px;
        }

        .feature-list { list-style: none; }
        .feature-list li {
            display: flex; align-items: center; gap: 12px;
            color: rgba(255,255,255,.55); font-size: .875rem;
            margin-bottom: 14px;
        }
        .feature-list li i {
            width: 32px; height: 32px; border-radius: 8px;
            background: rgba(0,188,212,.12); color: #00BCD4;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; flex-shrink: 0;
        }

        /* ── RIGHT PANEL (form) ── */
        .right-panel {
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 48px 40px;
            background: #fff;
        }

        .form-box { width: 100%; max-width: 400px; }

        .form-box .top-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(0,188,212,.08); color: #007b8a;
            padding: 5px 12px; border-radius: 20px;
            font-size: .75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 20px;
            border: 1px solid rgba(0,188,212,.2);
        }

        .form-box h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 2.2rem; letter-spacing: 1.5px;
            color: #1a1d2e; margin-bottom: 6px;
        }
        .form-box .sub {
            font-size: .875rem; color: #6b7280; margin-bottom: 32px;
        }

        /* Inputs */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: .78rem; font-weight: 700;
            color: #374151; margin-bottom: 8px;
            text-transform: uppercase; letter-spacing: .7px;
        }
        .input-wrap { position: relative; }
        .input-wrap .i-left {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: .85rem; pointer-events: none;
            transition: color .2s;
        }
        .input-wrap input {
            width: 100%; padding: 12px 42px;
            background: #f9fafb; border: 1.5px solid #e5e7eb;
            border-radius: 10px; color: #1a1d2e;
            font-family: 'DM Sans', sans-serif; font-size: .925rem;
            outline: none; transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .input-wrap input:focus {
            border-color: #00BCD4; background: #fff;
            box-shadow: 0 0 0 3px rgba(0,188,212,.1);
        }
        .input-wrap input:focus ~ .i-left { color: #00BCD4; }
        .input-wrap input::placeholder { color: #c4c9d4; }

        .eye-btn {
            position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: .85rem; padding: 4px;
            transition: color .2s;
        }
        .eye-btn:hover { color: #00BCD4; }

        /* Error */
        .alert-error {
            display: flex; align-items: flex-start; gap: 10px;
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 10px; padding: 12px 14px;
            color: #b91c1c; font-size: .875rem;
            margin-bottom: 22px;
            animation: shake .4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-5px); }
            40%,80%  { transform: translateX(5px); }
        }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: #fff; font-family: 'DM Sans', sans-serif;
            font-weight: 700; font-size: .95rem; letter-spacing: .3px;
            border: none; border-radius: 10px; cursor: pointer;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(0,188,212,.3);
            margin-top: 8px;
        }
        .btn-submit:hover { opacity: .93; box-shadow: 0 6px 22px rgba(0,188,212,.4); }
        .btn-submit:active { transform: scale(.98); }

        .form-footer {
            text-align: center; margin-top: 28px;
            font-size: .82rem; color: #9ca3af;
        }
        .form-footer a { color: #00BCD4; text-decoration: none; font-weight: 600; }

        /* Responsive */
        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { padding: 40px 24px; }
        }
    </style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
    <div class="panel-logo">
        <div class="panel-logo-icon"><i class="fas fa-dumbbell"></i></div>
        <span class="panel-logo-text">FitZone</span>
    </div>
    <div class="panel-content">
        <h1>Espace<br><span>Admin</span><br>FitZone</h1>
        <p>Gérez votre boutique, vos produits et votre catalogue depuis ce tableau de bord sécurisé.</p>
        <ul class="feature-list">
            <li><i class="fas fa-box"></i> Gestion des produits & stock</li>
            <li><i class="fas fa-tags"></i> Gestion des catégories</li>
            <li><i class="fas fa-shield-alt"></i> Accès sécurisé et privé</li>
            <li><i class="fas fa-user-cog"></i> Paramètres du compte admin</li>
        </ul>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
    <div class="form-box">
        <div class="top-tag"><i class="fas fa-lock"></i> Accès restreint</div>
        <h2>Connexion Admin</h2>
        <p class="sub">Entrez vos identifiants administrateur pour continuer.</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle" style="margin-top:2px;flex-shrink:0"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <div class="input-wrap">
                    <input type="email" id="email" name="email" required
                           placeholder="admin@ecommerce.dz" autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <i class="fas fa-envelope i-left"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password"
                           required placeholder="••••••••" autocomplete="current-password">
                    <i class="fas fa-lock i-left"></i>
                    <button type="button" class="eye-btn" onclick="togglePwd(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Accéder au tableau de bord
            </button>
        </form>

        <div class="form-footer">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Retour à la boutique</a>
        </div>
    </div>
</div>

<script>
function togglePwd(btn) {
    const input = btn.closest('.input-wrap').querySelector('input');
    const icon  = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}
</script>
</body>
</html>