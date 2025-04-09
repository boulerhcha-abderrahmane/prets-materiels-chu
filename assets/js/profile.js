document.addEventListener('DOMContentLoaded', function() {
    // Gestion des alertes
    setTimeout(function() {
        const alertSuccess = document.querySelector('.alert-success');
        if (alertSuccess) {
            alertSuccess.remove();
        }
    }, 3000);

    // Fonction de suppression de photo
    window.deletePhoto = function() {
        const deletePhotoModal = new bootstrap.Modal(document.getElementById('deletePhotoModal'));
        deletePhotoModal.show();
    };

    // Définir confirmDeletePhoto dans le scope global
    window.confirmDeletePhoto = function() {
        const userId = document.getElementById('userId').value;
        
        fetch('delete_photo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.text())
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Réponse serveur:', text);
                throw new Error('Réponse serveur invalide');
            }
        })
        .then(data => {
            if (data.success) {
                document.getElementById('previewImage').src = '../../uploads/user_photos/default_profile.png';
                const deletePhotoModal = bootstrap.Modal.getInstance(document.getElementById('deletePhotoModal'));
                deletePhotoModal.hide();
                window.location.reload();
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de la photo: ' + error.message);
        });
    };

    // Gestion du changement de photo
    const photoInput = document.getElementById('photoInput');
    const previewImage = document.getElementById('previewImage');
    
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Vérification de la taille
                if (file.size > 5 * 1024 * 1024) {
                    alert('La taille du fichier doit être inférieure à 5MB.');
                    this.value = '';
                    return;
                }
                
                // Vérification du format
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                    this.value = '';
                    return;
                }
                
                // Prévisualisation de l'image dans le modal
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('modalPreviewImage').src = e.target.result;
                    const photoConfirmModal = new bootstrap.Modal(document.getElementById('photoConfirmModal'));
                    photoConfirmModal.show();
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Gestion de la confirmation dans le modal
    const confirmPhotoChange = document.getElementById('confirmPhotoChange');
    if (confirmPhotoChange) {
        confirmPhotoChange.addEventListener('click', function() {
            const photoConfirmModal = bootstrap.Modal.getInstance(document.getElementById('photoConfirmModal'));
            photoConfirmModal.hide();
            document.querySelector('form').submit();
        });
    }

    // Gestion de l'annulation dans le modal
    const photoConfirmModal = document.getElementById('photoConfirmModal');
    if (photoConfirmModal) {
        photoConfirmModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('photoInput').value = '';
        });
    }

    // Validation du mot de passe


    // Fonction pour ouvrir le modal de zoom
    window.openPhotoZoom = function() {
        const previewImage = document.getElementById('previewImage');
        const zoomedImage = document.getElementById('zoomedImage');
        
        // Assurer que l'image source est correcte
        let imgSrc = previewImage.src;
        
        // Si le chemin ne contient pas déjà uploads/user_photos et ce n'est pas un data URL
        if (!imgSrc.includes('uploads/user_photos/') && !imgSrc.startsWith('data:')) {
            // Extraire le nom du fichier de l'URL
            const fileName = imgSrc.split('/').pop();
            // Reconstruire l'URL avec le chemin correct
            imgSrc = '../../uploads/user_photos/' + fileName;
        }
        
        zoomedImage.src = imgSrc;
        const photoZoomModal = new bootstrap.Modal(document.getElementById('photoZoomModal'));
        photoZoomModal.show();
    };
}); 