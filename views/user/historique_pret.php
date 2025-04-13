<?php
session_start();
require_once '../../config/config.php';



if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Déplacer la logique métier dans des fonctions
function checkUserAuthentication($pdo) {
    if (!isset($_SESSION['user_id'])) {
        redirectToLogin();
    }
    return getUserInfo($pdo);
}

function getUserInfo($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateur WHERE id_utilisateur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            redirectToLogin();
        }
        
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        return $user;
    } catch(PDOException $e) {
        logError("Erreur lors de la récupération des informations utilisateur", $e);
        redirectToLogin();
    }
}

function getNotificationCount($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE id_utilisateur = ? AND lu = FALSE");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        logError("Erreur lors du comptage des notifications", $e);
        return 0;
    }
}

function getLoanHistory($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.*, m.nom, m.type, m.description, 
                   d.date_retour_prevue, r.date_retour
            FROM Demande_pret d 
            JOIN Materiel m ON d.id_materiel = m.id_materiel 
            LEFT JOIN RETOUR_PRET r ON d.id_demande = r.id_demande
            WHERE d.id_utilisateur = ? 
            ORDER BY d.date_demande DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        logError("Erreur lors de la récupération de l'historique des prêts", $e);
        return [];
    }
}

function getLoanStats($pdo) {
    try {
        $stats = [
            'en_attente' => 0,
            'validé' => 0,
            'valide en attente retour' => 0,
            'refusé' => 0,
            'retourné' => 0
        ];
        
        $stmt = $pdo->prepare("
            SELECT statut, COUNT(*) as count 
            FROM Demande_pret 
            WHERE id_utilisateur = ? 
            GROUP BY statut
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $stats[$result['statut']] = $result['count'];
        }
        return $stats;
    } catch(PDOException $e) {
        logError("Erreur lors du calcul des statistiques", $e);
        return $stats;
    }
}

function getStatusBadge($status) {
    $badges = [
        'en_attente' => 'info',
        'validé' => 'success',
        'valide en attente retour' => 'success',
        'refusé' => 'danger',
        'retourné' => 'warning'
    ];
    
    $class = $badges[$status] ?? 'secondary';
    $label = $status === 'en_attente' ? 'En attente' : ucfirst($status);
    
    return sprintf('<span class="badge bg-%s" style="font-size: 1rem;">%s</span>', 
        $class, 
        htmlspecialchars($label)
    );
}

function redirectToLogin() {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

function logError($message, $exception) {
    error_log($message . ": " . $exception->getMessage());
}

// Initialisation des données
$user = checkUserAuthentication($pdo);
$notif_count = getNotificationCount($pdo);
$demande_pret = getLoanHistory($pdo);
$stats = getLoanStats($pdo);

// Inclure la navbar

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi d'état</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/historique.css">
   
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container">
        <br>
       
        <!-- Nouvelle section statistiques avec filtres -->
        <div class="stats-container">
            <div class="stat-card total">
                <i class="fas fa-list-alt"></i>
                <span class="stat-number"><?= array_sum($stats) ?></span>
                <span class="stat-label">Total des demandes</span>
            </div>
            <div class="stat-card" data-filter="en_attente">
                <i class="fas fa-clock"></i>
                <span class="stat-number"><?= $stats['en_attente'] ?></span>
                <span class="stat-label">En attente</span>
            </div>
            <div class="stat-card" data-filter="approve">
                <i class="fas fa-check-circle"></i>
                <span class="stat-number"><?= $stats['validé'] ?></span>
                <span class="stat-label">emprunté</span>
            </div>
            <div class="stat-card" data-filter="refuse">
                <i class="fas fa-times-circle"></i>
                <span class="stat-number"><?= $stats['refusé'] ?></span>
                <span class="stat-label">Refusés</span>
            </div>
            <div class="stat-card" data-filter="waiting_return">
                <i class="fas fa-hourglass-half"></i>
                <span class="stat-number"><?= $stats['valide en attente retour'] ?></span>
                <span class="stat-label">En attente retour</span>
            </div>
            <div class="stat-card" data-filter="retourne">
                <i class="fas fa-undo"></i>
                <span class="stat-number"><?= $stats['retourné'] ?></span>
                <span class="stat-label">Retournés</span>
            </div>
        </div>

        <div class="history-card">
            <?php if (count($demande_pret) > 0): ?>
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>  <th>Date de Demande</th>
                                <th>Matériel</th>
                                <th>Type</th>
                                <th>Description</th>
                              
                                <th>Quantité</th>
                                <th>Statut</th>
                                <th>Date retour prévue</th>
                                <th>Date de retour</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demande_pret as $demande): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?></td>
                                    <td><strong><?= htmlspecialchars($demande['nom']) ?></strong></td>
                                    <td><?= htmlspecialchars($demande['type']) ?></td>
                                    <td><?= htmlspecialchars($demande['description']) ?></td>
                                    
                                    <td><?= htmlspecialchars($demande['quantite']) ?></td>
                                    
                                    <td>
                                        <?= getStatusBadge($demande['statut']) ?>
                                    </td>
                                    <td><?= isset($demande['date_retour_prevue']) && !in_array($demande['statut'], ['en_attente', 'refusé']) 
                                            ? date('d/m H:i', strtotime($demande['date_retour_prevue'])) 
                                            : 'N/A' ?></td>
                                    <td><?= isset($demande['date_retour']) ? date('d/m/Y H:i', strtotime($demande['date_retour'])) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history fa-3x mb-3"></i>
                    <p>Aucun prêt trouvé dans l'historique.</p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>

  

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/historique.js"></script>
</body>
</html>