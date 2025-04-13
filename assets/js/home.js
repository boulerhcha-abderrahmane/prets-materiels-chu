/**
 * Gestionnaire des matériels pour la page d'accueil
 * - Recherche en temps réel
 * - Filtrage par type (consommable/non-consommable)
 */

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    initSearch();
    initFilter();
});

/**
 * Initialise la fonctionnalité de recherche en temps réel
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const materielsContainer = document.getElementById('materielsContainer');
    
    if (!searchInput || !materielsContainer) return;
    
    // Fonction debounce pour limiter le nombre d'appels pendant la saisie
    const debounce = (func, delay) => {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), delay);
        };
    };
    
    // Fonction de recherche avec optimisation debounce
    const handleSearch = debounce(() => {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const materielItems = materielsContainer.querySelectorAll('.materiel-item');
        
        // Si la recherche est vide, afficher tous les éléments
        if (!searchTerm) {
            materielItems.forEach(item => item.style.display = '');
            return;
        }
        
        // Filtrer les éléments selon le terme de recherche
        materielItems.forEach(item => {
            const name = item.dataset.name?.toLowerCase() || '';
            const type = item.dataset.type?.toLowerCase() || '';
            
            const isVisible = name.includes(searchTerm) || type.includes(searchTerm);
            item.style.display = isVisible ? '' : 'none';
        });
    }, 300);
    
    // Ajouter l'écouteur d'événements
    searchInput.addEventListener('input', handleSearch);
}

/**
 * Initialise la fonctionnalité de filtrage par type
 */
function initFilter() {
    // Récupérer le filtre existant
    const filterSelect = document.getElementById('filterSelect');
    
    // Créer un nouveau filtre si nécessaire
    if (!filterSelect) {
        createFilterElement();
        return;
    }
    
    const materielsContainer = document.getElementById('materielsContainer');
    if (!materielsContainer) return;
    
    // Configurer l'écouteur d'événements pour le filtre
    filterSelect.addEventListener('change', () => {
        const selectedValue = filterSelect.value;
        const materielItems = materielsContainer.querySelectorAll('.materiel-item');
        
        // Appliquer le filtre à chaque élément
        materielItems.forEach(item => {
            const type = item.dataset.type?.toLowerCase() || '';
            
            // Déterminer la visibilité en fonction du filtre sélectionné
            const shouldShow = 
                selectedValue === 'all' || 
                (selectedValue === 'consommable' && type === 'consommable') ||
                (selectedValue === 'non-consommable' && type === 'non-consommable');
            
            // Appliquer la visibilité
            item.style.display = shouldShow ? '' : 'none';
        });
    });
    
    // Appliquer le filtre initial si nécessaire
    if (filterSelect.value !== 'all') {
        filterSelect.dispatchEvent(new Event('change'));
    }
}

/**
 * Crée un élément de filtre si non présent dans la navbar
 */
function createFilterElement() {
    const searchBar = document.querySelector('.search-bar');
    if (!searchBar) return;
    
    // HTML pour le nouveau filtre
    const filterHTML = `
        <div class="filter-container mt-3">
            <select id="filterSelect" class="form-select">
                <option value="all">Tous les matériels</option>
                <option value="consommable">Consommables</option>
                <option value="non-consommable">Non consommables</option>
            </select>
        </div>
    `;
    
    // Ajouter le filtre après la barre de recherche
    searchBar.insertAdjacentHTML('afterend', filterHTML);
    
    // Initialiser le filtre
    initFilter();
} 