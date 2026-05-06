<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// ── Identifiants admin (à changer selon tes besoins) ──
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'Admin1234');   // mot de passe en clair ici, comparé via password_verify si hashé

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['user_id']  = 1;
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Identifiants incorrects. Réessayez.';
    }
}

// Si déjà connecté admin → rediriger directement
if (!empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — FitZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: #0d0d1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,188,212,.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,188,212,.07) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0%   { transform: translateY(0); }
            100% { transform: translateY(40px); }
        }

        /* Glow blobs */
        .blob {
            position: fixed; border-radius: 50%; filter: blur(80px); opacity: .25; pointer-events: none;
        }
        .blob-1 { width: 400px; height: 400px; background: #00BCD4; top: -100px; right: -100px; }
        .blob-2 { width: 300px; height: 300px; background: #7c3aed; bottom: -80px; left: -80px; }

        /* Card */
        .login-card {
            position: relative; z-index: 10;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 48px 44px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 32px 64px rgba(0,0,0,.5);
            animation: slideUp .5s ease both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo / brand */
        .brand {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 32px;
        }
        .brand-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #fff;
            box-shadow: 0 4px 16px rgba(0,188,212,.4);
        }
        .brand-text h1 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.6rem; letter-spacing: 2px; color: #fff;
            line-height: 1;
        }
        .brand-text span {
            font-size: .75rem; color: rgba(255,255,255,.45);
            text-transform: uppercase; letter-spacing: 1px;
        }

        h2 {
            font-size: 1.1rem; font-weight: 600;
            color: rgba(255,255,255,.85);
            margin-bottom: 6px;
        }
        .sub {
            font-size: .85rem; color: rgba(255,255,255,.35);
            margin-bottom: 28px;
        }

        /* Form */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block; font-size: .82rem; font-weight: 600;
            color: rgba(255,255,255,.55); margin-bottom: 8px;
            text-transform: uppercase; letter-spacing: .8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,.25); font-size: .9rem; pointer-events: none;
            transition: color .2s;
        }
        .input-wrap input {
            width: 100%; padding: 12px 14px 12px 42px;
            background: rgba(255,255,255,.06);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 10px;
            color: #fff; font-family: 'Outfit', sans-serif; font-size: .95rem;
            outline: none; transition: border-color .2s, background .2s;
        }
        .input-wrap input::placeholder { color: rgba(255,255,255,.2); }
        .input-wrap input:focus {
            border-color: #00BCD4;
            background: rgba(0,188,212,.06);
        }
        .input-wrap input:focus + i,
        .input-wrap:focus-within i { color: #00BCD4; }

        /* Eye toggle */
        .eye-toggle {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,.25); cursor: pointer; font-size: .9rem;
            background: none; border: none; padding: 0;
            transition: color .2s;
        }
        .eye-toggle:hover { color: #00BCD4; }

        /* Error */
        .alert-error {
            background: rgba(229,57,53,.12);
            border: 1px solid rgba(229,57,53,.3);
            border-radius: 8px;
            padding: 11px 14px;
            color: #ef5350;
            font-size: .88rem;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        /* Submit */
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: #fff; font-family: 'Outfit', sans-serif;
            font-weight: 700; font-size: 1rem;
            border: none; border-radius: 10px; cursor: pointer;
            transition: opacity .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 20px rgba(0,188,212,.35);
            margin-top: 8px;
        }
        .btn-login:hover  { opacity: .92; }
        .btn-login:active { transform: scale(.98); }

        /* Back link */
        .back-link {
            display: block; text-align: center; margin-top: 22px;
            font-size: .83rem; color: rgba(255,255,255,.3);
            text-decoration: none; transition: color .2s;
        }
        .back-link:hover { color: #00BCD4; }
    </style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<div class="login-card">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="brand-text">
            <h1>FitZone</h1>
            <span>Espace Administration</span>
        </div>
    </div>

    <h2>Connexion Admin</h2>
    <p class="sub">Accès réservé aux administrateurs autorisés.</p>

    <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Identifiant</label>
            <div class="input-wrap">
                <input type="text" id="username" name="username"
                       placeholder="admin" autocomplete="username" required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <i class="fas fa-user"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password"
                       placeholder="••••••••" autocomplete="current-password" required>
                <i class="fas fa-lock"></i>
                <button type="button" class="eye-toggle" onclick="togglePwd(this)" tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Accéder au tableau de bord
        </button>
    </form>

    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Retour à la boutique
    </a>
</div>

<script>
function togglePwd(btn) {
    const input = btn.closest('.input-wrap').querySelector('input');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>