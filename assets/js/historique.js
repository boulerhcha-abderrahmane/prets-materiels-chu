document.addEventListener('DOMContentLoaded', function() {
    // Fermeture de l'alerte de confirmation après 3 secondes
    setTimeout(function() {
        const alert = document.querySelector('.alert-success');
        if (alert) {
            alert.remove();
        }
    }, 3000);

    // Gestion du formulaire de demande
    const demandeForm = document.getElementById('demandeForm');
    const confirmationPrompt = document.getElementById('confirmationPrompt');
    const quantiteInput = document.getElementById('quantite');
    const materielNomHidden = document.getElementById('materielNomHidden');
    const showConfirmationBtn = document.querySelector('[data-action="showConfirmation"]');
    const submitFormBtn = document.querySelector('[data-action="submitForm"]');
    const cancelRequestBtn = document.querySelector('[data-action="cancelRequest"]');

    // Vérifier si tous les éléments nécessaires existent
    if (demandeForm && confirmationPrompt && quantiteInput && materielNomHidden && 
        showConfirmationBtn && submitFormBtn && cancelRequestBtn) {
        
        const materielNom = materielNomHidden.value;

        showConfirmationBtn.addEventListener('click', function() {
            document.getElementById('confirmationQuantite').textContent = quantiteInput.value;
            document.getElementById('confirmationNom').textContent = materielNom;
            confirmationPrompt.classList.add('show');
        });

        submitFormBtn.addEventListener('click', function() {
            demandeForm.submit();
        });

        cancelRequestBtn.addEventListener('click', function() {
            confirmationPrompt.classList.remove('show');
        });
    }

    // Gestion des filtres dans l'historique des prêts
    const statCards = document.querySelectorAll('.stat-card:not(:first-child)');
    const tableRows = document.querySelectorAll('tbody tr');
    const dateRetourHeader = Array.from(document.querySelectorAll('th')).find(th => 
        th.textContent.includes('Date retour prévue')
    );
    const dateRetourCells = document.querySelectorAll('td:nth-child(7)');
    const dateRetourEffectifHeader = Array.from(document.querySelectorAll('th')).find(th => 
        th.textContent.includes('Date de retour')
    );
    const dateRetourEffectifCells = document.querySelectorAll('td:nth-child(8)');

    // Fonction pour afficher/masquer les colonnes de dates selon le filtre
    function toggleDateColumns(show, filter) {
        // Date retour prévue reste visible pour 'waiting_return'
        if (dateRetourHeader) {
            dateRetourHeader.style.display = show ? '' : 'none';
        }
        dateRetourCells.forEach(cell => {
            if (cell) {
                cell.style.display = show ? '' : 'none';
            }
        });

        // Date de retour est masquée pour 'waiting_return'
        const showRetourEffectif = show && filter !== 'waiting_return';
        if (dateRetourEffectifHeader) {
            dateRetourEffectifHeader.style.display = showRetourEffectif ? '' : 'none';
        }
        dateRetourEffectifCells.forEach(cell => {
            if (cell) {
                cell.style.display = showRetourEffectif ? '' : 'none';
            }
        });
    }

    // Fonction pour appliquer un filtre
    function applyFilter(filterCard) {
        if (!filterCard) return;
        
        const filter = filterCard.dataset.filter;
        
        statCards.forEach(c => c.classList.remove('active'));
        filterCard.classList.add('active');

        const showDateColumns = !['en_attente', 'refuse', 'approve'].includes(filter);
        toggleDateColumns(showDateColumns, filter);

        tableRows.forEach(row => {
            const status = row.querySelector('td:nth-child(6)').textContent.trim();
            const shouldShow = 
                (filter === 'en_attente' && status.includes('En attente')) ||
                (filter === 'approve' && status.includes('Validé')) ||
                (filter === 'waiting_return' && status.includes('Valide en attente retour')) ||
                (filter === 'refuse' && status.includes('Refusé')) ||
                (filter === 'retourne' && status.includes('Retourné'));
            
            row.style.display = shouldShow ? '' : 'none';
        });
    }

    // Ajouter le click event à chaque carte de statistiques
    if (statCards.length > 0) {
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                applyFilter(this);
            });
        });

        // Sélectionner "en attente" par défaut au chargement
        const enAttenteCard = document.querySelector('.stat-card[data-filter="en_attente"]');
        if (enAttenteCard) {
            applyFilter(enAttenteCard);
        }
    }
}); 