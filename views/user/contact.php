<?php
session_start();
require_once '../../config/config.php';




if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contactez-nous pour toute question concernant le système de gestion des prêts">
    <meta name="theme-color" content="#3498db">
    <title>Contact - Système de Gestion des Prêts</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/contact.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <br><br>
    <main class="container position-relative">
        <div class="contact-container" role="region" aria-label="Informations de contact">
            <h1>Nous Contacter</h1>
            <div class="contact-info">
                <article class="contact-item">
                    <div class="contact-icon" aria-hidden="true">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-text">
                        <strong>Email</strong>
                        <a href="mailto:gestion_prets@gmail.com" aria-label="Envoyer un email">
                            <i class="fas fa-envelope-open-text"></i>
                            gestion_prets@gmail.com
                        </a>
                    </div>
                </article>

                <article class="contact-item">
                    <div class="contact-icon" aria-hidden="true">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-text">
                        <strong>Téléphone</strong>
                        <a href="tel:0766662217" aria-label="Appeler le numéro">
                            <i class="fas fa-phone-volume"></i>
                            07 66 66 22 17
                        </a>
                    </div>
                </article>

                <article class="contact-item">
                    <div class="contact-icon" aria-hidden="true">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="contact-text">
                        <strong>Site Web</strong>
                        <a href="http://www.gestprets.com" target="_blank" rel="noopener noreferrer" aria-label="Visiter notre site web">
                            <i class="fas fa-external-link-alt"></i>
                            www.gestprets.com
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </main>

    <button class="back-to-top" aria-label="Retour en haut de la page" title="Retour en haut">
        <i class="fas fa-chevron-up" aria-hidden="true"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="../../assets/js/contact.js" defer></script>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>