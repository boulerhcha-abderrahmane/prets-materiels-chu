<!-- Footer --> <link rel="stylesheet" href="http://localhost/prets_materiels/assets/css/footer.css">
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
