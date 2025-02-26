
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

// JQUERY AJAX for catalog searchbar
$(document).ready(function(){
    $('#search-input').on('input', function() {
        var searchQuery = $(this).val();

        if(searchQuery.length < 0) {
            $.ajax({
                url
            })
        }
    })
}) 

// Datatables scripts 

// User orders list dataTables
$(document).ready(function() {
    var table = $('#commandesTable').DataTable({
        "paging": true,       // Active la pagination
        "searching": true,    // Active la recherche
        "ordering": true,  
        "order": [[2, "desc"]], // Trie par date (colonne 2) en ordre décroissant
        "info": false,         // Désactive les informations sur le nombre d'éléments
        "pagingType": "numbers", // Désactive 
        "lengthChange": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json" // Traduction en français
        },
    });
});

// Admin orders list dataTables
$(document).ready(function() {
    var table = $('#commandesAdminTable').DataTable({
        "paging": true,       // Active la pagination
        "searching": true,    // Active la recherche
        "ordering": true,  
        "order": [[2, "desc"]], // Trie par date (colonne 2) en ordre décroissant
        "info": false,         // Désactive les informations sur le nombre d'éléments
        "pagingType": "numbers", // Désactive 
        "lengthChange": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json" // Traduction en français
        },
    });
});

// Datatable script for user list
$(document).ready(function() {
    var table = $('#utilisateurTable').DataTable({
        "paging": true,       // Active la pagination
        "searching": true,    // Active la recherche
        "ordering": true,  
        "info": false,         // Désactive les informations sur le nombre d'éléments
        "pagingType": "numbers",
        "lengthChange": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json" // Traduction en français
        },
    });
});



// Admin add product dataTables

$(document).ready(function() {
    $('#produitsTable').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "order": [[1, "asc"]],
        "lengthChange": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json"
        }
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















