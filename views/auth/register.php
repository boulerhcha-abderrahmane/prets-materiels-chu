<?php
// Inclusion des fichiers de configuration et de la fonction d'upload de photo
require_once '../../config/config.php';
require_once 'upload_photo.php';

// Définir le type de contenu de la réponse en JSON
header('Content-Type: application/json');

// Récupération des données du formulaire
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$nom = $_POST['nom'] ?? '';
$prenom = $_POST['prenom'] ?? '';
$role = $_POST['role'] ?? '';

try {
    // Démarrer une transaction pour garantir l'intégrité des données
    $pdo->beginTransaction();

    // Vérifier si l'email est autorisé à créer un compte et récupérer son id_email
    $stmt = $pdo->prepare("
        SELECT id_email 
        FROM Email_autorise
        WHERE email = ? 
    ");
    $stmt->execute([$email]);
    $idEmail = $stmt->fetchColumn();

    // Si l'email n'est pas autorisé, lever une exception
    if (!$idEmail) {
        throw new Exception("Vous n'avez pas l'autorisation de créer un compte");
    }

    // Vérifier si l'id_email est déjà utilisé dans la table Utilisateur
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM UTILISATEUR 
        WHERE id_email = ?
    ");
    $stmt->execute([$idEmail]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cet email est déjà utilisé');
    }

    // Gérer l'upload de la photo si elle est présente dans la requête
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadPhoto($_FILES['photo']);
        // Si l'upload échoue, lever une exception avec le message d'erreur
        if (!$uploadResult['success']) {
            throw new Exception($uploadResult['message']);
        }
        $photoPath = $uploadResult['filename'];
    }

    // Créer le compte utilisateur avec les informations fournies
    $stmt = $pdo->prepare("
        INSERT INTO UTILISATEUR (
            id_email,
            mot_de_passe, 
            nom, 
            prenom, 
            role, 
            photo,
            date_creation
        ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$idEmail, $password, $nom, $prenom, $role, $photoPath]);

    // Créer une notification de bienvenue pour l'utilisateur
    $userId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        INSERT INTO notification (
            message,
            id_utilisateur,
            type
        ) VALUES (
            ?, ?, 'information'
        )
    ");
    $message = "Bienvenue sur la plateforme de gestion des prêts ! Votre compte a été créé avec succès.";
    $stmt->execute([$message, $userId]);

    // Valider la transaction
    $pdo->commit();
    // Retourner une réponse JSON indiquant le succès de l'opération
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    $pdo->rollBack();
    // Retourner une réponse JSON avec le message d'erreur
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 