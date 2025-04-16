<!-- Footer --> 
<?php
// Inclure la configuration des chemins si elle n'a pas déjà été incluse
if (!isset($css_url)) {
    // Détermine où est le fichier de configuration selon le contexte
    $paths_file = file_exists('../config/paths.php') ? '../config/paths.php' : './config/paths.php';
    include_once $paths_file;
}
?>
<link rel="stylesheet" href="<?php echo $css_url; ?>footer.css">
<style>
    /* Override pour réduire la hauteur du footer */
    .footer-container {
        padding: 1.5rem 0 0.5rem !important;
    }
    .footer-content {
        padding-bottom: 1rem !important;
        gap: 1rem !important;
    }
    .footer-section {
        margin-bottom: 0.5rem !important;
    }
    .footer-section h3 {
        margin-bottom: 0.5rem !important;
        font-size: 1.1rem !important;
    }
    .footer-section p, .footer-section li {
        margin-bottom: 0.3rem !important;
        font-size: 0.9rem !important;
    }
    .footer-bottom {
        padding: 0.5rem 0 !important;
    }
</style>
<footer class="footer-container">
    <div class="footer-content">
        <div class="footer-section about">
            <h3>Gestion des Prêts Matériels</h3>
            <p>Plateforme professionnelle de gestion des prêts de matériels. Suivez vos demandes et historiques en toute simplicité.</p>
        </div>
        
        <?php if(basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
        <div class="footer-section links">
            <h3>Liens Rapides</h3>
            <ul>
                <li><a href="#features">Fonctionnalités</a></li>
                <li><a href="#how-it-works">Processus</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="views/auth/login.php">Connexion Utilisateur</a></li>
            </ul>
        </div>
        <?php else: ?>
        <div class="footer-section links">
            <h3>Liens Rapides</h3>
            <ul>
                <li><a href="home.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li><a href="historique_pret.php"><i class="fas fa-history me-1"></i>Historiques</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell me-1"></i>Notifications</a></li>
                <li><a href="profile.php"><i class="fas fa-user me-1"></i>Profil</a></li>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="footer-section contact">
            <h3>Contact</h3>
            <p><i class="fas fa-map-marker-alt"></i> Paris, France</p>
            <p><i class="fas fa-phone"></i> 0766662217</p>
            <p><i class="fas fa-globe"></i> www.gestprets.com</p>
            <p><i class="fas fa-envelope"></i> gestion_prets@gmail.com</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Gestion des Prêts Matériels. Tous droits réservés.</p>
    </div>
</footer>
