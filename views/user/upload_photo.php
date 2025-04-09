<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if (!isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
    exit;
}

try {
    $file = $_FILES['photo'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Format de fichier non autorisé');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Fichier trop volumineux');
    }

    $uploadDir = '../../uploads/user_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $photoPath = 'uploads/user_photos/' . $filename;
        
        $stmt = $pdo->prepare("UPDATE utilisateur SET photo = ? WHERE id_utilisateur = ?");
        $stmt->execute([$photoPath, $_SESSION['user_id']]);

        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erreur lors du téléchargement');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}