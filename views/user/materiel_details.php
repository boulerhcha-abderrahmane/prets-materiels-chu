<?php
session_start();
require_once '../../config/EmailServices.php';
require_once '../../config/api_config.php';
require_once '../../config/config.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom 
    FROM utilisateur u 
    WHERE u.id_utilisateur = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch();

// Stocker les informations dans la session
$_SESSION['nom'] = $user_info['nom'];
$_SESSION['prenom'] = $user_info['prenom'];

// Vérifier si l'ID est passé dans l'URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Récupérer les détails du matériel depuis la base de données
    $stmt = $pdo->prepare("SELECT * FROM materiel WHERE id_materiel = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $materiel = $stmt->fetch();

    // Vérifier si le matériel existe
    if (!$materiel) {
        echo "Matériel non trouvé.";
        exit;
    }
    
    // Récupérer la photo depuis la table materiel
    $imagePath = '../admin/' . $materiel['photo'];
    $photo = file_exists($imagePath) ? $imagePath : 'default.png';
    
} else {
    echo "Aucun ID de matériel fourni.";
    exit;
}

// Récupérer la quantité disponible
$quantite_disponible = $materiel['quantite_disponible'];

// Vérifier si l'utilisateur a des matériels non retournés
$stmt = $pdo->prepare("
    SELECT m.nom as nom_materiel, d.date_retour_prevue, d.quantite
    FROM demande_pret d
    JOIN materiel m ON d.id_materiel = m.id_materiel
    WHERE d.id_utilisateur = ? 
    AND d.statut = 'valide en attente retour'
    AND d.date_retour_prevue < NOW()
");
$stmt->execute([$_SESSION['user_id']]);
$materiels_en_retard = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pretDisponible = true;
$messageDisponibilite = '';
if (count($materiels_en_retard) > 0) {
    $pretDisponible = false;
    $messageDisponibilite = "<div class='alert alert-danger'>
        <i class='fas fa-exclamation-triangle'></i> 
        Prêt non disponible : Vous avez des matériels non retournés après la date limite. Veuillez retourner ces matériels pour pouvoir effectuer de nouveaux emprunts.
    </div>";
    
    if (!isset($_SESSION['blocage_email_envoye'])) {
        // Récupérer l'email de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT e.email, u.nom, u.prenom 
            FROM utilisateur u
            JOIN email_autorise e ON u.id_email = e.id_email
            WHERE u.id_utilisateur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Créer notification pour l'utilisateur
        $message_notification = "Votre accès aux emprunts a été suspendu en raison des matériels non retournés : ";
        foreach ($materiels_en_retard as $materiel_retard) {
            $message_notification .= $materiel_retard['quantite'] . " x " . $materiel_retard['nom_materiel'] . ", ";
        }
        $message_notification = rtrim($message_notification, ", ") . ".";

        $stmt = $pdo->prepare("
            INSERT INTO notification (id_utilisateur, message, type)
            VALUES (?, ?, 'rappel')
        ");
        $stmt->execute([$_SESSION['user_id'], $message_notification]);
        
        // Créer notification pour les administrateurs
        $stmt = $pdo->prepare("
            INSERT INTO notification (message, type)
            VALUES (?, 'rappel')
        ");
        $message_admin = "L'utilisateur " . $user['prenom'] . " " . $user['nom'] . " a été suspendu pour les matériels non retournés suivants :\n";
        foreach ($materiels_en_retard as $materiel_retard) {
            $message_admin .= "- " . $materiel_retard['quantite'] . " x " . $materiel_retard['nom_materiel'] . 
                             " (Date de retour prévue : " . date('d/m/Y', strtotime($materiel_retard['date_retour_prevue'])) . ")\n";
        }
        $stmt->execute([$message_admin]);
        $id_notification = $pdo->lastInsertId();
        
        // Lier la notification à tous les administrateurs
        $stmt = $pdo->prepare("
            INSERT INTO notification_administrateur (id_notification, id_admin)
            SELECT ?, id_admin FROM administrateur
        ");
        $stmt->execute([$id_notification]);
        
        // Envoyer l'email de blocage
        $messageHtml = "Bonjour " . $user['prenom'] . ",<br><br>";
        $messageHtml .= "Nous vous informons que votre accès aux emprunts a été temporairement suspendu en raison des matériels suivants non retournés après la date limite :<br><br>";
        $messageHtml .= "<ul>";
        foreach ($materiels_en_retard as $materiel_retard) {
            $messageHtml .= "<li>" . $materiel_retard['quantite'] . " x " . $materiel_retard['nom_materiel'] . 
                           " (Date de retour prévue : " . date('d/m/Y', strtotime($materiel_retard['date_retour_prevue'])) . ")</li>";
        }
        $messageHtml .= "</ul><br>";
        $messageHtml .= "Pour réactiver votre accès au service de prêts, nous vous prions de bien vouloir :<br>";
        $messageHtml .= "- Nous fournir une explication concernant ce retard<br>";
        $messageHtml .= "- Retourner les matériels concernés dans les plus brefs délais<br><br>";
        $messageHtml .= "Vous pouvez répondre directement à cet email pour nous donner plus d'informations sur votre situation.<br><br>";
        $messageHtml .= "Cordialement,<br>";
        $messageHtml .= "Le Systeme de Gestion des Prêts de materiels";
        
        // Ajouter des logs de diagnostic
        error_log("==== ENVOI EMAIL BLOCAGE ====");
        error_log("Email utilisateur: " . ($user['email'] ?? 'Non défini'));
        
        $emailResult = $emailService->sendEmail(
            $user['email'],
            'Suspension des emprunts - Matériels en retard',
            $messageHtml
        );
        
        // Enregistrer le résultat
        error_log("Résultat envoi email: " . json_encode($emailResult));

        if ($emailResult['success']) {
            $_SESSION['blocage_email_envoye'] = true;
            error_log("Flag blocage_email_envoye défini à true");
            
            // Récupérer les emails des administrateurs
            $stmt_admins = $pdo->query("SELECT GROUP_CONCAT(email) as emails FROM administrateur WHERE email IS NOT NULL");
            $admin_emails = $stmt_admins->fetch(PDO::FETCH_ASSOC)['emails'];
            
            if ($admin_emails) {
                // Envoyer email aux administrateurs
                $admin_messageHtml = "Bonjour,<br><br>";
                $admin_messageHtml .= "Un utilisateur a été suspendu pour des matériels non retournés :<br><br>";
                $admin_messageHtml .= "👤 <strong>Utilisateur :</strong> " . $user['prenom'] . " " . $user['nom'] . "<br>";
                $admin_messageHtml .= "📧 <strong>Email :</strong> " . $user['email'] . "<br><br>";
                $admin_messageHtml .= "<strong>Matériels en retard :</strong><br><ul>";
                
                foreach ($materiels_en_retard as $materiel_retard) {
                    $admin_messageHtml .= "<li>" . $materiel_retard['quantite'] . " x " . $materiel_retard['nom_materiel'] . 
                                          " (Date de retour prévue : " . date('d/m/Y', strtotime($materiel_retard['date_retour_prevue'])) . ")</li>";
                }
                
                $admin_messageHtml .= "</ul><br>";
                $admin_messageHtml .= "L'utilisateur a été informé et son accès aux emprunts a été suspendu.<br><br>";
                $admin_messageHtml .= "Cordialement,<br>";
                $admin_messageHtml .= "Le Système de Gestion des Prêts de materiels";
                
                $adminEmailResult = $emailService->sendEmail(
                    $admin_emails,
                    'ALERTE - Utilisateur suspendu pour matériels non retournés',
                    $admin_messageHtml,
                    true // Utiliser BCC
                );
                
                error_log("Résultat envoi email aux admins: " . json_encode($adminEmailResult));
            }
        } else {
            error_log("ÉCHEC envoi email blocage: " . ($emailResult['error'] ?? 'Erreur inconnue'));
        }
    }
} else {
    $messageDisponibilite = "<div></div>";
    unset($_SESSION['blocage_email_envoye']);
}

// Traitement de la demande de prêt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$pretDisponible) {
        $_SESSION['error'] = "Vous ne pouvez pas faire de demande car vous avez des matériels en retard.";
        header("Location: materiel_details.php?id=" . $materiel['id_materiel']);
        exit;
    }

    // Validation de la quantité
    if (!isset($_POST['quantite']) || $_POST['quantite'] <= 0 || $_POST['quantite'] > $quantite_disponible) {
        $_SESSION['error'] = "Quantité invalide.";
        header("Location: materiel_details.php?id=" . $materiel['id_materiel']);
        exit;
    }

    $quantite = intval($_POST['quantite']);
    
    try {
        // Récupérer les emails des administrateurs avant la transaction
        $stmt_admins = $pdo->query("SELECT GROUP_CONCAT(email) as emails FROM administrateur WHERE email IS NOT NULL");
        $admin_emails = $stmt_admins->fetch(PDO::FETCH_ASSOC)['emails'];
        
        $pdo->beginTransaction();
        
        // 1. Création de la demande
        $stmt = $pdo->prepare("
            INSERT INTO demande_pret (
                id_utilisateur, id_materiel, quantite, date_demande, statut
            ) VALUES (?, ?, ?, NOW(), 'en_attente')
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $materiel['id_materiel'], $quantite])) {
            // 2. Créer notification pour les administrateurs
            $message_admin = "Nouvelle demande de prêt pour " . $quantite . " " . $materiel['nom'] . 
                           " (" . $materiel['type'] . ")\nDe : " . $_SESSION['nom'] . " " . $_SESSION['prenom'] . 
                           "\n";
            
            $stmt = $pdo->prepare("
                INSERT INTO notification (message, type)
                VALUES (?, 'demande')
            ");
            $stmt->execute([$message_admin]);
            $id_notification = $pdo->lastInsertId();
            
            // 3. Lier la notification à tous les administrateurs (en une seule requête)
            $stmt = $pdo->prepare("
                INSERT INTO notification_administrateur (id_notification, id_admin)
                SELECT ?, id_admin FROM administrateur
            ");
            $stmt->execute([$id_notification]);
            
            $pdo->commit();
            
            // 4. Envoyer l'email après la transaction
            if ($admin_emails) {
                $messageHtml = "Bonjour,<br><br>";
                $messageHtml .= "Une nouvelle demande de prêt a été soumise :<br>";
                $messageHtml .= "👤 - Demandeur : " . $_SESSION['prenom'] . " " . $_SESSION['nom'] . "<br>";
                $messageHtml .= "📦 - Matériel : " . $materiel['nom'] . "<br>";
                $messageHtml .= "🏷️ - Type : " . $materiel['type'] . "<br>";
                $messageHtml .= "🔢 - Quantité : " . $quantite . "<br>";
                $messageHtml .= "Veuillez vous connecter au système pour traiter cette demande.<br><br>";
                $messageHtml .=  "Cordialement,\nLe systeme de gestion des prêts de matériels ";
                
                // Un seul envoi d'email avec tous les administrateurs en BCC
                $emailService->sendEmail(
                    $admin_emails,
                    'Nouvelle demande de prêt - ' . $materiel['nom'],
                    $messageHtml,
                    true // Utiliser BCC
                );
            }
                
            $_SESSION['message'] = "<div class='alert alert-success'><i class='fas fa-check'></i>Demande soumise avec succès</div>";
        }
        
        header("Location: materiel_details.php?id=" . $materiel['id_materiel']);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la soumission de la demande : " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de l'envoi de la demande.";
        header("Location: materiel_details.php?id=" . $materiel['id_materiel']);
        exit;
    }
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Matériel - Système de Gestion des Prêts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/materiel_details.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>
<br><br><br>
    <div class="container">
        <h2 class="page-title">Détails du Matériel</h2>
        <div class="card">
            <div class="card-body">
                <div class="product-details">
                    <div class="product-info">
                        <h1 class="product-title"><?= htmlspecialchars($materiel['nom']) ?></h1>
                        
                        <h6 class="info-label">Type:</h6>
                        <p><?= htmlspecialchars($materiel['type']) ?></p>
                        
                        <h6 class="info-label">Description:</h6>
                        <p><?= htmlspecialchars($materiel['description']) ?></p>
                        
                        <h6 class="info-label">Emplacement:</h6>
                        <p><?= htmlspecialchars($materiel['emplacement']) ?></p>
                        
                        <h6 class="info-label">Quantité Disponible:</h6>
                        <p><?= $quantite_disponible ?></p>
                        
                        <?= $messageDisponibilite ?>

                        <?php if ($quantite_disponible > 0 && $pretDisponible): ?>
                            <form id="demandeForm" action="" method="POST">
                                <input type="hidden" name="materiel_id" value="<?= $materiel['id_materiel'] ?>">
                                <input type="hidden" id="materielNomHidden" value="<?= htmlspecialchars($materiel['nom']) ?>">
                                <div class="mb-3">
                                    <label for="quantite" class="form-label">Quantité à Emprunter</label>
                                    <input type="number" id="quantite" name="quantite" class="form-control" value="1" min="1" max="<?= $quantite_disponible ?>" required>
                                </div>
                                <button type="button" class="btn btn-primary" data-action="showConfirmation">Demander un Prêt</button>
                            </form>

                            <div id="confirmationPrompt">
                                <p>Êtes-vous sûr de vouloir demander <span id="confirmationQuantite"></span> unité(s) de <span id="confirmationNom"></span> ?</p>
                                <button class="btn btn-success" data-action="submitForm">Oui</button>
                                <button class="btn btn-danger" data-action="cancelRequest">Non</button>
                            </div>

                            <div class="mt-3">
                                <?php if ($materiel['type'] === 'consommable'): ?>
                                    <div class="alert alert-info" role="alert">
                                        <strong>Règles de Retour :</strong> Pas de pénalités pour les matériels consommables.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning" role="alert">
                                        <strong>Règles de Retour :</strong> Les matériels non consommables doivent être retournés le même jour.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($quantite_disponible <= 0): ?>
                                <div class="alert alert-danger" role="alert">
                                    Ce matériel n'est pas disponible actuellement. Veuillez choisir un autre matériel.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="product-image-container">
                        <img src="<?= htmlspecialchars($photo) ?>" class="product-image" alt="<?= htmlspecialchars($materiel['nom']) ?>" onerror="this.src='default.png'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fermeture de l'alerte de confirmation après 3 secondes
            setTimeout(function() {
                const alert = document.querySelector('.alert-success');
                if (alert) {
                    alert.remove();
                }
            }, 3000);

            // Gestion du formulaire de demande
            const demandeForm = document.getElementById('demandeForm');
            const confirmationPrompt = document.getElementById('confirmationPrompt');
            const quantiteInput = document.getElementById('quantite');
            const materielNomHidden = document.getElementById('materielNomHidden');
            const showConfirmationBtn = document.querySelector('[data-action="showConfirmation"]');
            const submitFormBtn = document.querySelector('[data-action="submitForm"]');
            const cancelRequestBtn = document.querySelector('[data-action="cancelRequest"]');

            // Vérifier si tous les éléments nécessaires existent
            if (demandeForm && confirmationPrompt && quantiteInput && materielNomHidden && 
                showConfirmationBtn && submitFormBtn && cancelRequestBtn) {
                
                const materielNom = materielNomHidden.value;

                showConfirmationBtn.addEventListener('click', function() {
                    document.getElementById('confirmationQuantite').textContent = quantiteInput.value;
                    document.getElementById('confirmationNom').textContent = materielNom;
                    confirmationPrompt.classList.add('show');
                });

                submitFormBtn.addEventListener('click', function() {
                    demandeForm.submit();
                });

                cancelRequestBtn.addEventListener('click', function() {
                    confirmationPrompt.classList.remove('show');
                });
            }
        });
    </script>

    <?php
    // Section de débogage - à supprimer après dépannage
    if (isset($_GET['debug']) && $_GET['debug'] == 'true') {
        echo '<div class="container mt-5 border border-danger p-3 bg-light">';
        echo '<h3 class="text-danger">Débogage Email</h3>';
        
        // Vérification cURL
        echo '<h4>Vérification cURL</h4>';
        echo 'cURL installé: ' . (function_exists('curl_version') ? 'Oui' : 'Non') . '<br>';
        if (function_exists('curl_version')) {
            $curl_version = curl_version();
            echo 'Version cURL: ' . $curl_version['version'] . '<br>';
            echo 'SSL Version: ' . $curl_version['ssl_version'] . '<br>';
        }
        
        // État du serveur Resend
        echo '<h4>Test de connexion à l\'API Resend</h4>';
        $ch = curl_init('https://api.resend.com/api/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo 'Code réponse: ' . $httpCode . '<br>';
        echo 'Réponse: ' . htmlspecialchars($result) . '<br>';
        
        // Vérification de l'utilisateur et de son email
        echo '<h4>Vérification Email Utilisateur</h4>';
        $stmt = $pdo->prepare("
            SELECT e.email, u.nom, u.prenom 
            FROM utilisateur u
            LEFT JOIN email_autorise e ON u.id_email = e.id_email
            WHERE u.id_utilisateur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_debug = $stmt->fetch();
        
        echo 'ID Utilisateur: ' . $_SESSION['user_id'] . '<br>';
        echo 'Nom: ' . htmlspecialchars($user_debug['nom'] ?? 'Non défini') . '<br>';
        echo 'Prénom: ' . htmlspecialchars($user_debug['prenom'] ?? 'Non défini') . '<br>';
        echo 'Email: ' . htmlspecialchars($user_debug['email'] ?? 'NON DÉFINI - PROBLÈME') . '<br>';
        
        if (empty($user_debug['email'])) {
            echo '<div class="alert alert-danger">L\'utilisateur n\'a pas d\'email associé!</div>';
        }
        
        // Test d'envoi d'email
        if (isset($_POST['test_email']) && !empty($user_debug['email'])) {
            echo '<h4>Résultat du test d\'envoi</h4>';
            $message_test = "Ceci est un email de test pour vérifier que le système d'envoi fonctionne correctement.<br><br>";
            $message_test .= "Si vous recevez cet email, c'est que tout fonctionne comme prévu.<br><br>";
            $message_test .= "Date/heure: " . date('Y-m-d H:i:s');
            
            $test_result = $emailService->sendEmail(
                $user_debug['email'],
                'Test du système d\'emails',
                $message_test
            );
            
            echo '<pre>' . print_r($test_result, true) . '</pre>';
        }
        
        // Formulaire de test
        echo '<form method="post" action="materiel_details.php?id=' . $id . '&debug=true">';
        echo '<input type="hidden" name="test_email" value="1">';
        echo '<button type="submit" class="btn btn-warning mt-3">Tester l\'envoi d\'email</button>';
        echo '</form>';
        
        echo '</div>';
    }
    ?>
</body>
</html>