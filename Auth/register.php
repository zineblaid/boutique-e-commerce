<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

$error   = "";
$success = "";
$name    = "";
$email   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    // ── Validation ──
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (strlen($name) < 2) {
        // ✅ FIX : nom trop court (ex: "a")
        $error = "Le nom doit contenir au moins 2 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // ── Vérifier si l'email existe déjà ──
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $sql  = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $email, $hashed);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $_SESSION["user_id"] = $user_id;
                $_SESSION["email"]   = $email;
                $_SESSION["name"]    = $name;

                header("Location: ../index.php");
                exit();
            } else {
                $error = "Une erreur est survenue. Veuillez réessayer.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - FitZone</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        /* ── Navbar ── */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 40px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .nav-logo {
            width: 38px;
            height: 38px;
            background: #0bbcd4;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .nav-links { display: flex; gap: 28px; list-style: none; }
        .nav-links a { text-decoration: none; color: #555; font-size: 0.95rem; }
        .nav-links a:hover { color: #0bbcd4; }

        .nav-actions { display: flex; align-items: center; gap: 18px; }
        .nav-actions a { text-decoration: none; font-size: 0.95rem; }
        .nav-actions a:not(.btn-signup) { color: #555; }

        /* ✅ FIX : lien actif pour Sign up */
        .btn-signup {
            background: #0bbcd4;
            color: #fff !important;
            padding: 9px 22px;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-signup:hover { background: #09a8be; }
        .cart-icon { font-size: 1.2rem; cursor: pointer; }

        /* ── Card ── */
        .page-wrapper {
            min-height: calc(100vh - 68px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }

        .signup-card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 44px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        .signup-card h1 {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .signup-card p.subtitle {
            text-align: center;
            color: #777;
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error   { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-success { background: #f0fff4; color: #27ae60; border: 1px solid #b2dfdb; }

        /* ── Form ── */
        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #dde2e8;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            color: #333;
            transition: border-color 0.2s;
        }
        input::placeholder { color: #aab; }
        input:focus { border-color: #0bbcd4; }

        /* Indicateur de force du mot de passe */
        .password-strength {
            margin-top: 6px;
            height: 4px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }

        .strength-weak   { width: 33%; background: #e74c3c; }
        .strength-medium { width: 66%; background: #f39c12; }
        .strength-strong { width: 100%; background: #27ae60; }

        .strength-label {
            font-size: 0.78rem;
            margin-top: 4px;
            color: #888;
            min-height: 16px;
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            background: #0bbcd4;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.2s;
        }
        .btn-register:hover { background: #09a8be; }

        .login-link {
            text-align: center;
            margin-top: 22px;
            font-size: 0.9rem;
            color: #777;
        }
        .login-link a {
            color: #0bbcd4;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<nav>
    <a href="../index.php" class="nav-brand">
        <div class="nav-logo">FZ</div>
        FitZone
    </a>
    <ul class="nav-links">
        <li><a href="../index.php">Home</a></li>
        <li><a href="../shop.php">Shop</a></li>
    </ul>
    <div class="nav-actions">
        <span class="cart-icon">🛒</span>
        <a href="login.php">Login</a>
        <a href="register.php" class="btn-signup">Sign up</a>
    </div>
</nav>

<div class="page-wrapper">
    <div class="signup-card">
        <h1>Create your account</h1>
        <p class="subtitle">Join FitZone and start your fitness journey</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ✅ FIX : bloc $success maintenant affiché -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="name">Full name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Maya Chen"
                    value="<?= htmlspecialchars($name) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="At least 8 characters"
                    oninput="checkStrength(this.value)"
                    required
                >
                <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                </div>
                <div class="strength-label" id="strength-label"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn-register">Sign up</button>
        </form>

        <p class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</div>

<script>
    function checkStrength(value) {
        const bar   = document.getElementById("strength-bar");
        const label = document.getElementById("strength-label");
        bar.className = "password-strength-bar";

        if (value.length === 0) {
            label.textContent = "";
            return;
        }

        const strong = value.length >= 12
            && /[A-Z]/.test(value)
            && /[0-9]/.test(value)
            && /[^a-zA-Z0-9]/.test(value);

        const medium = value.length >= 8
            && (/[A-Z]/.test(value) || /[0-9]/.test(value));

        if (strong) {
            bar.classList.add("strength-strong");
            label.textContent  = "✅ Mot de passe fort";
            label.style.color  = "#27ae60";
        } else if (medium) {
            bar.classList.add("strength-medium");
            label.textContent  = "⚠️ Mot de passe moyen";
            label.style.color  = "#f39c12";
        } else {
            bar.classList.add("strength-weak");
            label.textContent  = "❌ Mot de passe faible";
            label.style.color  = "#e74c3c";
        }
    }
</script>

</body>
</html>