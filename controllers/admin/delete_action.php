<?php
require_once '../../config/config.php';
session_start();

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.']);
    exit();
}

// Vérifier si l'ID de l'action est fourni
if (!isset($_POST['action_id']) || empty($_POST['action_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de l\'action non fourni.']);
    exit();
}

$action_id = intval($_POST['action_id']);

try {
    // Supprimer l'action de la base de données
    $sql = "DELETE FROM historique_actions WHERE id_action = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $action_id]);
    
    // Vérifier si une ligne a été affectée
    if ($stmt->rowCount() > 0) {
        // Ajouter une entrée dans l'historique pour la suppression
        $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, details, date_action) 
                          VALUES (:id_admin, :type_action, :details, NOW())";
        $stmt_historique = $pdo->prepare($sql_historique);
        $stmt_historique->execute([
            'id_admin' => $_SESSION['admin_id'],
            'type_action' => 'suppression_historique',
            'details' => 'Suppression d\'une entrée de l\'historique (ID: ' . $action_id . ')'
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action non trouvée ou déjà supprimée.']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
} 