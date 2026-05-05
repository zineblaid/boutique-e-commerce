<?php
require_once "config/config.php";
require_once "config/produits_query.php";

echo "<h1>Test connexion + produits</h1>";

// Test connexion
echo "Connexion OK ✅<br><br>";

// Test récupération produits
$produits = getAllProduits($pdo);

if (empty($produits)) {
    echo "❌ Aucun produit trouvé";
} else {
    echo "✅ Produits trouvés :<br><br>";

    foreach ($produits as $p) {
        echo "Nom: " . $p['nom'] . "<br>";
        echo "Prix: " . $p['prix'] . " DA<br>";
        echo "Catégorie: " . $p['categorie_nom'] . "<br>";
        echo "----------------------<br>";
    }
}