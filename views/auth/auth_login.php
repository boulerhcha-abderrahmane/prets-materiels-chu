<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

try {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérifier d'abord si l'email existe et récupérer les informations utilisateur
    $stmt = $pdo->prepare("
        SELECT u.* 
        FROM `UTILISATEUR` u
        JOIN `EMAIL_AUTORISE` e ON u.id_email = e.id_email
        WHERE e.`email` = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => "Email ou mot de passe incorrect"
        ]);
        exit;
    }

    // Vérifier le mot de passe
    if ($password === $user['mot_de_passe']) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['user_type'] = 'user';
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'redirect' => '../user/home.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => " mot de passe incorrect"
        ]);
    }
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Erreur lors de la connexion"
    ]);
} 