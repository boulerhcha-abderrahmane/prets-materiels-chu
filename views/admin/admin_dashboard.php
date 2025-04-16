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
            u.id_utilisateur
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
            <div class="stat-box" data-bs-toggle="modal" data-bs-target="#userListModal" style="cursor: pointer;">
                <i class="fas fa-users"></i> Utilisateurs connectés: 
                <span id="users-count"><?= htmlspecialchars($counts['active_users']) ?></span>
            </div>
            <div class="stat-box" data-bs-toggle="modal" data-bs-target="#adminListModal" style="cursor: pointer;">
                <i class="fas fa-users"></i> Administrateurs connectés: 
                <span id="admin-count"><?= htmlspecialchars($counts['active_admins']) ?></span>
            </div>
        </section>

        <section class="recent-requests">
            <h2>Demandes Récentes</h2>
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Utilisateur</th>
                        <th>Matériel</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Date</th>
                        <th>Message</th>
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
                            <tr class="<?= $isQuantityAvailable ? '' : 'table-warning' ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-2">
                                            <div class="fw-bold"><?= htmlspecialchars($demande['nom_utilisateur']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($demande['prenom_utilisateur']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($demande['nom_materiel']) ?></div>
                                    <div class="text-muted small">Disponible: <?= htmlspecialchars($quantite_disponible) ?></div>
                                </td>
                                <td><?= htmlspecialchars($demande['type_materiel'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge <?= $isQuantityAvailable ? 'bg-success' : 'bg-warning' ?>">
                                        <?= htmlspecialchars($demande['quantite']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($demande['date_demande'])) ?></div>
                                    <div class="text-muted small"><?= date('H:i', strtotime($demande['date_demande'])) ?></div>
                                </td>
                                <td>
                                    <textarea class="form-control comment-text" id="comment-<?= htmlspecialchars($demande['id_demande']) ?>" 
                                            name="comment-text-<?= htmlspecialchars($demande['id_demande']) ?>"
                                            placeholder="Motif de refus.." 
                                            style="min-height: 60px; font-size: 0.9em;"></textarea>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal pour la liste des administrateurs -->
    <div class="modal fade" id="adminListModal" tabindex="-1" aria-labelledby="adminListModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminListModalLabel">Administrateurs connectés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($activeAdmins)): ?>
                        <p class="text-center">Aucun administrateur connecté</p>
                    <?php else: ?>
                        <?php foreach ($activeAdmins as $admin): 
                            $photo = $admin['photo'] ?: 'uploads/admin_photos/default_profile.png';
                        ?>
                            <div class="d-flex align-items-center mb-3">
                                <span><?= htmlspecialchars($admin['id_admin']) ?></span>
                                <img src="../../<?= htmlspecialchars($photo) ?>" alt="Photo de profil" 
                                     class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <span><?= htmlspecialchars($admin['nom'] . ' ' . $admin['prenom']) ?></span>
                            </div>
                        <?php endforeach; ?>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userListModalLabel">Utilisateurs connectés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($activeUsers)): ?>
                        <p class="text-center">Aucun utilisateur connecté</p>
                    <?php else: ?>
                        <?php foreach ($activeUsers as $user): 
                            $photo = $user['photo'] ?: 'uploads/admin_photos/default_profile.png';
                        ?>
                            <div class="d-flex align-items-center mb-3">
                                <span><?= htmlspecialchars($user['id_utilisateur']) ?></span>
                                <img src="../../<?= htmlspecialchars($photo) ?>" alt="Photo de profil" 
                                     class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <span><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></span>
                            </div>
                        <?php endforeach; ?>
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
</body>
</html>