/**
 * Script pour la gestion du menu hamburger
 * Gère l'ouverture/fermeture et les interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const hamburger = document.querySelector('.hamburger-menu');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Toggle menu hamburger
    hamburger.addEventListener('click', function() {
        this.classList.toggle('active');
        navbarCollapse.classList.toggle('show');
    });
    
    // Fermer le menu lors du clic sur un lien
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navbarCollapse.classList.remove('show');
        });
    });
    
    // Fermer le menu lors du clic en dehors
    document.addEventListener('click', function(event) {
        const isClickInside = navbarCollapse.contains(event.target) || 
                              hamburger.contains(event.target);
        
        if (!isClickInside && navbarCollapse.classList.contains('show')) {
            hamburger.classList.remove('active');
            navbarCollapse.classList.remove('show');
        }
    });
}); 