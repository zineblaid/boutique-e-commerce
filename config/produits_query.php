<?php

//  produits_query.php – Toutes les requêtes liées aux produits

//  Utilisation dans les autres pages :
//      require_once 'config/config.php';
//      require_once 'config/produits_query.php';

//  1. RÉCUPÉRER TOUS LES PRODUITS
//     Retourne tous les produits avec le nom de leur catégorie.

function getAllProduits(PDO $pdo): array
{
    $sql = "SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
            FROM produits p
            JOIN categories c ON p.categorie_id = c.id
            ORDER BY p.created_at DESC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


//  2. RÉCUPÉRER LES PRODUITS PAR CATÉGORIE (slug)
//     Exemple : getProduitsByCategorie($pdo, 'nutrition')

function getProduitsByCategorie(PDO $pdo, string $slug): array
{
    $sql = "SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
            FROM produits p
            JOIN categories c ON p.categorie_id = c.id
            WHERE c.slug = :slug
            ORDER BY p.nom ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetchAll();
}


//  3. RÉCUPÉRER UN SEUL PRODUIT PAR SON ID
//     Utilisé par la page detail_produit.php

function getProduitById(PDO $pdo, int $id): array|false
{
    $sql = "SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
            FROM produits p
            JOIN categories c ON p.categorie_id = c.id
            WHERE p.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}


//  4. RECHERCHER DES PRODUITS (nom ou description)
//     Utilisé par la barre de recherche du frontend
//     Exemple : searchProduits($pdo, 'whey')

function searchProduits(PDO $pdo, string $motCle): array
{
    $like = '%' . $motCle . '%';

    $sql = "SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
            FROM produits p
            JOIN categories c ON p.categorie_id = c.id
            WHERE p.nom LIKE :motcle
               OR p.description LIKE :motcle
            ORDER BY p.nom ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':motcle' => $like]);
    return $stmt->fetchAll();
}


//  5. FILTRER : catégorie + prix min/max + mot-clé (combiné)
//     Tous les paramètres sont optionnels (null = ignoré)

function filterProduits(
    PDO    $pdo,
    ?string $slug     = null,   // slug de catégorie
    ?float  $prixMin  = null,
    ?float  $prixMax  = null,
    ?string $motCle   = null
): array {
    $params = [];
    $conditions = [];

    $sql = "SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
            FROM produits p
            JOIN categories c ON p.categorie_id = c.id
            WHERE 1=1";

    if ($slug !== null) {
        $conditions[] = "c.slug = :slug";
        $params[':slug'] = $slug;
    }
    if ($prixMin !== null) {
        $conditions[] = "p.prix >= :prixMin";
        $params[':prixMin'] = $prixMin;
    }
    if ($prixMax !== null) {
        $conditions[] = "p.prix <= :prixMax";
        $params[':prixMax'] = $prixMax;
    }
    if ($motCle !== null && $motCle !== '') {
        $conditions[] = "(p.nom LIKE :motcle OR p.description LIKE :motcle)";
        $params[':motcle'] = '%' . $motCle . '%';
    }

    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY p.prix ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


//  6. RÉCUPÉRER TOUTES LES CATÉGORIES
//     Utilisé pour afficher le menu / filtre par catégorie

function getAllCategories(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom ASC");
    return $stmt->fetchAll();
}


//  7. AJOUTER UN PRODUIT (utilisé par admin)

function addProduit(
    PDO    $pdo,
    string $nom,
    string $description,
    float  $prix,
    int    $stock,
    string $image,
    int    $categorieId
): bool {
    $sql = "INSERT INTO produits (nom, description, prix, stock, image, categorie_id)
            VALUES (:nom, :description, :prix, :stock, :image, :categorie_id)";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':nom'          => $nom,
        ':description'  => $description,
        ':prix'         => $prix,
        ':stock'        => $stock,
        ':image'        => $image,
        ':categorie_id' => $categorieId,
    ]);
}


//  8. MODIFIER UN PRODUIT (utilisé par admin)

function updateProduit(
    PDO    $pdo,
    int    $id,
    string $nom,
    string $description,
    float  $prix,
    int    $stock,
    string $image,
    int    $categorieId
): bool {
    $sql = "UPDATE produits
            SET nom = :nom,
                description = :description,
                prix = :prix,
                stock = :stock,
                image = :image,
                categorie_id = :categorie_id
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':nom'          => $nom,
        ':description'  => $description,
        ':prix'         => $prix,
        ':stock'        => $stock,
        ':image'        => $image,
        ':categorie_id' => $categorieId,
        ':id'           => $id,
    ]);
}


//  9. SUPPRIMER UN PRODUIT (utilisé par admin)
/
function deleteProduit(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM produits WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}


//  10. COMPTER LES PRODUITS PAR CATÉGORIE
//      Utile pour afficher un badge sur les boutons de filtre

function countProduitsParCategorie(PDO $pdo): array
{
    $sql = "SELECT c.nom, c.slug, COUNT(p.id) AS total
            FROM categories c
            LEFT JOIN produits p ON p.categorie_id = c.id
            GROUP BY c.id
            ORDER BY c.nom ASC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}