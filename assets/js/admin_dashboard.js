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

    // Améliorer la gestion des modals
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        // Quand le modal s'ouvre
        modal.addEventListener('show.bs.modal', function() {
            this.setAttribute('aria-hidden', 'false');
        });
    });
    
    // Améliorer la gestion des boutons de fermeture des modals
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalElement = this.closest('.modal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        });
    });

    // Initialiser les boutons de détails
    initDetailsButtons();
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
 * Initialise les effets de survol pour les lignes de demande
 * Cette fonction n'est plus utilisée pour respecter le comportement souhaité
 * où les modals s'ouvrent uniquement au clic et non au survol
 */
function initHoverEffects() {
    // Fonction conservée mais non appelée
    const rows = document.querySelectorAll('.request-row');
    
    rows.forEach(row => {
        // Empêcher que le clic sur les boutons n'ouvre le modal
        row.querySelectorAll('button, .form-control').forEach(element => {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
}

/**
 * Initialiser les boutons de détails et leurs modales
 */
function initDetailsButtons() {
    // Ajouter une animation légère à l'ouverture des modales de détails
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const targetId = this.getAttribute('data-bs-target').substring(1);
            const modalElement = document.getElementById(targetId);
            
            if (modalElement) {
                e.preventDefault();
                
                // Animation améliorée à l'ouverture
                const modal = new bootstrap.Modal(modalElement);
                
                modalElement.addEventListener('shown.bs.modal', function onShown() {
                    // Animer les éléments internes un par un
                    const items = this.querySelectorAll('.mb-3, .info-group');
                    items.forEach((item, index) => {
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, 100 * (index + 1));
                    });
                    
                    this.removeEventListener('shown.bs.modal', onShown);
                }, { once: true });
                
                // Préparer les éléments pour l'animation
                const items = modalElement.querySelectorAll('.mb-3, .info-group');
                items.forEach(item => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                });
                
                modal.show();
            }
        });
    });
    
    // S'assurer que les boutons de fermeture fonctionnent correctement
    document.querySelectorAll('.modal .btn-close, .modal .btn-secondary').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalElement = this.closest('.modal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        });
    });
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
                // Réinitialiser les boutons de détails
                initDetailsButtons();
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
    
    // S'assurer que les clics sur les boutons et les zones de texte ne propagent pas l'événement
    document.querySelectorAll('.request-row button, .request-row .form-control').forEach(element => {
        element.addEventListener('click', function(e) {
            e.stopPropagation();
        });
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