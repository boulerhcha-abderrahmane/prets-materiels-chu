<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || $data['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Récupérer le chemin de la photo actuelle
    $stmt = $pdo->prepare("SELECT photo FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $photo = $stmt->fetchColumn();

    // Si une photo existe, la supprimer physiquement
    if ($photo && file_exists('../../' . $photo)) {
        unlink('../../' . $photo);
    }

    // Mettre à jour la base de données
    $stmt = $pdo->prepare("UPDATE utilisateur SET photo = NULL WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Photo supprimée avec succès']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la photo']);
}