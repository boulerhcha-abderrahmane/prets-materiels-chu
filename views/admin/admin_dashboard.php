<?php
session_start();
require_once '../../config/config.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Récupérer les informations de l'admin
$stmt = $pdo->prepare("SELECT nom, prenom, photo FROM administrateur WHERE id_admin = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$nom = $admin['nom'];
$prenom = $admin['prenom'];

// Modifier la logique du chemin de la photo
$default_photo = 'uploads/admin_photos/default_profile.png';
$photo_profil = $admin['photo'] 
    ? (file_exists('../../' . $admin['photo']) 
        ?  $admin['photo'] 
        : $default_photo)
    : $default_photo;

//mettre à jour le statut actif
$updateStmt = $pdo->prepare("UPDATE administrateur SET actif = 1 WHERE id_admin = ?");
$updateStmt->execute([$_SESSION['admin_id']]);

// Récupération des statistiques
try {
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM demande_pret WHERE statut IN ('valide en attente retour')) as valid_requests,
            (SELECT COUNT(*) FROM demande_pret WHERE statut = 'en_attente') as pending_requests,
            (SELECT COUNT(*) FROM utilisateur WHERE actif = '1') as active_users,
            (SELECT COUNT(*) FROM administrateur WHERE actif = '1') as active_admins
    ";
    $counts = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    $counts = [
        'valid_requests' => 0,
        'pending_requests' => 0,
        'active_users' => 0,
        'active_admins' => 0
    ];
}

// Requête pour les demandes récentes
try {
    $recent_requests_query = "
        SELECT 
            d.id_demande,
            u.nom as nom_utilisateur,
            u.prenom as prenom_utilisateur,
            m.nom as nom_materiel,
            m.type as type_materiel,
            d.quantite,
            d.date_demande,
            m.quantite_disponible,
            u.id_utilisateur,
            d.motif
        FROM demande_pret d 
        JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur 
        JOIN materiel m ON d.id_materiel = m.id_materiel 
        WHERE d.statut = 'en_attente'
        ORDER BY d.date_demande DESC
    ";
    $recent_requests = $pdo->query($recent_requests_query)->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des demandes récentes: " . $e->getMessage());
    $recent_requests = [];
}

// Fonction pour afficher les alertes
function displayAlerts() {
    if (isset($_SESSION['process_request_success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_SESSION['process_request_success']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['process_request_success']);
    }

    if (isset($_SESSION['process_request_error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_SESSION['process_request_error']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['process_request_error']);
    }
}

// Fonction pour récupérer la liste des utilisateurs/admins connectés
function getActiveUsers($pdo, $isAdmin = false) {
    $tableName = $isAdmin ? 'administrateur' : 'utilisateur';
    $idColumn = $isAdmin ? 'id_admin' : 'id_utilisateur';
    
    try {
        $stmt = $pdo->query("SELECT nom, prenom, photo, $idColumn FROM $tableName WHERE actif = '1'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur lors de la récupération des " . ($isAdmin ? "administrateurs" : "utilisateurs") . " connectés: " . $e->getMessage());
        return [];
    }
}

$activeUsers = getActiveUsers($pdo, false);
$activeAdmins = getActiveUsers($pdo, true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin_dashboard.css">
    <style>
        /* Styles pour les modals */
        .modal-content {
            border: none;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background: #4a90e2;
            color: white;
            border-radius: 6px 6px 0 0;
            padding: 0.75rem 1rem;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 0.75rem 1rem;
        }
        
        /* Réduire la taille du modal */
        .modal-sm-custom {
            max-width: 400px;
            margin: 1.75rem auto;
        }
        
        /* Style pour les stat-box clickables */
        .stat-box.clickable {
            transition: background-color 0.2s;
        }
        
        .stat-box.clickable:hover {
            background-color: #f5f9ff;
        }
        
        /* Masquer les boutons détails dans les stat-box */
        .stat-box .btn-modal,
        .stat-box .btn-details {
            display: none !important;
        }
        
        /* Styles pour les listes d'utilisateurs */
        .user-list-item {
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 0.4rem;
            border: 1px solid #eee;
        }
        
        .user-list-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 2px solid #4a90e2;
        }
        
        .user-list-item .user-info {
            margin-left: 1rem;
        }
        
        .user-list-item .user-name {
            font-weight: 600;
            color: #333;
        }
        
        .user-list-item .user-id {
            font-size: 0.85rem;
            color: #666;
        }
        
        /* Styles pour le modal de détails */
        .details-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .details-card .card-header {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1rem;
        }
        
        .details-card .card-body {
            padding: 1.5rem;
        }
        
        .details-card .info-group {
            margin-bottom: 1rem;
        }
        
        .details-card .info-label {
            font-weight: 600;
            color: #4a90e2;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .details-card .info-value {
            color: #333;
            padding: 0.4rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .details-card .motif-box {
            background-color: #f8f9fa;
            padding: 0.8rem;
            border-radius: 6px;
            border-left: 3px solid #4a90e2;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <!-- Conteneur d'alertes -->
    <div class="alert-container">
        <?php displayAlerts(); ?>
    </div>

    <div class="main-content">
        <header class="top-bar">
            <h1>Tableau de Bord</h1>
            <div class="profile">
                <h4>Bonjour, <?php echo htmlspecialchars($nom . ' ' . $prenom); ?></h4>
                <a href="profile_admin.php" style="text-decoration: none; color: inherit;">
                    <img src="../../<?php echo htmlspecialchars($photo_profil); ?>" alt="Profil">
                </a>
            </div>
        </header>

        <section class="dashboard">
            <div class="stat-box">
                <i class="fas fa-sync-alt"></i> En attente retour 
                <span id="valid-requests"><?= htmlspecialchars($counts['valid_requests']) ?></span>
            </div>
            <div class="stat-box">
                <i class="fas fa-clock"></i> Demandes en attente: 
                <span id="pending-requests"><?= htmlspecialchars($counts['pending_requests']) ?></span>
            </div>
            <div class="stat-box clickable" onclick="openModal('userListModal')" style="cursor: pointer;">
                <i class="fas fa-users"></i> Utilisateurs connectés: 
                <span id="users-count"><?= htmlspecialchars($counts['active_users']) ?></span>
            </div>
            <div class="stat-box clickable" onclick="openModal('adminListModal')" style="cursor: pointer;">
                <i class="fas fa-users"></i> Administrateurs connectés: 
                <span id="admin-count"><?= htmlspecialchars($counts['active_admins']) ?></span>
            </div>
        </section>

        <section class="recent-requests">
            <h2>Demandes Récentes</h2>
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Utilisateur</th>
                        <th>Matériel</th>
                        <th>Quantité</th>
                        <th>Message</th>
                        <th>Détails</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="requests-table-body">
                    <?php if (empty($recent_requests)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucune demande en attente</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_requests as $demande): 
                            $quantite_disponible = $demande['quantite_disponible'];
                            $quantite_demande = $demande['quantite'];
                            $isQuantityAvailable = $quantite_demande <= $quantite_disponible;
                        ?>
                            <tr class="<?= $isQuantityAvailable ? '' : 'table-warning' ?> request-row">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-2">
                                            <div class="fw-bold"><?= htmlspecialchars($demande['nom_utilisateur'] . ' ' . $demande['prenom_utilisateur']) ?></div>
                                            <div class="text-muted small mt-1">
                                                <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($demande['date_demande'])) ?>
                                                <i class="far fa-clock ms-2"></i> <?= date('H:i', strtotime($demande['date_demande'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($demande['nom_materiel']) ?></div>
                                    <div class="text-muted small">Disponible: <?= htmlspecialchars($quantite_disponible) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $isQuantityAvailable ? 'bg-success' : 'bg-warning' ?>">
                                        <?= htmlspecialchars($demande['quantite']) ?>
                                    </span>
                                </td>
                                <td>
                                    <textarea class="form-control comment-text" id="comment-<?= htmlspecialchars($demande['id_demande']) ?>" 
                                            name="comment-text-<?= htmlspecialchars($demande['id_demande']) ?>"
                                            placeholder="Motif de refus.." 
                                            style="min-height: 60px; font-size: 0.9em;"></textarea>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-modal rounded-circle" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailsModal<?= htmlspecialchars($demande['id_demande']) ?>"
                                            style="width: 36px; height: 36px;">
                                        <i class="fas fa-info"></i>
                                    </button>
                                </td>
                                <td>
                                    <form action='process_request.php' method='POST' class='d-flex gap-2'>
                                        <input type='hidden' name='id_demande' value='<?= htmlspecialchars($demande['id_demande']) ?>'>
                                        <input type='hidden' name='id_utilisateur' value='<?= htmlspecialchars($demande['id_utilisateur']) ?>'>
                                        <input type='hidden' name='nom_materiel' value='<?= htmlspecialchars($demande['nom_materiel']) ?>'>
                                        <input type='hidden' name='commentaire' class='comment-input'>
                                        
                                        <?php if ($isQuantityAvailable): ?>
                                            <button type='submit' name='action' value='approve' 
                                                    class='btn btn-sm btn-success btn-action' 
                                                    onclick='return setComment(this)'>
                                                <i class='fas fa-check'></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type='submit' name='action' value='reject' 
                                                class='btn btn-sm btn-danger btn-action' 
                                                onclick='return setComment(this)'>
                                            <i class='fas fa-times'></i>
                                        </button>
                                    </form>
                                </td>
                                
                                <!-- Modal Détails -->
                                <div class="modal" id="detailsModal<?= htmlspecialchars($demande['id_demande']) ?>" 
                                     tabindex="-1" aria-labelledby="detailsModalLabel<?= htmlspecialchars($demande['id_demande']) ?>" 
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-sm-custom">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="detailsModalLabel<?= htmlspecialchars($demande['id_demande']) ?>">
                                                    Détails de la demande
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="details-card">
                                                    
                                                    <div class="card-body">
                                                        <div class="info-group">
                                                            <div class="info-label">Utilisateur</div>
                                                            <div class="info-value">
                                                                <?= htmlspecialchars($demande['nom_utilisateur'] . ' ' . $demande['prenom_utilisateur']) ?>
                                                            </div>
                                                        </div>
                                                        <div class="info-group">
                                                            <div class="info-label">Matériel</div>
                                                            <div class="info-value">
                                                                <?= htmlspecialchars($demande['nom_materiel']) ?>
                                                            </div>
                                                        </div>
                                                        <div class="info-group">
                                                            <div class="info-label">Type</div>
                                                            <div class="info-value">
                                                                <?= htmlspecialchars($demande['type_materiel'] ?? 'Non spécifié') ?>
                                                            </div>
                                                        </div>
                                                        <div class="info-group">
                                                            <div class="info-label">Quantité</div>
                                                            <div class="info-value">
                                                                <?= htmlspecialchars($demande['quantite']) ?> 
                                                                (Disponible: <?= htmlspecialchars($demande['quantite_disponible']) ?>)
                                                            </div>
                                                        </div>
                                                        <div class="info-group">
                                                            <div class="info-label">Date de demande</div>
                                                            <div class="info-value">
                                                                <?= date('d/m/Y à H:i', strtotime($demande['date_demande'])) ?>
                                                            </div>
                                                        </div>
                                                        <div class="info-group">
                                                            <div class="info-label">Motif de la demande</div>
                                                            <div class="motif-box">
                                                                <?= !empty($demande['motif']) ? nl2br(htmlspecialchars($demande['motif'])) : '<em>Aucun motif spécifié</em>' ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeModal('detailsModal<?= htmlspecialchars($demande['id_demande']) ?>')">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal pour la liste des administrateurs -->
    <div class="modal fade" id="adminListModal" tabindex="-1" aria-labelledby="adminListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminListModalLabel">Administrateurs connectés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($activeAdmins)): ?>
                        <p class="text-center text-muted">Aucun administrateur connecté</p>
                    <?php else: ?>
                        <ul class="list-group">
                        <?php foreach ($activeAdmins as $admin): 
                            $photo = $admin['photo'] ?: 'uploads/admin_photos/default_profile.png';
                        ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <img src="../../<?= htmlspecialchars($photo) ?>" alt="Photo de profil" 
                                         class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <span class="fw-bold"><?= htmlspecialchars($admin['nom'] . ' ' . $admin['prenom']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour la liste des utilisateurs -->
    <div class="modal fade" id="userListModal" tabindex="-1" aria-labelledby="userListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userListModalLabel">Utilisateurs connectés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($activeUsers)): ?>
                        <p class="text-center text-muted">Aucun utilisateur connecté</p>
                    <?php else: ?>
                        <ul class="list-group">
                        <?php foreach ($activeUsers as $user): 
                            $photo = $user['photo'] ?: 'uploads/admin_photos/default_profile.png';
                        ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <img src="../../<?= htmlspecialchars($photo) ?>" alt="Photo de profil" 
                                         class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <span class="fw-bold"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/admin_dashboard.js"></script>
    <script>
        function openModal(modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                // Nettoyer d'abord les backdrops existants
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
        
        function closeModal(modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                
                // Forcer le nettoyage complet après fermeture
                setTimeout(() => {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 300);
            }
        }
        
        // Amélioration globale pour tous les modals de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Attacher à tous les boutons de fermeture de modal
            document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
                button.addEventListener('click', function() {
                    setTimeout(() => {
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 300);
                });
            });
            
            // Ajouter un écouteur à tous les modals
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });
            });
        });
    </script>
</body>
</html>