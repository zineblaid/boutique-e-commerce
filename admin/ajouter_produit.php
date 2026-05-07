<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/produits_query.php';

// Protection admin cohérente
if (empty($_SESSION['admin_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php'); // Retrait du ../auth/
    exit;
}

$categories = getAllCategories($pdo);
$error  = '';
$mode   = 'add'; // 'add' | 'edit'
$produit = [
    'id' => 0, 'nom' => '', 'description' => '',
    'prix' => '', 'stock' => '', 'image' => '', 'categorie_id' => '',
];

// Mode édition ?
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($edit_id > 0) {
    $found = getProduitById($pdo, $edit_id);
    if ($found) {
        $produit = $found;
        $mode    = 'edit';
    }
}

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_post   = (int)($_POST['id'] ?? 0);
    $nom       = trim($_POST['nom'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $prix      = (float)str_replace(',', '.', $_POST['prix'] ?? '');
    $stock     = (int)($_POST['stock'] ?? 0);
    $image     = trim($_POST['image'] ?? 'images/default.jpg');
    $cat_id    = (int)($_POST['categorie_id'] ?? 0);
    $action    = $_POST['action'] ?? 'add';

    // Validation
    if (empty($nom))   { $error = 'Le nom du produit est obligatoire.'; }
    elseif ($prix <= 0){ $error = 'Le prix doit être supérieur à 0.'; }
    elseif ($cat_id <= 0){ $error = 'Veuillez sélectionner une catégorie.'; }
    else {
        if ($action === 'edit' && $id_post > 0) {
            updateProduit($pdo, $id_post, $nom, $desc, $prix, $stock, $image, $cat_id);
            $_SESSION['flash_success'] = "Produit « $nom » mis à jour avec succès.";
        } else {
            addProduit($pdo, $nom, $desc, $prix, $stock, $image, $cat_id);
            $_SESSION['flash_success'] = "Produit « $nom » ajouté avec succès.";
        }
        header('Location: admin.php');
        exit;
    }

    // Repopuler le formulaire en cas d'erreur
    $produit = array_merge($produit, compact('nom', 'desc', 'prix', 'stock', 'image', 'cat_id'));
}

$page_title = $mode === 'edit' ? 'Modifier le produit' : 'Ajouter un produit';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — FitZone Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f4f6f8; padding-top: 64px; }

        .admin-topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: #1a1a2e; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px;
        }
        .admin-topbar .brand { color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
        .admin-topbar .brand span { background: #00BCD4; padding: 4px 8px; border-radius: 6px; font-size: .8rem; }
        .admin-topbar .topbar-links a { color: rgba(255,255,255,.7); font-size: .9rem; text-decoration: none; transition: color .2s; margin-left: 20px; }
        .admin-topbar .topbar-links a:hover { color: #00BCD4; }

        .form-container { max-width: 740px; margin: 0 auto; padding: 40px 24px; }

        .form-card {
            background: #fff; border-radius: 14px;
            border: 1px solid #e2e6ea; padding: 36px 40px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
        }

        .form-card h1 { font-family: 'Bebas Neue',cursive; font-size: 2rem; letter-spacing: 1px; color: #1a1a2e; margin-bottom: 6px; }
        .form-card .sub { color: #6b7280; font-size: .9rem; margin-bottom: 28px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: .88rem; margin-bottom: 7px; color: #374151; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 11px 14px;
            background: #f9fafb; border: 1.5px solid #e2e6ea;
            border-radius: 8px; color: #1a1a2e;
            font-family: 'Outfit', sans-serif; font-size: .93rem;
            outline: none; transition: border-color .2s, background .2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: #00BCD4; background: #fff; }
        .form-group textarea { resize: vertical; min-height: 110px; }
        .form-group small { color: #9ca3af; font-size: .78rem; margin-top: 4px; display: block; }

        /* Prévisualisation image */
        #img-preview {
            max-width: 100%; max-height: 140px; border-radius: 8px;
            border: 1px solid #e2e6ea; margin-top: 10px;
            display: none; object-fit: cover;
        }

        .form-actions { display: flex; gap: 14px; margin-top: 8px; }
        .btn-save {
            display: inline-flex; align-items: center; gap: 8px;
            background: #00BCD4; color: #fff; padding: 12px 30px;
            border-radius: 8px; font-weight: 600; font-size: .95rem;
            border: none; cursor: pointer; transition: background .2s;
        }
        .btn-save:hover { background: #0097A7; }
        .btn-cancel {
            display: inline-flex; align-items: center; gap: 8px;
            background: transparent; color: #6b7280; padding: 12px 24px;
            border-radius: 8px; font-weight: 600; font-size: .95rem;
            border: 1.5px solid #e2e6ea; text-decoration: none; transition: all .2s;
        }
        .btn-cancel:hover { border-color: #00BCD4; color: #00BCD4; }

        @media(max-width:600px){ .form-row { grid-template-columns: 1fr; } .form-card { padding: 24px 18px; } }
    </style>
</head>
<body>

<div class="admin-topbar">
    <div class="brand"><span>ADMIN</span> FitZone Dashboard</div>
    <div class="topbar-links">
        <a href="admin.php"><i class="fas fa-arrow-left"></i> Retour</a>
        <a href="../index.php"><i class="fas fa-store"></i> Boutique</a>
    </div>
</div>

<div class="form-container">
    <div class="form-card">
        <h1><?= $page_title ?></h1>
        <p class="sub">Remplissez tous les champs marqués comme obligatoires (*).</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="id"     value="<?= $produit['id'] ?>">
                <input type="hidden" name="action" value="edit">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <!-- Nom -->
            <div class="form-group">
                <label for="nom">Nom du produit *</label>
                <input type="text" id="nom" name="nom" required
                       placeholder="Ex: Whey Protein Chocolat 1kg"
                       value="<?= htmlspecialchars($produit['nom']) ?>">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Décrivez le produit..."><?= htmlspecialchars($produit['description']) ?></textarea>
            </div>

            <!-- Prix + Stock -->
            <div class="form-row">
                <div class="form-group">
                    <label for="prix">Prix (DA) *</label>
                    <input type="number" id="prix" name="prix" required
                           step="0.01" min="0" placeholder="0.00"
                           value="<?= $produit['prix'] ?>">
                </div>
                <div class="form-group">
                    <label for="stock">Stock *</label>
                    <input type="number" id="stock" name="stock" required
                           min="0" placeholder="0"
                           value="<?= $produit['stock'] ?>">
                </div>
            </div>

            <!-- Catégorie -->
            <div class="form-group">
                <label for="categorie_id">Catégorie *</label>
                <select id="categorie_id" name="categorie_id" required>
                    <option value="">— Sélectionner une catégorie —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= ($produit['categorie_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label for="image">Chemin de l'image</label>
                <input type="text" id="image" name="image"
                       placeholder="images/mon-produit.jpg"
                       value="<?= htmlspecialchars($produit['image']) ?>"
                       oninput="previewImg(this.value)">
                <small>Chemin relatif depuis la racine du projet (ex: images/whey.jpg) ou URL complète.</small>
                <img id="img-preview" src="" alt="Aperçu">
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    <?= $mode === 'edit' ? 'Mettre à jour' : 'Ajouter le produit' ?>
                </button>
                <a href="admin.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImg(src) {
    const img = document.getElementById('img-preview');
    if (!src) { img.style.display = 'none'; return; }
    img.src = src.startsWith('http') ? src : '../' + src;
    img.style.display = 'block';
    img.onerror = () => { img.style.display = 'none'; };
}
// Initialiser l'aperçu si mode édition
window.addEventListener('DOMContentLoaded', () => {
    const val = document.getElementById('image').value;
    if (val) previewImg(val);
});
</script>
</body>
</html>