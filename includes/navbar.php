<?php
/**
 * Navigation principale de l'application de gestion de prêts
 * Affiche la barre de navigation avec les liens essentiels
 * et le compteur de notifications
 */

// Vérification de la session et inclusion de la configuration
if (!isset($_SESSION)) {
    session_start();
}

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header('Location: index.php');
    exit();
}

// Récupération du nombre de notifications non lues
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notification 
        WHERE id_utilisateur = :user_id 
        AND lu = FALSE 
        AND type IN ('validation', 'refus', 'retour', 'rappel')
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $notif_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    error_log("Erreur notifications: " . $e->getMessage());
    $notif_count = 0;
}

// Détermination de la page active
$current_page = basename($_SERVER['PHP_SELF']);

// Définition des chemins pour les ressources CSS et JS
$navbar_css = '/assets/css/navbar.css';
$navbar_js = '/assets/js/navbar.js';

// Ajustement des chemins en fonction de l'emplacement du fichier
$root_path = '';
if (strpos($_SERVER['SCRIPT_FILENAME'], 'views') !== false) {
    $root_path = '../../';
    $navbar_css = $root_path . 'assets/css/navbar.css';
    $navbar_js = $root_path . 'assets/js/navbar.js';
}
?>

<!-- CSS de la navbar -->
<link rel="stylesheet" href="<?= htmlspecialchars($navbar_css) ?>">

<!-- Barre de navigation -->
<?php if (!isset($hide_navbar)): ?>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <!-- Logo et titre -->
        <a class="navbar-brand" href="#">
            <i class="fas fa-box-open me-2"></i>Gestion de Prêt
        </a>
        
        <!-- Hamburger Menu pour mobile -->
        <div class="hamburger-menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </div>
        
        <!-- Liens de navigation -->
        <div class="navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($current_page === 'home.php'): ?>
                <!-- Filtre de matériaux sur la page d'accueil -->
                <li class="nav-item me-3 filter-container">
                    <select class="form-select" id="filterSelect">
                        <option value="all">Tous les matériaux</option>
                        <option value="consommable">Consommables</option>
                        <option value="non-consommable">Non Consommables</option>
                    </select>
                </li>
                <?php endif; ?>
                
                <!-- Liens principaux -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'home.php' ? 'active' : '' ?>" href="home.php">
                        <i class="fas fa-home me-1"></i>Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'historique_pret.php' ? 'active' : '' ?>" href="historique_pret.php">
                        <i class="fas fa-clipboard-list me-1"></i>Suivi de Statut
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'contact.php' ? 'active' : '' ?>" href="contact.php">
                        <i class="fas fa-envelope me-1"></i>Contact
                    </a>
                </li>
                
                <!-- Notifications -->
                <li class="nav-item">
                    <div class="notification-link">
                        <a class="nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
                            <i class="fas fa-bell"></i>
                        </a>
                        <?php if ($notif_count > 0): ?>
                            <span class="notification-badge">
                                <?= htmlspecialchars($notif_count) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </li>
                
                <!-- Profil utilisateur -->
                <li class="nav-item ms-2">
                    <a class="btn btn-profile" href="profile.php">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['prenom'] ?? '') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>  
<?php endif; ?>

<!-- Conteneur pour les notifications toast -->
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>

<!-- Script de la navbar -->
<script src="<?= htmlspecialchars($navbar_js) ?>"></script>

<script>
    // Configuration pour les communications WebSocket
    const userId = '<?php echo htmlspecialchars($_SESSION['user_id']); ?>';
</script> 