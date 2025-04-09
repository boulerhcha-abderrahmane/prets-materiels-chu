<?php
session_start();
require_once '../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Récupérer d'abord les informations de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT nom, prenom 
        FROM utilisateur 
        WHERE id_utilisateur = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
    }

    // Mettre à jour le statut actif
    $stmt = $pdo->prepare("
        UPDATE utilisateur 
        SET actif = 1 
        WHERE id_utilisateur = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Récupérer le nombre de notifications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notification 
        WHERE id_utilisateur = ? 
        AND lu = FALSE
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notif_count = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des informations : " . $e->getMessage());
}

// Récupération des matériels
$stmt = $pdo->prepare("
    SELECT id_materiel, nom, photo, quantite_disponible, type 
    FROM materiel 
    WHERE actif = TRUE 
    ORDER BY nom
");
$stmt->execute();
$materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug - Vérifier les valeurs de session
// echo "Prénom en session : " . ($_SESSION['prenom'] ?? 'Non défini');

// Afficher le message de statut si disponible
if (isset($_SESSION['status_message'])) {
    echo "<div style='color: blue;'>" . $_SESSION['status_message'] . "</div>";
    unset($_SESSION['status_message']); // Effacer le message après l'affichage
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Utilisateur - Gestion de Prêt</title>
    
    <!-- CSS essentiels -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/home.css">
</head>
<body>  
    <!-- Inclure la navbar -->
    <?php include '../../includes/navbar.php'; ?>
    
    <!-- Ajouter le conteneur d'alertes -->
    <div class="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 1050; max-width: 400px;"></div>

    <div class="container py-4">
        <h2 class="section-title">Matériels Disponibles</h2>
        
        <div class="search-bar">
            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un matériel...">
        </div>

        <div class="row g-3" id="materielsContainer">
            <?php if (empty($materiels)): ?>
                <div class="col-12 text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                        <h3>Aucun matériel disponible</h3>
                        <p class="text-muted">Revenez plus tard pour découvrir notre catalogue.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($materiels as $index => $materiel): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 materiel-item" 
                         data-type="<?= htmlspecialchars($materiel['type']) ?>" 
                         data-name="<?= htmlspecialchars($materiel['nom']) ?>"
                         data-quantity="<?= $materiel['quantite_disponible'] ?>">
                        <div class="card h-100 hover-effect">
                            <a href="materiel_details.php?id=<?= $materiel['id_materiel'] ?>" 
                               class="card-link"></a>
                            <div class="card-img-wrapper">
                                <?php $imagePath = '../admin/' . $materiel['photo']; ?>
                                <img src="<?= file_exists($imagePath) ? $imagePath : 'default.png' ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($materiel['nom']) ?>" 
                                     onerror="this.src='default.png'"
                                     loading="lazy">
                                <?php if ($materiel['quantite_disponible'] <= 3): ?>
                                    <span class="stock-badge low-stock">Stock faible</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($materiel['nom']) ?></h5>
                                <p class="card-text">
                                    <span class="badge-container">
                                        <span class="badge <?= $materiel['quantite_disponible'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <i class="fas fa-boxes me-1"></i><?= $materiel['quantite_disponible'] ?> disponible<?= $materiel['quantite_disponible'] > 1 ? 's' : '' ?>
                                        </span>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($materiel['type']) ?>
                                        </span>
                                    </span>
                                </p>
                                <a href="materiel_details.php?id=<?= $materiel['id_materiel'] ?>" 
                                   class="btn btn-primary mt-auto">
                                    <i class="fas fa-info-circle me-1"></i>Voir les détails
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="../../assets/js/home.js"></script>
</body>
</html>