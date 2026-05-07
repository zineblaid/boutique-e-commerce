<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

$error   = "";
$success = "";
$email   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        $sql  = "SELECT * FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["email"]   = $user["email"];
            $_SESSION["name"]    = $user["name"] ?? "";

            header("Location: ../index.php");
            exit();
        } else {
            sleep(1);
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FitZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', -apple-system, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        /* ── Login card ── */
        .page-wrapper {
            min-height: calc(100vh - 68px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 44px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        .login-card h1 {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .login-card p.subtitle {
            text-align: center;
            color: #777;
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error   { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6cb; }
        .alert-success { background: #f0fff4; color: #27ae60; border: 1px solid #b2dfdb; }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #dde2e8;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #f0f4f8;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
            color: #333;
            font-family: 'Outfit', sans-serif;
        }
        input::placeholder { color: #aab; }
        input:focus { border-color: #0bbcd4; background: #fff; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #0bbcd4;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.2s;
        }
        .btn-login:hover { background: #09a8be; }

        .signup-link {
            text-align: center;
            margin-top: 22px;
            font-size: 0.9rem;
            color: #777;
        }
        .signup-link a {
            color: #0bbcd4;
            text-decoration: none;
            font-weight: 600;
        }
        .signup-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="page-wrapper">
    <div class="login-card">
        <h1>Welcome back</h1>
        <p class="subtitle">Log in to your account to continue</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
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
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn-login">Log in</button>
        </form>

        <p class="signup-link">
            Don't have an account? <a href="./register.php">Sign up</a>
        </p>
    </div>
</div>

</body>
</html>