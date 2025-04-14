<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/api_config.php';
require_once '../../config/EmailServices.php';

// Vérifier que l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    error_log("Tentative d'accès à process_request.php sans session admin valide");
    header('Location: ../../index.php');
    exit();
}

// Validation des requêtes POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Méthode non autorisée pour process_request.php");
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Méthode non autorisée');
}

// Vérification des paramètres requis
if (!isset($_POST['id_demande']) || !isset($_POST['action'])) {
    error_log("Paramètres manquants dans process_request.php");
    $_SESSION['process_request_error'] = "Données requises manquantes";
    header('Location: admin_dashboard.php');
    exit();
}

// Récupération des paramètres
$id_demande = filter_input(INPUT_POST, 'id_demande', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$commentaire = filter_input(INPUT_POST, 'commentaire', FILTER_SANITIZE_STRING);

// Validation des paramètres
if (!$id_demande || !in_array($action, ['approve', 'reject'])) {
    error_log("Paramètres invalides dans process_request.php");
    $_SESSION['process_request_error'] = "Données invalides";
    header('Location: admin_dashboard.php');
    exit();
}

// Initialisation du service d'email
$emailService = new EmailService(RESEND_API_KEY, RESEND_FROM_EMAIL);

try {
    // Récupérer les détails de la demande
    $stmt = $pdo->prepare("
        SELECT 
            d.id_utilisateur, 
            d.id_materiel,
            d.quantite,
            m.nom as nom_materiel,
            m.type as type_materiel,
            u.prenom as prenom_utilisateur, 
            u.nom as nom_utilisateur 
        FROM demande_pret d 
        JOIN materiel m ON d.id_materiel = m.id_materiel 
        JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur
        WHERE d.id_demande = ?
    ");
    $stmt->execute([$id_demande]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        throw new Exception("Demande non trouvée");
    }

    // Préparation des variables pour notifications et mises à jour
    $id_utilisateur = $demande['id_utilisateur'];
    $id_materiel = $demande['id_materiel'];
    $quantite = $demande['quantite'];
    $nom_materiel = $demande['nom_materiel'];
    $type_materiel = $demande['type_materiel'];
    $nom_complet_utilisateur = $demande['prenom_utilisateur'] . ' ' . $demande['nom_utilisateur'];

    // Traitement en fonction de l'action
    if ($action === 'approve') {
        // Vérifier si le stock est suffisant
        $stmt = $pdo->prepare("SELECT quantite_disponible FROM materiel WHERE id_materiel = ?");
        $stmt->execute([$id_materiel]);
        $stock = $stmt->fetchColumn();
        
        if ($stock < $quantite) {
            throw new Exception("Stock insuffisant pour valider la demande");
        }

        // Déterminer le statut en fonction du type de matériel
        $new_status = ($type_materiel === 'consommable') ? 'validé' : 'valide en attente retour';
        $notification_message = "Votre demande pour " . $quantite . " " . $nom_materiel . 
                              ($type_materiel === 'consommable' ? 
                               " a été acceptée" : 
                               " a été acceptée et en attente le retour après la fin de la journée");
        $notification_type = "validation";
        
        // Mise à jour du stock et de la date de retour prévue
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE materiel 
            SET quantite_disponible = quantite_disponible - :quantite
            WHERE id_materiel = :id_materiel
        ");
        $stmt->execute([
            ':quantite' => $quantite,
            ':id_materiel' => $id_materiel
        ]);
        
        // Mise à jour de la date de retour pour les non-consommables
        if ($type_materiel === 'non-consommable') {
            $stmt = $pdo->prepare("
                UPDATE demande_pret 
                SET date_retour_prevue = DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:59')
                WHERE id_demande = ?
            ");
            $stmt->execute([$id_demande]);
        }
        
        // Enregistrement dans l'historique
        $details = "validation de la demande de " . $nom_complet_utilisateur . 
                   " pour " . $quantite . " " . $nom_materiel;
        $type_action = 'validation_demande';
    } else { // reject
        $new_status = 'refusé';
        $notification_message = "Votre demande pour " . $quantite . " " . $nom_materiel . 
                               " a été refusée" . ($commentaire ? " : " . $commentaire : "");
        $notification_type = "refus";
        
        // Enregistrement dans l'historique
        $details = "refus de la demande de " . $nom_complet_utilisateur . 
                   " pour " . $quantite . " " . $nom_materiel . 
                   ". Motif: " . ($commentaire ?: 'Non spécifié');
        $type_action = 'refus_demande';
    }
    
    // Début de la transaction si ce n'est pas déjà fait
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }
    
    // Mise à jour du statut de la demande
    $stmt = $pdo->prepare("
        UPDATE demande_pret 
        SET statut = :statut,
            commentaire = :commentaire 
        WHERE id_demande = :id_demande
    ");
    $stmt->execute([
        ':statut' => $new_status,
        ':commentaire' => $commentaire,
        ':id_demande' => $id_demande
    ]);
    
    // Création de la notification
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_utilisateur, message, type, date_envoi, lu) 
        VALUES (?, ?, ?, NOW(), FALSE)
    ");
    $stmt->execute([
        $id_utilisateur,
        $notification_message,
        $notification_type
    ]);
    
    // Enregistrement dans l'historique des actions
    $stmt = $pdo->prepare("
        INSERT INTO historique_actions 
        (id_admin, type_action, date_action, details) 
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $type_action,
        $details
    ]);
    
    // Validation de la transaction 
    $pdo->commit();
    
    // Envoi de l'email
    $stmt = $pdo->prepare("
        SELECT e.email, u.prenom, u.nom
        FROM utilisateur u
        JOIN email_autorise e ON u.id_email = e.id_email
        WHERE u.id_utilisateur = ?
    ");
    $stmt->execute([$id_utilisateur]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Construction du message
        $messageHtml = "Bonjour " . htmlspecialchars($user['prenom']) . ",<br><br>";
        
        if ($action === 'approve') {
            $messageHtml .= "Votre demande d'emprunt a été approuvée.<br>";
            $subject = "Demande de prêt approuvée";
        } else {
            $messageHtml .= "Votre demande d'emprunt a été refusée.<br>";
            $subject = "Demande de prêt refusée";
        }
        
        $messageHtml .= "Détails de la demande :<br>";
        $messageHtml .= "- Matériel : " . htmlspecialchars($nom_materiel) . "<br>";
        $messageHtml .= "- Quantité : " . htmlspecialchars($quantite) . "<br>";
        
        if ($commentaire) {
            $messageHtml .= "- " . ($action === 'approve' ? "Commentaire" : "Motif du refus") . 
                           " : " . htmlspecialchars($commentaire) . "<br>";
        }
        
        $messageHtml .= "<br>";
        
        if ($action === 'approve') {
            $messageHtml .= "Vous pouvez maintenant venir récupérer le matériel.";
        } else {
            $messageHtml .= "Pour plus d'informations, veuillez contacter le service de gestion des prêts.";
        }
        
        $messageHtml .= "<br><br>Cordialement,<br>Le Systeme de Gestion des Prêts de materiels";
        
        // Envoi de l'email
        $emailResult = $emailService->sendEmail($user['email'], $subject, $messageHtml);
        
        if (!$emailResult['success']) {
            error_log("Erreur d'envoi d'email: " . $emailResult['response']);
            $_SESSION['process_request_success'] = "Demande traitée, mais l'email n'a pas pu être envoyé";
        } else {
            $_SESSION['process_request_success'] = "Demande " . 
                                                ($action === 'approve' ? "approuvée" : "refusée") . 
                                                " et email envoyé";
        }
    } else {
        $_SESSION['process_request_success'] = "Demande traitée, mais l'email n'a pas pu être envoyé (utilisateur non trouvé)";
    }
    
} catch (Exception $e) {
    // Annulation de la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur dans process_request.php: " . $e->getMessage());
    $_SESSION['process_request_error'] = "Erreur lors du traitement de la demande: " . $e->getMessage();
}

// Redirection vers le tableau de bord
header('Location: admin_dashboard.php');
exit();