<?php
session_start();
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Traitement de la suppression d'une notification
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $stmt = $pdo->prepare("DELETE FROM notification WHERE id_notification = ? AND id_utilisateur = ?");
        $stmt->execute([$_POST['id_notification'], $_SESSION['user_id']]);
        exit; // Arrêter l'exécution après la suppression
    } catch(PDOException $e) {
        error_log("Erreur de suppression : " . $e->getMessage());
        http_response_code(500);
        exit;
    }
}

// Traitement de la suppression de toutes les notifications
if (isset($_POST['action']) && $_POST['action'] === 'delete_all') {
    try {
        $stmt = $pdo->prepare("DELETE FROM notification WHERE id_utilisateur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        exit; // Arrêter l'exécution après la suppression
    } catch(PDOException $e) {
        error_log("Erreur de suppression : " . $e->getMessage());
        http_response_code(500);
        exit;
    }
}

// Récupérer les notifications de l'utilisateur (uniquement validation, refus et retour et rappel)
$stmt = $pdo->prepare("SELECT * FROM notification 
                       WHERE id_utilisateur = ? 
                       AND type IN ('validation', 'refus', 'retour', 'rappel')
                       ORDER BY date_envoi DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marquer les notifications comme lues de type validation, refus, retour et rappel
$stmt = $pdo->prepare("UPDATE notification SET lu = TRUE WHERE id_utilisateur = ? AND lu = FALSE AND type IN ('validation', 'refus', 'retour', 'rappel')");
$stmt->execute([$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/notifications-responsive.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fonction pour afficher la modal de confirmation
            function showConfirmationModal(title, message, callback) {
                const modalHtml = `
                    <div class="modal fade" id="confirmationModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${title}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>${message}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="button" class="btn btn-danger" id="confirmAction">Confirmer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Supprimer l'ancienne modal si elle existe
                $('#confirmationModal').remove();
                
                // Ajouter la nouvelle modal au body
                $('body').append(modalHtml);
                
                // Afficher la modal
                const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                modal.show();
                
                // Gérer la confirmation
                $('#confirmAction').click(function() {
                    modal.hide();
                    callback();
                });
                
                // Nettoyer la modal après sa fermeture
                $('#confirmationModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            }

            // Supprimer une notification
            $('.delete-notification').click(function(e) {
                e.preventDefault();
                const notificationCard = $(this).closest('.notification-card');
                const deleteBtn = $(this);
                
                showConfirmationModal(
                    'Supprimer la notification',
                    'Êtes-vous sûr de vouloir supprimer cette notification ?',
                    function() {
                        deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                        
                        $.ajax({
                            url: 'notifications.php',
                            type: 'POST',
                            data: {
                                action: 'delete',
                                id_notification: deleteBtn.data('id')
                            },
                            success: function(response) {
                                notificationCard.fadeOut(300, function() {
                                    $(this).remove();
                                    if ($('.notification-card').length === 0) {
                                        location.reload();
                                    }
                                });
                            },
                            error: function() {
                                alert('Une erreur est survenue lors de la suppression.');
                                deleteBtn.prop('disabled', false).html('<i class="fas fa-times"></i>');
                            }
                        });
                    }
                );
            });

            // Supprimer toutes les notifications
            $('.delete-all-btn').click(function(e) {
                e.preventDefault();
                const deleteAllBtn = $(this);
                
                showConfirmationModal(
                    'Supprimer toutes les notifications',
                    'Êtes-vous sûr de vouloir supprimer toutes vos notifications ? Cette action est irréversible.',
                    function() {
                        deleteAllBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Tout supprimer');
                        
                        $.ajax({
                            url: 'notifications.php',
                            type: 'POST',
                            data: {
                                action: 'delete_all'
                            },
                            success: function(response) {
                                location.reload();
                            },
                            error: function() {
                                alert('Une erreur est survenue lors de la suppression.');
                                deleteAllBtn.prop('disabled', false).html('<i class="fas fa-trash-alt me-2"></i> Tout supprimer');
                            }
                        });
                    }
                );
            });
        });
    </script>
</head>
<body>
  <?php include '../../includes/navbar.php'; ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 page-header">
                    <h2 class="page-title mb-0">
                        <i class="fas fa-bell me-2"></i>
                        Mes Notifications
                    </h2>
                    <?php if (!empty($notifications)): ?>
                        <button type="button" class="btn btn-danger delete-all-btn">
                            <i class="fas fa-trash-alt me-2"></i>
                            Tout supprimer
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h4>Aucune notification</h4>
                        <p>Vous n'avez pas encore reçu de notifications.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-card p-3 <?php echo !$notif['lu'] ? 'notification-unread' : ''; ?>" role="alert">
                            <div class="d-flex justify-content-between align-items-center notification-info">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="notification-type type-<?php echo htmlspecialchars($notif['type']); ?>" aria-label="Type: <?php echo ucfirst(htmlspecialchars($notif['type'])); ?>">
                                        <?php echo ucfirst(htmlspecialchars($notif['type'])); ?>
                                    </span>
                                    <span class="notification-message">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center notification-actions">
                                    <span class="notification-time">
                                        <?php 
                                        $date = new DateTime($notif['date_envoi']);
                                        echo $date->format('d/m/Y H:i'); 
                                        ?>
                                    </span>
                                    <button type="button" class="delete-notification" data-id="<?php echo $notif['id_notification']; ?>" aria-label="Supprimer la notification">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
