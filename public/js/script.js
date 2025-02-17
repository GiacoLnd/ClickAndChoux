
// script for burger menu

document.addEventListener('DOMContentLoaded', () => {
    const burgerButton = document.getElementById('burger-button');
    const burgerIcon = document.getElementById('burger-icon');
    const navbarList = document.getElementById('navbar-list');

    burgerButton.addEventListener('click', () => {
        navbarList.classList.toggle('active');
        if (navbarList.classList.contains('active')) {
            burgerIcon.classList.remove('fa-bars');
            burgerIcon.classList.add('fa-xmark');
        } else {
            burgerIcon.classList.remove('fa-xmark');
            burgerIcon.classList.add('fa-bars');
        }
    });
});

//script for fav 
document.addEventListener('DOMContentLoaded', function () {
    // Liste des URLs où l'AJAX est autorisé
    const urlsCatalogues = [
        '/produit/salty',
        '/produit/sweety',
        '/favoris/page',
    ];

    // Vérifie si l'URL actuelle correspond à un catalogue
    if (!urlsCatalogues.includes(window.location.pathname)) {
        console.log("AJAX bloqué : URL non autorisée.");
        return;
    }

    console.log("AJAX activé : Page catalogue détectée.");

    fetch('/favoris/liste', { credentials: 'include' }) 
        .then(response => response.json())
        .then(data => {
            if (data.favoris) {
                document.querySelectorAll('.favori-btn').forEach(button => {
                    const produitId = button.dataset.id;
                    if (data.favoris.includes(parseInt(produitId))) {
                        button.classList.add('favori-active');
                    }
                });
            }
        });

    // Gérer l'ajout et la suppression des favoris
    document.querySelectorAll('.favori-btn').forEach(button => {
        button.addEventListener('click', function () {
            const produitId = this.dataset.id;
            const isFavori = this.classList.contains('favori-active');
            const url = isFavori ? `/favoris/supprimer/${produitId}` : `/favoris/ajouter/${produitId}`;
            const method = isFavori ? 'DELETE' : 'POST';

            fetch(url, {
                method: method,
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => { 
                if (data.message.includes('ajouté')) {
                    this.classList.add('favori-active');
                } else if (data.message.includes('retiré')) {
                    this.classList.remove('favori-active');

                    const productElement = this.closest('.favori-item');  // Trouve l'élément parent : .favori-item
                    if (productElement) {
                        productElement.remove();  // Retirer l'élément du DOM 
                    }
                }
            });
        });
    });
});










