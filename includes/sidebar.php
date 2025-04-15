<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    error_log("admin_id is not set in the session");
    exit;
}

// Compter les notifications
$notif_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM NOTIFICATION n
        JOIN NOTIFICATION_ADMINISTRATEUR na ON n.id_notification = na.id_notification
        WHERE na.id_admin = ? AND n.lu = FALSE AND n.type IN ('demande', 'rappel')
    ");
    if ($stmt) {
        $stmt->execute([$_SESSION['admin_id']]);
        $notif_count = $stmt->fetchColumn();
    }
} catch(PDOException $e) {
    error_log("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets../../../../assets/css/sidebar.css">
    <style></style>
</head>
<body>
    <!-- Menu hamburger -->
    <div class="menu-container">
        <button type="button" class="hamburger-menu" id="menu-toggle" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <!-- Overlay pour fermer le menu sur mobile -->
    <div class="sidebar-overlay"></div>

    <div class="sidebar">
        <h2>
            <?php echo isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'chef' 
                  ? 'Chef Admin Panel' 
                  : 'Admin Panel'; ?>
        </h2>
        <ul>
            <li><a href="admin_dashboard.php" aria-label="Dashboard" <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' || basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="material_management.php" aria-label="Gestion Matériels" <?php echo (basename($_SERVER['PHP_SELF']) == 'material_management.php') ? 'class="active"' : ''; ?>><i class="fas fa-box"></i> Gestion Matériels</a></li>
            <li><a href="gestion_retours.php" aria-label="Gestion Retours" <?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_retours.php') ? 'class="active"' : ''; ?>><i class="fas fa-undo"></i> Gestion Retours</a></li>
            <li><a href="manage_users.php" aria-label="Gestion Utilisateurs" <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Gestion Utilisateurs</a></li>
            <li><a href="manage_emails.php" aria-label="Emails Autorisés" <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_emails.php') ? 'class="active"' : ''; ?>><i class="fas fa-envelope"></i> Emails Autorisés</a></li>
            <li><a href="historique.php" aria-label="Historiques" <?php echo (basename($_SERVER['PHP_SELF']) == 'historique.php') ? 'class="active"' : ''; ?>><i class="fas fa-history"></i> Historiques</a></li>
            <li class="nav-item">
                <a href="notifications.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php if($notif_count > 0): ?>
                        <span class="notification-badge" style="animation: pulse 1s infinite;"><?= $notif_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'chef'): ?>
                <li><a href="manage_admin.php" aria-label="Gestion Admins" <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_admin.php') ? 'class="active"' : ''; ?>><i class="fas fa-user-shield"></i> Gestion Admins</a></li>
                <li><a href="historique_actions.php" aria-label="Historiques Actions" <?php echo (basename($_SERVER['PHP_SELF']) == 'historique_actions.php') ? 'class="active"' : ''; ?>><i class="fas fa-box"></i> Historiques Actions</a></li>
            <?php endif; ?>
            <li><a href="materiaux_defectueux.php" aria-label="Matériaux Défectueux"><i class="fas fa-exclamation-triangle"></i> Matériaux Défectueux</a></li>
            <li><a href="../logout.php?admin_id=<?php echo $_SESSION['admin_id']; ?>" aria-label="Déconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </div>

    <!-- Global Variables -->
    

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/sidebar.js"></script>
</body>
</html>