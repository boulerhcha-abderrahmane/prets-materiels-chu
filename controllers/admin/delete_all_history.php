<?php
require_once '../../config/config.php';
session_start();

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.']);
    exit();
}

try {
    // Compter le nombre d'entrées avant suppression
    $count_sql = "SELECT COUNT(*) as total FROM historique_actions";
    $count_stmt = $pdo->query($count_sql);
    $total_actions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Supprimer toutes les actions de la base de données
    $sql = "DELETE FROM historique_actions";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Réinitialiser l'auto-incrémentation
    $reset_sql = "ALTER TABLE historique_actions AUTO_INCREMENT = 1";
    $pdo->exec($reset_sql);
    
    // Ajouter une entrée dans l'historique pour la suppression massive
    $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, details, date_action) 
                      VALUES (:id_admin, :type_action, :details, NOW())";
    $stmt_historique = $pdo->prepare($sql_historique);
    $stmt_historique->execute([
        'id_admin' => $_SESSION['admin_id'],
        'type_action' => 'suppression_historique',
        'details' => 'Suppression complète de l\'historique (' . $total_actions . ' entrées)'
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Historique complètement vidé (' . $total_actions . ' entrées supprimées).']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
} 