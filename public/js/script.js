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
                            // Création de l'élément li avec la classe catalog-in-stock
                            const li = document.createElement('li');
                            li.classList.add('catalog-in-stock');
                            
                            // Création du lien avec l'image
                            const imageLink = document.createElement('a');
                            imageLink.setAttribute('href', '/produit/' + produit.slug);
                            
                            const img = document.createElement('img');
                            img.setAttribute('src', '/img/' + produit.image);
                            img.setAttribute('alt', produit.nomProduit);
                            img.classList.add('catalog-image');
                            
                            imageLink.appendChild(img);
                            li.appendChild(imageLink);
                            
                            // Création du conteneur title-price
                            const titlePrice = document.createElement('div');
                            titlePrice.classList.add('title-price');
                            
                            // Création du conteneur details
                            const details = document.createElement('div');
                            details.classList.add('details');
                             
                            // Lien avec le titre h2
                            const titleLink = document.createElement('a');
                            titleLink.setAttribute('href', '/produit/' + produit.slug);
                            
                            const h2 = document.createElement('h2');
                            h2.textContent = produit.nomProduit;
                            
                            titleLink.appendChild(h2);
                            details.appendChild(titleLink);
                            
                            // Paragraphe du prix avec classe price
                            const price = document.createElement('p');
                            price.classList.add('price');
                            // Formatage du prix comme dans le template original
                            const formattedPrice = new Intl.NumberFormat('fr-FR', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            }).format(produit.getTTC);
                            price.textContent = formattedPrice + '€';
                            
                            details.appendChild(price);
                            titlePrice.appendChild(details);
                            
                            // Création du conteneur clickable-icon
                            const clickableIcon = document.createElement('div');
                            clickableIcon.classList.add('clickable-icon');

                            titlePrice.appendChild(clickableIcon);
                            li.appendChild(titlePrice);
                            
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
                            // Création de l'élément li avec la classe catalog-in-stock
                            const li = document.createElement('li');
                            li.classList.add('catalog-in-stock');
                            
                            // Création du lien avec l'image
                            const imageLink = document.createElement('a');
                            imageLink.setAttribute('href', '/produit/' + produit.slug);
                            
                            const img = document.createElement('img');
                            img.setAttribute('src', '/img/' + produit.image);
                            img.setAttribute('alt', produit.nomProduit);
                            img.classList.add('catalog-image');
                            
                            imageLink.appendChild(img);
                            li.appendChild(imageLink);
                            
                            // Création du conteneur title-price
                            const titlePrice = document.createElement('div');
                            titlePrice.classList.add('title-price');
                            
                            // Création du conteneur details
                            const details = document.createElement('div');
                            details.classList.add('details');
                            
                            // Lien avec le titre h2
                            const titleLink = document.createElement('a');
                            titleLink.setAttribute('href', '/produit/' + produit.slug);
                            
                            const h2 = document.createElement('h2');
                            h2.textContent = produit.nomProduit;
                            
                            titleLink.appendChild(h2);
                            details.appendChild(titleLink);
                            
                            // Paragraphe du prix avec classe price
                            const price = document.createElement('p');
                            price.classList.add('price');
                            // Formatage du prix comme dans le template original
                            const formattedPrice = new Intl.NumberFormat('fr-FR', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            }).format(produit.getTTC);
                            price.textContent = formattedPrice + '€';
                            
                            details.appendChild(price);
                            titlePrice.appendChild(details);
                            
                            // Création du conteneur clickable-icon
                            const clickableIcon = document.createElement('div');
                            clickableIcon.classList.add('clickable-icon');
                            
                            titlePrice.appendChild(clickableIcon);
                            li.appendChild(titlePrice);
                            
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

// Script AJAX for fav 
document.addEventListener('DOMContentLoaded', function () {
    // Liste des URLs où l'AJAX est autorisé
    const urlsCatalogues = [
        '/produit/salty',
        '/produit/sweety',
        '/favoris/page',
    ];

    const path = window.location.pathname; // Récupère le chemin de l'URL actuel sans le domaine HTTPS
    const slug = path.split('/')[2]; // Divise le chemin par le séparateur / et récupère un tableau contenant chaque partie [0] = '', [1] = 'produit', [2] = 'slug' (le slug étant passé dans l'URL pour déterminer le produit)
    const produitUrl = `/produit/${slug}`; // Ajoute le chemin final dans une const pour pouvoir inclure la mise en favoris dans les détails produits
    
    // Ajoute l'URL du produit à la liste
    urlsCatalogues.push(produitUrl);

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

                    if(path.includes('/favoris/page')) {
                        const productElement = this.closest('li');  // Trouve l'élément parent : .favori-item
                        if (productElement) {
                            productElement.remove();  // Retire l'élément du DOM 
                        }
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
// Function handling new allergens integration in add product and update product
document.addEventListener('DOMContentLoaded', function() {
    const addButton = document.getElementById('add-allergen-button');
    const allergenCollection = document.getElementById('form_newAllergenes'); // ID du form newAllergene
    
    if (!addButton || !allergenCollection) return;

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

// Display F.A.Q. blocks
document.addEventListener('DOMContentLoaded', function () {
    const allergenHeaders = document.querySelectorAll('.salty-allergen p, .sweety-allergen p');

    allergenHeaders.forEach(function(header) {

        header.addEventListener('click', function() {
            const content = header.nextElementSibling; 
            const arrowIcon = header.querySelector('i');

            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                arrowIcon.classList.replace('fa-angle-down', 'fa-angle-up');
            } else {
                content.style.display = 'none';
                arrowIcon.classList.replace('fa-angle-up', 'fa-angle-down');
            }
        });
    });
    
    const faqHeaders = document.querySelectorAll('.faq-questions h2');

    faqHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            const content = header.nextElementSibling;
            const arrowIcon = header.querySelector('i'); 

            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block'; 
                arrowIcon.classList.remove('fa-angle-down');
                arrowIcon.classList.add('fa-angle-up');
            } else {
                content.style.display = 'none'; 
                arrowIcon.classList.remove('fa-angle-up');
                arrowIcon.classList.add('fa-angle-down');
            }
        });
    });
});

// Homepage swiper.js 
document.addEventListener('DOMContentLoaded', function () {
    const swiper = new Swiper('.best-sellers-swiper', {
        direction: 'horizontal',
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        scrollbar: {
            el: '.swiper-scrollbar',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
          },
        autoplay: {
            delay: 4000,
            disableOnInteraction: false, 
        },
});
});

// Datatables scripts 
// User orders list dataTables
$(document).ready(function() {
    var table = $('.dataTable').DataTable({
        responsive: true,
        scrollX: true,
        "paging": true,       // Activer la pagination
        "searching": true,    // Activer la recherche
        "ordering": true,  
        "order": [[2, "desc"]], // Triert par date (colonne 2) en ordre décroissant
        "info": false,         // Désactiver les informations sur le nombre d'éléments
        "scrollY": "500px",
        "pagingType": "numbers", // Donner uniquement les numéros de pages
        "lengthChange": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json" // Traduction en français
        },
    });
});

