
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
    fetch('/favoris/liste', { credentials: 'include' }) // Récupère les favoris de l'utilisateur
        .then(response => response.json())
        .then(data => {
            if (data.favoris) { // Vérification des favoris
                document.querySelectorAll('.favori-btn').forEach(button => {
                    const produitId = button.dataset.id; // Récupère l'ID du produit depuis le bouton
                    if (data.favoris.includes(parseInt(produitId))) { // Vérifie si le produit est en favoris
                        button.classList.add('favori-active'); // Ajoute la classe pour indiquer que le produit est déjà en favoris
                    }
                });
            }
        })

    // Gérer l'ajout et la suppression des favoris
    document.querySelectorAll('.favori-btn').forEach(button => {
        button.addEventListener('click', function () {
            const produitId = this.dataset.id; // Récupère l'id via le coeur
            const isFavori = this.classList.contains('favori-active'); // Vérifie si le bouton a déjà la classe "favori-active"
            const url = isFavori ? `/favoris/supprimer/${produitId}` : `/favoris/ajouter/${produitId}`; // Si produit déjà en favoris, supprime, sinon l'ajoute
            const method = isFavori ? 'DELETE' : 'POST'; // Si suppression : DELETE, sinon POST

            fetch(url, {
                method: method, // Utilise la méthode http précédente
                credentials: 'include', // 🔹 Garde la session active
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Précise que la requête est une requête AJAX
                }
            })
            .then(response => response.json()) // Converti la réponse en JSON
            .then(data => { 
                if (data.message.includes('ajouté')) {
                    this.classList.add('favori-active'); // active le coeur rouge
                } else if (data.message.includes('retiré')) {
                    this.classList.remove('favori-active'); // active le coeur rose
                }
            })
        });
    });
});









