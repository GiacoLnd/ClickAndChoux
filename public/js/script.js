
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

// Script AJAX for fav 
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

// Script AJAX for cart quantity
document.addEventListener('DOMContentLoaded', function () {
// --- BOUTONS "AUGMENTER" ---
    document.querySelectorAll('.increase-btn').forEach(button => {
        button.addEventListener('click', function () {
            const produitId = this.dataset.id;

            fetch(`/panier/increase-ajax/${produitId}`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour la quantité
                    const quantityElement = document.getElementById(`quantity-${produitId}`);
                    if (quantityElement) {
                        quantityElement.textContent = data.newQuantity;
                    }

                    // Mettre à jour le total de la ligne
                    const cartItem = button.closest('.cart-item');
                    if (cartItem) {
                        // Lecture du prix unitaire depuis data-price
                        const unitPrice = parseFloat(cartItem.dataset.price);
                        
                        // Calcul du nouveau prix de la ligne
                        const newLineTotal = unitPrice * data.newQuantity;

                        // Mise à jour de l'élément line-total
                        const lineTotalElement = document.getElementById(`line-total-${produitId}`);
                        if (lineTotalElement) {
                            lineTotalElement.textContent = newLineTotal.toFixed(2); 
                            // .toFixed(2) pour afficher deux décimales
                        }
                    }

                    // Mise à jour du total général si tu le gères côté JS
                    const totalElement = document.getElementById('montant-total');
                    if (totalElement && data.newTotal !== undefined) {
                        const totalArrondi = parseFloat(data.newTotal).toFixed(2); // .toFixed(2) pour afficher deux décimales
                        totalElement.textContent = `${totalArrondi} €`;
                    }

                    const navbarQty = document.getElementById('navbar-cart-quantity');
                    if (navbarQty && data.cartQuantity !== undefined) {
                        navbarQty.textContent = data.cartQuantity;
                    }
                }
            })
            .catch(error => console.error(error));
        });
    });

    // --- BOUTONS "DIMINUER" ---
    document.querySelectorAll('.decrease-btn').forEach(button => {
        button.addEventListener('click', function () {
            const produitId = this.dataset.id;

            fetch(`/panier/decrease-ajax/${produitId}`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP : ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mise à jour de la quantité
                    const quantityElement = document.getElementById(`quantity-${produitId}`);
                    if (quantityElement) {
                        quantityElement.textContent = data.newQuantity;
                    }
            
                    // Mise à jour du total par ligne
                    const cartItem = button.closest('.cart-item');
                    if (cartItem) {
                        // Lecture du prix unitaire depuis data-price
                        const unitPrice = parseFloat(cartItem.dataset.price);
                        
                        // Calcul du nouveau prix de la ligne
                        const newLineTotal = (unitPrice * data.newQuantity).toFixed(2);
                        
                        // Affichage dans l'élément line-total
                        const lineTotalElement = document.getElementById(`line-total-${produitId}`);
                        if (lineTotalElement) {
                            lineTotalElement.textContent = newLineTotal;
                        }
                    }
            
                    // Mise à jour du total général
                    const totalElement = document.getElementById('montant-total');
                    if (totalElement && data.newTotal !== undefined) {
                        const totalArrondi = parseFloat(data.newTotal).toFixed(2);
                        totalElement.textContent = `${totalArrondi} €`;
                    }

                    const navbarQty = document.getElementById('navbar-cart-quantity');
                    if (navbarQty && data.cartQuantity !== undefined) {
                        navbarQty.textContent = data.cartQuantity;
                    }
                }
            })
            
            .catch(error => console.error("Erreur lors de la requête Decrease AJAX :", error));
        });
    });
});

// Datatables scripts 

// User orders list dataTables
$(document).ready(function() {
    var table = $('#table').DataTable({
        responsive: true,
        scrollX: true,
        "paging": true,       // Active la pagination
        "searching": true,    // Active la recherche
        "ordering": true,  
        "order": [[2, "desc"]], // Trie par date (colonne 2) en ordre décroissant
        "info": false,         // Désactive les informations sur le nombre d'éléments
        "scrollY": "500px",
        "pagingType": "numbers", // Donne uniquement les numéros de pages
        "lengthChange": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json" // Traduction en français
        },
    });
});

// Script to zoom images in detail produit 

document.getElementById("image").addEventListener("click", function() {
    this.classList.toggle("zoom");
})


const image = document.getElementById("image")

document.getElementById("text-image").addEventListener("click", function() {
    image.classList.toggle("zoom")
})

// AJAX for live search bar in catalog

// Salé
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('search-input');
    const produitsList = document.getElementById('produits-list');

    searchInput.addEventListener('input', function() {
        const searchQuery = searchInput.value.trim();  // Récupère la valeur de la barre de recherche

        console.log("Recherche:", searchQuery); // Log de la valeur envoyée à chaque frappe

        // Si la recherche n'est pas vide
        if (searchQuery.length > 0) {
            fetch('/produit/salty/ajax?query=' + encodeURIComponent(searchQuery), {
                method: 'GET'
            })
            .then(response => response.json())  // Traite la réponse comme un JSON
            .then(data => {
                console.log("Réponse reçue:", data);  // Log de la réponse reçue du serveur

                produitsList.innerHTML = '';  // Réinitialiser la liste existante

                // Ajouter chaque produit à la liste de manière sécurisée
                data.produits.forEach(produit => {
                    const li = document.createElement('li');

                    const a = document.createElement('a');
                    a.setAttribute('href', '/produit/' + produit.id);

                    const img = document.createElement('img');
                    img.setAttribute('src', '/img/' + produit.image);
                    img.setAttribute('alt', produit.nomProduit);
                    img.classList.add('catalog-image');
                    a.appendChild(img);

                    const h2 = document.createElement('h2');
                    h2.textContent = produit.nomProduit;

                    const p = document.createElement('p');
                    p.textContent = produit.getTTC + '€';

                    li.appendChild(a);
                    li.appendChild(h2);
                    li.appendChild(p);

                    produitsList.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des produits:', error);
                produitsList.innerHTML = '<p class="error-message">Une erreur est survenue. Veuillez réessayer.</p>';
            });
        } else {
            // Si la recherche est vide, on récupère tous les produits
            fetch('/produit/salty/ajax', {
                method: 'GET'
            })
            .then(response => response.json())  // Traite la réponse comme un JSON
            .then(data => {
                produitsList.innerHTML = '';  // Réinitialiser la liste existante

                // Ajouter chaque produit à la liste de manière sécurisée
                data.produits.forEach(produit => {
                    const li = document.createElement('li');

                    const a = document.createElement('a');
                    a.setAttribute('href', '/produit/' + produit.id);

                    const img = document.createElement('img');
                    img.setAttribute('src', '/img/' + produit.image);
                    img.setAttribute('alt', produit.nomProduit);
                    img.classList.add('catalog-image');
                    a.appendChild(img);

                    const h2 = document.createElement('h2');
                    h2.textContent = produit.nomProduit;

                    const p = document.createElement('p');
                    p.textContent = produit.getTTC + '€';

                    li.appendChild(a);
                    li.appendChild(h2);
                    li.appendChild(p);

                    produitsList.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des produits:', error);
                produitsList.innerHTML = '<p class="error-message">Une erreur est survenue. Veuillez réessayer.</p>';
            });
        }
    });
});

// Sucré
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('search-input');
    const produitsList = document.getElementById('produits-list');
    const categoryType = document.body.getAttribute('data-category'); // Ajoute un attribut pour connaître la catégorie

    searchInput.addEventListener('input', function() {
        const searchQuery = searchInput.value.trim();  // Récupère la valeur de la barre de recherche

        console.log("Recherche:", searchQuery); // Log de la valeur envoyée à chaque frappe

        let url = '';
        if (categoryType === 'salty') {
            url = '/produit/salty/ajax?query=' + encodeURIComponent(searchQuery);
        } else if (categoryType === 'sweety') {
            url = '/produit/sweety/ajax?query=' + encodeURIComponent(searchQuery);
        }

        // Si la recherche n'est pas vide
        if (searchQuery.length > 0) {
            fetch(url, {
                method: 'GET'
            })
            .then(response => response.json())  // Traite la réponse comme un JSON
            .then(data => {
                console.log("Réponse reçue:", data);  // Log de la réponse reçue du serveur

                produitsList.innerHTML = '';  // Réinitialiser la liste existante

                // Ajouter chaque produit à la liste de manière sécurisée
                data.produits.forEach(produit => {
                    const li = document.createElement('li');

                    const a = document.createElement('a');
                    a.setAttribute('href', '/produit/' + produit.id);

                    const img = document.createElement('img');
                    img.setAttribute('src', '/img/' + produit.image);
                    img.setAttribute('alt', produit.nomProduit);
                    img.classList.add('catalog-image');
                    a.appendChild(img);

                    const h2 = document.createElement('h2');
                    h2.textContent = produit.nomProduit;

                    const p = document.createElement('p');
                    p.textContent = produit.getTTC + '€';

                    li.appendChild(a);
                    li.appendChild(h2);
                    li.appendChild(p);

                    produitsList.appendChild(li);
                });
            })
        } else {
            // Si la recherche est vide, on récupère tous les produits
            fetch(url, {
                method: 'GET'
            })
            .then(response => response.json())  // Traite la réponse comme un JSON
            .then(data => {
                produitsList.innerHTML = '';  // Réinitialiser la liste existante

                // Ajouter chaque produit à la liste de manière sécurisée
                data.produits.forEach(produit => {
                    const li = document.createElement('li');

                    const a = document.createElement('a');
                    a.setAttribute('href', '/produit/' + produit.id);

                    const img = document.createElement('img');
                    img.setAttribute('src', '/img/' + produit.image);
                    img.setAttribute('alt', produit.nomProduit);
                    img.classList.add('catalog-image');
                    a.appendChild(img);

                    const h2 = document.createElement('h2');
                    h2.textContent = produit.nomProduit;

                    const p = document.createElement('p');
                    p.textContent = produit.getTTC + '€';

                    li.appendChild(a);
                    li.appendChild(h2);
                    li.appendChild(p);

                    produitsList.appendChild(li);
                });
            })
        }
    });
});
