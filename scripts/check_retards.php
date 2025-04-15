<?php
// Définir le fuseau horaire pour Casablanca
date_default_timezone_set('Africa/Casablanca');

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/EmailServices.php';
require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../config/config.php';

// Fonction pour vérifier les retards et envoyer les notifications
function checkRetardsEtNotifier() {
    global $pdo, $emailService;
    
    // Récupérer tous les utilisateurs avec des matériels en retard
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, e.email
        FROM utilisateur u
        JOIN email_autorise e ON u.id_email = e.id_email
        JOIN demande_pret d ON u.id_utilisateur = d.id_utilisateur
        WHERE d.statut = 'valide en attente retour'
        AND d.date_retour_prevue < NOW()
    ");
    $stmt->execute();
    $utilisateurs_en_retard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($utilisateurs_en_retard as $user) {
        // Récupérer les matériels en retard pour cet utilisateur
        $stmt = $pdo->prepare("
            SELECT m.nom as nom_materiel, d.date_retour_prevue, d.quantite
            FROM demande_pret d
            JOIN materiel m ON d.id_materiel = m.id_materiel
            WHERE d.id_utilisateur = ? 
            AND d.statut = 'valide en attente retour'
            AND d.date_retour_prevue < NOW()
        ");
        $stmt->execute([$user['id_utilisateur']]);
        $materiels_en_retard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($materiels_en_retard) > 0) {
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
            $stmt->execute([$user['id_utilisateur'], $message_notification]);
            
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
            
            // Envoyer l'email de blocage à l'utilisateur
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
            
            // Envoyer l'email à l'utilisateur
            $emailResult = $emailService->sendEmail(
                $user['email'],
                'Suspension des emprunts - Matériels en retard',
                $messageHtml
            );
            
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
                
                // Envoyer l'email aux administrateurs
                $emailService->sendEmail(
                    $admin_emails,
                    'ALERTE - Utilisateur suspendu pour matériels non retournés',
                    $admin_messageHtml,
                    true // Utiliser BCC
                );
            }
            
            // Enregistrer dans les logs
            error_log("Vérification des retards - " . date('Y-m-d H:i:s') . " - Utilisateur: " . $user['id_utilisateur'] . " - Email envoyé");
        }
    }
}

// Exécuter la vérification
try {
    checkRetardsEtNotifier();
    error_log("Vérification des retards terminée avec succès - " . date('Y-m-d H:i:s'));
} catch (Exception $e) {
    error_log("Erreur lors de la vérification des retards: " . $e->getMessage());
} 