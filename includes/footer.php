<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'fr';
$tf = [
    'fr' => ['liens'=>'Liens rapides','categories'=>'Catégories','contact'=>'Contact','droits'=>'Tous droits réservés'],
    'en' => ['liens'=>'Quick links',  'categories'=>'Categories', 'contact'=>'Contact', 'droits'=>'All rights reserved'],
    'ar' => ['liens'=>'روابط سريعة', 'categories'=>'الأقسام',   'contact'=>'اتصل بنا','droits'=>'جميع الحقوق محفوظة'],
];
$tx = $tf[$lang] ?? $tf['fr'];
?>
<footer>
    <div class="footer-grid">

        <!-- Brand -->
        <div class="footer-col">
            <h4>FitZone</h4>
            <p style="color:var(--gray);font-size:.88rem;line-height:1.75;max-width:220px;">
                La boutique sport numéro&nbsp;1 en Algérie. Vêtements, nutrition et accessoires pour tous les niveaux.
            </p>
            <div class="footer-social" style="margin-top:16px;">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>

        <!-- Liens rapides -->
        <div class="footer-col">
            <h4><?= $tx['liens'] ?></h4>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="boutique.php">Boutique</a></li>
                <li><a href="boutique.php?categorie=homme">Homme</a></li>
                <li><a href="boutique.php?categorie=femme">Femme</a></li>
                <li><a href="boutique.php?categorie=food">Nutrition</a></li>
            </ul>
        </div>

        <!-- Catégories -->
        <div class="footer-col">
            <h4><?= $tx['categories'] ?></h4>
            <ul>
                <li><a href="boutique.php?categorie=vetement">Vêtements</a></li>
                <li><a href="boutique.php?categorie=accessoire">Accessoires</a></li>
                <li><a href="boutique.php?categorie=tapis">Tapis & Sol</a></li>
                <li><a href="boutique.php?categorie=outil">Petits Outils</a></li>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
            <h4><?= $tx['contact'] ?></h4>
            <ul>
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--gray);font-size:.88rem;margin-bottom:10px;">
                    <i class="fas fa-map-marker-alt" style="color:var(--teal);margin-top:3px;flex-shrink:0;"></i>
                    Alger, Algérie
                </li>
                <li style="display:flex;gap:10px;align-items:center;color:var(--gray);font-size:.88rem;margin-bottom:10px;">
                    <i class="fas fa-envelope" style="color:var(--teal);flex-shrink:0;"></i>
                    fitzone@example.dz
                </li>
                <li style="display:flex;gap:10px;align-items:center;color:var(--gray);font-size:.88rem;">
                    <i class="fas fa-phone" style="color:var(--teal);flex-shrink:0;"></i>
                    +213 555 000 000
                </li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> FitZone &mdash; <?= $tx['droits'] ?>.</span>
        <div style="display:flex;gap:18px;">
            <a href="#" style="color:var(--gray);font-size:.82rem;">Confidentialité</a>
            <a href="#" style="color:var(--gray);font-size:.82rem;">CGV</a>
        </div>
    </div>
</footer>

