/**
 * Admin Dashboard JavaScript
 * Gère les fonctionnalités interactives du tableau de bord administrateur
 */

// Vérifier que jQuery est bien chargé
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery est chargé correctement');
        initDashboard();
    } else {
        console.error('jQuery n\'est pas chargé');
    }

    // Auto-dismiss des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Utiliser la nouvelle approche avec l'attribut inert
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        // Quand le modal s'ouvre
        modal.addEventListener('show.bs.modal', function() {
            // S'assurer que l'attribut aria-hidden n'est pas appliqué
            this.setAttribute('aria-hidden', 'false');
        });
        
        // Quand le modal se ferme
        modal.addEventListener('hide.bs.modal', function() {
            // Utiliser inert plutôt que aria-hidden
            this.inert = true;
            
            // Retirer inert après l'animation
            setTimeout(() => {
                this.inert = false;
            }, 500); // Duration de l'animation du modal
        });
    });
    
    // Transférer le focus de manière sûre
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Déplacer le focus vers un élément sûr (non masqué)
            setTimeout(() => {
                document.querySelector('body').focus();
            }, 150);
        });
    });
});

/**
 * Initialisation des fonctionnalités du tableau de bord
 */
function initDashboard() {
    // Rafraîchir les données toutes les 30 secondes
    setInterval(refreshRequests, 30000);
    
    // Attacher les écouteurs d'événements aux boutons
    attachEventListeners();
}

/**
 * Rafraîchir la liste des demandes via AJAX
 */
function refreshRequests() {
    $.ajax({
        url: window.location.href,
        method: 'GET',
        success: function(data) {
            // Extraire le contenu du tbody de la réponse
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const newTbody = doc.querySelector('#requests-table-body');
            
            if (newTbody) {
                // Remplacer l'ancien tbody par le nouveau
                const currentTbody = document.querySelector('#requests-table-body');
                currentTbody.innerHTML = newTbody.innerHTML;
                
                // Réattacher les événements aux nouveaux éléments
                attachEventListeners();
            }
            
            // Mettre à jour les compteurs
            updateCounters();
        },
        error: function(xhr, status, error) {
            console.error('Erreur lors du rafraîchissement des demandes:', error);
        }
    });
}

/**
 * Mettre à jour les compteurs d'éléments
 */
function updateCounters() {
    $.ajax({
        url: 'get_counters.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#valid-requests').text(data.valid_requests);
            $('#pending-requests').text(data.pending_requests);
            $('#users-count').text(data.active_users);
            $('#admin-count').text(data.active_admins);
        },
        error: function(xhr, status, error) {
            console.error('Erreur lors de la mise à jour des compteurs:', error);
        }
    });
}

/**
 * Attacher les écouteurs d'événements aux boutons
 */
function attachEventListeners() {
    document.querySelectorAll('.btn-action').forEach(button => {
        button.onclick = function() {
            return setComment(this);
        };
    });
}

/**
 * Vérifier et définir le commentaire pour une demande
 * @param {HTMLElement} button - Le bouton cliqué
 * @returns {boolean} - true si la validation est réussie, false sinon
 */
function setComment(button) {
    const row = button.closest('tr');
    const commentText = row.querySelector('.comment-text').value;
    const action = button.value; // 'approve' ou 'reject'
    const form = button.closest('form');
    
    // Vérifier le commentaire uniquement pour le refus
    if (action === 'reject' && !commentText.trim()) {
        // Créer et afficher l'alerte Bootstrap
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning alert-dismissible fade show';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <strong>Attention!</strong> Veuillez ajouter un commentaire avant de refuser la demande.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Ajouter l'alerte au conteneur d'alertes
        const alertContainer = document.querySelector('.alert-container');
        alertContainer.appendChild(alertDiv);
        
        // Auto-dismiss après 3 secondes
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 3000);
        
        return false;
    }
    
    // Définir le commentaire dans le champ caché
    form.querySelector('.comment-input').value = commentText;
    return true;
} 