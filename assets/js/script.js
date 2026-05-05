function changeQty(delta) {
    const el = document.getElementById('qty-val');
    if (!el) return;
 
    let val = parseInt(el.textContent) + delta;
    if (val < 1)  val = 1;   // minimum 1
    if (val > 99) val = 99;  // maximum 99
    el.textContent = val;
 
    // Met à jour automatiquement le lien "Ajouter au panier"
    const btn = document.getElementById('add-to-cart');
    if (btn) {
        const url = new URL(btn.href, window.location.origin);
        url.searchParams.set('qty', val);
        btn.href = url.toString();
    }
}
 
/* ═══════════════════════════════════════════════
   2. CONFIRMATION avant suppression (admin)
   ═══════════════════════════════════════════════ */
function confirmDelete(msg) {
    return confirm(msg || 'Êtes-vous sûr de vouloir supprimer cet élément ?');
}
 
/* ═══════════════════════════════════════════════
   3. TOUT LE RESTE — au chargement de la page
   ═══════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
 
    /* ── 3a. Flash messages disparaissent après 3.5s ── */
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3500);
    });
 
    /* ── 3b. Lien actif dans la nav ── */
    document.querySelectorAll('nav a').forEach(link => {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });
 
    /* ── 3c. Ombre du header au scroll ── */
    const header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.style.boxShadow = window.scrollY > 50
                ? '0 4px 30px rgba(0,0,0,0.6)'
                : 'none';
        });
    }
 
    /* ── 3d. Animation apparition au scroll ──
       Les cartes produits et catégories apparaissent
       progressivement quand on scrolle vers elles     */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target); // stop observer après apparition
            }
        });
    }, { threshold: 0.08 });
 
    document.querySelectorAll('.product-card, .cat-card').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(24px)';
        // délai progressif : 0s, 0.06s, 0.12s, 0.18s...
        el.style.transition = `opacity 0.4s ease ${i * 0.06}s, transform 0.4s ease ${i * 0.06}s`;
        observer.observe(el);
    });
 
    /* ── 3e. Tabs filtre catégorie (boutique.php) ── */
    document.querySelectorAll('.cat-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const cat = tab.dataset.cat; // data-cat="homme"
            const url = new URL(window.location.href);
            if (cat === 'all') {
                url.searchParams.delete('categorie');
            } else {
                url.searchParams.set('categorie', cat);
            }
            window.location.href = url.toString();
        });
    });
 
    /* ── 3f. Recherche : soumettre au Enter ── */
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const url = new URL(window.location.href);
                url.searchParams.set('q', searchInput.value.trim());
                window.location.href = url.toString();
            }
        });
    }
 
    /* ── 3g. Zoom image sur la page produit.php ── */
    const mainImg = document.getElementById('main-img');
    if (mainImg) {
        mainImg.style.transition = 'transform 0.4s ease';
        mainImg.addEventListener('mouseenter', () => {
            mainImg.style.transform = 'scale(1.05)';
        });
        mainImg.addEventListener('mouseleave', () => {
            mainImg.style.transform = 'scale(1)';
        });
    }
 
    /* ── 3h. Panier : soumettre form si quantité change ── */
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form) form.submit();
        });
    });
 
});
 
