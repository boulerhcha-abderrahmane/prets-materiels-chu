<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            display: block;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #f8f9fa;
            color: #2c3e50;
        }

        .main-content {
            margin-left: 0;
            padding: 30px;
            flex-grow: 1;
        }

        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .notification-card:hover {
            transform: translateY(-2px);
        }

        .notification-demande { border-left: 4px solid #0dcaf0; }
        .notification-validation { border-left: 4px solid #198754; }
        .notification-refus { border-left: 4px solid #dc3545; }
        .notification-retour { border-left: 4px solid #ffc107; }
        .notification-rappel { border-left: 4px solid #dc3545; }

        .notification-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .notification-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .notification-icon {
            margin-right: 10px;
        }

        .delete-notification {
            color: #dc3545;
            cursor: pointer;
            float: right;
            transition: all 0.3s ease;
        }

        .delete-notification:hover {
            color: #c82333;
            transform: scale(1.1);
        }

        .delete-all-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            float: right;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .delete-all-btn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php 
    require_once '../../config/config.php';
    session_start();

    // Vérifier si l'admin est connecté
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../login.php');
        exit();
    }

    include '../../includes/sidebar.php';

    $admin_id = $_SESSION['admin_id'];

    // Traitement de la suppression d'une notification
    if (isset($_POST['delete_notification'])) {
        $id_notification = $_POST['id_notification'];
        // Supprimer d'abord de la table de jointure
        $stmt = $pdo->prepare("DELETE FROM notification_administrateur WHERE id_notification = ? AND id_admin = ?");
        $stmt->execute([$id_notification, $admin_id]);
        // Puis supprimer la notification si elle n'est plus liée à aucun admin
        $stmt = $pdo->prepare("DELETE FROM notification WHERE id_notification = ? AND NOT EXISTS (SELECT 1 FROM notification_administrateur WHERE id_notification = ?)");
        $stmt->execute([$id_notification, $id_notification]);
    }

    // Traitement de la suppression de toutes les notifications
    if (isset($_POST['delete_all'])) {
        // Supprimer toutes les associations pour cet admin
        $stmt = $pdo->prepare("DELETE FROM notification_administrateur WHERE id_admin = ?");
        $stmt->execute([$admin_id]);
        // Supprimer les notifications qui ne sont plus liées à aucun admin
        $stmt = $pdo->prepare("DELETE FROM notification WHERE NOT EXISTS (SELECT 1 FROM notification_administrateur WHERE notification.id_notification = notification_administrateur.id_notification)");
        $stmt->execute();
    }

    // Marquer toutes les notifications comme lues
    try {
        $stmt = $pdo->prepare("
            UPDATE notification n
            INNER JOIN notification_administrateur na ON n.id_notification = na.id_notification
            SET n.lu = TRUE 
            WHERE na.id_admin = ? AND n.lu = FALSE
        ");
        $stmt->execute([$admin_id]);
    } catch(PDOException $e) {
        error_log("Erreur lors de la mise à jour des notifications : " . $e->getMessage());
    }

    // Récupérer les notifications pour l'admin juste de statut demande et rappel
    $stmt = $pdo->prepare("
        SELECT n.*, 
               u.nom as nom_utilisateur, 
               u.prenom as prenom_utilisateur
        FROM notification n
        INNER JOIN notification_administrateur na ON n.id_notification = na.id_notification
        LEFT JOIN utilisateur u ON n.id_utilisateur = u.id_utilisateur
        WHERE na.id_admin = ? AND (n.type = 'demande' OR n.type = 'rappel')
        ORDER BY n.date_envoi DESC
    ");
    $stmt->execute([$admin_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fonction pour obtenir l'icône selon le type de notification
    function getNotificationIcon($type) {
        switch ($type) {
            case 'demande': return '<i class="fas fa-file-alt text-info"></i>';
            case 'validation': return '<i class="fas fa-check-circle text-success"></i>';
            case 'refus': return '<i class="fas fa-times-circle text-danger"></i>';
            case 'retour': return '<i class="fas fa-retweet text-warning"></i>';
            case 'rappel': return '<i class="fas fa-exclamation-circle text-danger"></i>';
            default: return '<i class="fas fa-bell"></i>';
        }
    }
    ?>

    <div class="main-content">
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Notifications</h1>
                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer toutes les notifications ?');">
                    <button type="submit" name="delete_all" class="delete-all-btn">
                        <i class="fas fa-trash-alt"></i> Tout supprimer
                    </button>
                </form>
            </div>

            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-card notification-<?= htmlspecialchars($notif['type']) ?>">
                        <div class="notification-header d-flex justify-content-between align-items-start">
                            <div class="notification-title">
                                <span class="notification-icon">
                                    <?= getNotificationIcon($notif['type']) ?>
                                </span>
                                <?php
                                switch ($notif['type']) {
                                    case 'demande':
                                        echo "Nouvelle demande";
                                        break;
                                    case 'validation':
                                        echo "Demande validée";
                                        break;
                                    case 'refus':
                                        echo "Demande refusée";
                                        break;
                                    case 'retour':
                                        echo "Retour de matériel";
                                        break;
                                    case 'rappel':
                                        echo "Rappel";
                                        break;
                                }
                                ?>
                            </div>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return);">
                                <input type="hidden" name="id_notification" value="<?= $notif['id_notification'] ?>">
                                <button type="submit" name="delete_notification" class="btn btn-link delete-notification">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                        <div class="notification-content">
                            <?= nl2br(htmlspecialchars($notif['message'])) ?>
                            <?php if ($notif['id_utilisateur']): ?>
                                <br>
                                <small>De : <?= htmlspecialchars($notif['prenom_utilisateur'] . ' ' . $notif['nom_utilisateur']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="notification-date">
                            <?= date('d/m/Y H:i', strtotime($notif['date_envoi'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Aucune notification pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function refreshNotifications() {
            $.ajax({
                url: window.location.href,
                method: 'GET',
                success: function(response) {
                    // Extraire le contenu des notifications du HTML reçu
                    var newContent = $(response).find('.container.mt-4').html();
                    $('.container.mt-4').html(newContent);
                }
            });
        }

        // Rafraîchir les notifications toutes les 30 secondes
        setInterval(refreshNotifications, 3000);
    </script>
    
</body>
</html> 