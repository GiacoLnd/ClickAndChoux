
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

// AJAX for live search bar in catalog
// Salé
document.addEventListener("DOMContentLoaded", function() {
    const urlSalty = [
        '/produit/salty',
    ];

    if (!urlSalty.includes(window.location.pathname)) {
        return;
    }

    const searchInput = document.getElementById('search-input');
    const produitsList = document.getElementById('produits-list');

    searchInput.addEventListener('input', function() {
        const searchQuery = searchInput.value.trim();

        if (searchQuery.length > 0) {
            fetch('/produit/salty/ajax?query=' + encodeURIComponent(searchQuery), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);

                    produitsList.innerHTML = '';  // Vide la liste des produits

                    // Si aucun produit n'est trouvé
                    if (data.produits.length === 0) {
                        const noProductMessage = document.createElement('p');
                        noProductMessage.textContent = 'Aucun produit trouvé';
                        noProductMessage.classList.add('grid-text');
                        produitsList.appendChild(noProductMessage);
                    } else {
                        // Affiche les produits trouvés
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
                    }
                } catch (error) {
                    console.error('Erreur lors du parsing JSON:', error);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
            });
        } else {
            // Si la recherche est vide, redirige vers la page principale sans filtres
            window.location.href = '/produit/salty';
        }
    });
});



// Sucré
document.addEventListener("DOMContentLoaded", function() {
    // Liste des URLs où l'AJAX est autorisé
    const urlsSweety = [
        '/produit/sweety',
    ];
    
    // Vérifie si l'URL actuelle correspond à un catalogue
    if (!urlsSweety.includes(window.location.pathname)) {
        return;
    }

    const searchInput = document.getElementById('search-bar');
    const produitsList = document.getElementById('produits-list');

    searchInput.addEventListener('input', function() {
        const searchQuery = searchInput.value.trim();

        if (searchQuery.length > 0) {
            fetch('/produit/sweety/ajax?query=' + encodeURIComponent(searchQuery), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);

                    produitsList.innerHTML = '';  // Vide la liste existante

                    // Si aucun produit n'est trouvé
                    if (data.produits.length === 0) {
                        const noProductMessage = document.createElement('p');
                        noProductMessage.textContent = 'Aucun produit trouvé';
                        noProductMessage.classList.add('grid-text');
                        produitsList.appendChild(noProductMessage);
                    } else {
                        // Affiche les produits trouvés
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
                    }
                } catch (error) {
                    console.error('Erreur lors du parsing JSON:', error);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
            });
        } else {
            // Si la recherche est vide, redirige vers la page principale sans filtres
            window.location.href = '/produit/sweety';  // Rediriger vers la page sans perdre la position
        }
    });
});


//Alergen filter 
// //sweety
// document.addEventListener('DOMContentLoaded', function() {
//     const searchBar = document.getElementById('search-bar');
//     const searchButton = document.getElementById('search-button');
    
//     searchButton.addEventListener('click', function() {
//         const searchQuery = searchBar.value;
//         window.location.href = `/produit/sweety?query=${searchQuery}`;
//     });
// });

// //salty
// document.addEventListener('DOMContentLoaded', function() {
//     const searchBar = document.getElementById('search-bar');
//     const searchButton = document.getElementById('search-button');
    
//     searchButton.addEventListener('click', function() {
//         const searchQuery = searchBar.value;
//         window.location.href = `/produit/salty?query=${searchQuery}`;
//     });
// });

// Script AJAX for cart quantity
document.addEventListener('DOMContentLoaded', function () {
    const urlsSweety = [
        '/panier',
    ];
    
    // Vérifie si l'URL actuelle correspond à un catalogue
    if (!urlsSweety.includes(window.location.pathname)) {
        return;
    }
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


// Sidepannel for catalog filter 
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifie si l'URL contient "/catalogue"
        const urlsCatalogs = [
            '/produit/salty',
            '/produit/sweety',
        ];
        
        // Vérifie si URL correspond à un catalogue
        if (urlsCatalogs.includes(window.location.pathname)) {
            var sidePanel = document.getElementById("sidePanel");
            var toggleFilterPanelBtn = document.getElementById("toggleFilterPanelBtn");
            var catalogContainer = document.querySelector(".catalog-container");
    
            // Ouvrir/Fermer le side panel
            toggleFilterPanelBtn.onclick = function() {
                sidePanel.classList.toggle("open");
                catalogContainer.classList.toggle("shifted");
            };
    
            // Ferme le side panel si clic en dehors
            document.addEventListener("click", function(event) {
                if (sidePanel.classList.contains("open") && !sidePanel.contains(event.target) && event.target !== toggleFilterPanelBtn) {
                    sidePanel.classList.remove("open");
                    catalogContainer.classList.remove("shifted");
                }
            });
        }
    });


// Datatables scripts 

// User orders list dataTables
$(document).ready(function() {
    var table = $('.dataTable').DataTable({
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
        return;
    }

    fetch('/favoris/liste', { credentials: 'include',headers: { 'Accept': 'application/json' } }) 
    .then(response => {
        if (!response.ok) { 
            if (response.status === 401) {
                console.warn('Utilisateur non connecté');
                return null;
            }
            throw new Error('Erreur réseau');
        }
        return response.json();
    })
    .then(data => {
        if (data && data.favoris) {
            document.querySelectorAll('.favori-btn').forEach(button => {
                const produitId = button.dataset.id;
                if (data.favoris.includes(parseInt(produitId))) {
                    button.classList.add('favori-active');
                }
            });
        }
    })

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
                        productElement.remove();  // Retire l'élément du DOM 
                    }
                }
            });
        });
    });
});

// Return top button
const retourHaut = document.getElementById('retour-haut');

retourHaut.addEventListener('click', function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Function handling new allergens integration in add product and update product
document.addEventListener('DOMContentLoaded', function() {
    const addButton = document.getElementById('add-allergen-button');
    const allergenCollection = document.getElementById('form_newAllergenes'); // ID du form newAllergene
    
    const prototype = allergenCollection.dataset.prototype;

    // Lorsqu'on clique sur le bouton d'ajout
    addButton.addEventListener('click', function() {
        const newItem = prototype.replace(/__name__/g, allergenCollection.children.length); // Modifie __name__ par index de chaque proto
        const div = document.createElement('div');
        div.innerHTML = newItem;
        div.classList.add('flex-row-center')


        // Ajout du bouton de suppression
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.classList.add('delete-allergen');
        deleteButton.classList.add('bubblegum-link');
        deleteButton.innerHTML = '<i class="fa-solid fa-minus"></i>';

        // Evènement de suppression du proto
        deleteButton.addEventListener('click', function() {
            allergenCollection.removeChild(div);
        });

        // Bouton de suppression dans div
        div.appendChild(deleteButton);

        // Ajout du nouvel élément dans collection
        allergenCollection.appendChild(div);
    });

    // Ecoute de l'évènement de click sur le bouton de suppression
    allergenCollection.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('delete-allergen')) {
            // Trouve l'élément à supprimer
            const allergenItem = event.target.closest('.allergen-item');
            allergenCollection.removeChild(allergenItem);
        }
    });
});

// AJAX for edit comment
document.addEventListener('DOMContentLoaded', function () {
    // Bouton de modification du commentaire
    const editButtons = document.querySelectorAll('.btn-edit');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.getAttribute('data-comment-id');
            const slug = this.getAttribute('data-slug');
            
            // Obtention du formulaire de modification
            fetch(`/produit/${slug}/modifier-commentaire/${commentId}`)
                .then(response => response.text())
                .then(data => {
                    // Injection le formulaire dans la modale
                    document.getElementById('modalContent').innerHTML = data;
                    
                    // Affichage la modale
                    new bootstrap.Modal(document.getElementById('editCommentModal')).show();
                });
        });
    });

    // Soumettre le formulaire via AJAX
    document.getElementById('editCommentForm')?.addEventListener('submit', function (event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mise à jour de l'interface sans recharger la page
                alert(data.message);
                location.reload(); // Ou mets à jour le commentaire sur la page
            } else {
                alert(data.message);
            }
        });
    });
});

