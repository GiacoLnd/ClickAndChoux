
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

// script for catalog searchbar

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('search-input');
        const searchForm = searchInput.closest('form');

        searchInput.addEventListener('input', () => {

            searchForm.submit();
        });
    });

// script for quantity input

document.addEventListener('DOMContentLoaded', () => {
    const minusButton = document.getElementById('minus-btn');  // Sélection de la classe "quantity-minus"
    const plusButton = document.getElementById('plus-btn');    // Sélection de la classe "quantity-plus"
    const quantityInput = document.getElementById('quantity');     // Sélection de l'input avec l'id 'quantity'

    let quantity = parseInt(quantityInput.value);  // Stockage de la valeur actuelle

    minusButton.addEventListener('click', () => {
        if (quantity > 1) {  // Empêche la quantité d'aller en dessous de 1
            quantity -= 1;
            quantityInput.value = quantity; // Met à jour la valeur de l'input
        }
    });

    plusButton.addEventListener('click', () => {
        if (quantity < 10) {  // Empêche la quantité d'aller au-delà de 10
            quantity += 1;
            quantityInput.value = quantity; 
        }
    });

    quantityInput.addEventListener('input', () => {
        let value = parseInt(quantityInput.value);
        if (value < 1) {
            quantityInput.value = 1;  // Si la valeur est invalide, mettre la quantité à 1
        }
        if (value > 10) {
            quantityInput.value = 10;  // Limite la quantité à 10
        }
    });
});


