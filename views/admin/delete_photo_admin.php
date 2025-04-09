<?php
session_start();
require_once '../../config/config.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Récupérer les données JSON envoyées
$data = json_decode(file_get_contents('php://input'), true);
$admin_id = isset($data['admin_id']) ? $data['admin_id'] : null;

if (!$admin_id) {
    echo json_encode(['success' => false, 'message' => 'ID administrateur manquant']);
    exit();
}

try {
    // Récupérer le chemin de la photo actuelle
    $stmt = $pdo->prepare("SELECT photo FROM administrateur WHERE id_admin = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Administrateur non trouvé']);
        exit();
    }

    // Supprimer le fichier photo s'il existe
    if (!empty($admin['photo'])) {
        $photo_path = __DIR__ . '/../../' . $admin['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }

    // Mettre à jour la base de données
    $stmt = $pdo->prepare("UPDATE administrateur SET photo = NULL WHERE id_admin = ?");
    $stmt->execute([$admin_id]);

    echo json_encode(['success' => true, 'message' => 'Photo supprimée avec succès']);

} catch(PDOException $e) {
    error_log("Erreur lors de la suppression de la photo : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la photo']);
}