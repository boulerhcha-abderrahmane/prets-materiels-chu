<?php
require_once '../../config/config.php';
session_start();

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.']);
    exit();
}

// Vérifier si la catégorie est fournie
if (!isset($_POST['category']) || empty($_POST['category'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Catégorie non fournie.']);
    exit();
}

$category = $_POST['category'];

// Définir les types d'actions à supprimer en fonction de la catégorie
$types_to_delete = [];

switch ($category) {
    case 'utilisateur':
        $types_to_delete = ['ajout_utilisateur', 'modification_utilisateur', 'suppression_utilisateur'];
        break;
    case 'materiel':
        $types_to_delete = ['ajout_materiel', 'modification_materiel', 'suppression_materiel'];
        break;
    case 'demandes':
        $types_to_delete = ['validation_demande', 'refus_demande', 'validation_retour'];
        break;
    case 'email':
        $types_to_delete = ['ajout_email', 'suppression_email'];
        break;
    case 'administrateur':
        $types_to_delete = ['ajout_admin', 'modification_admin', 'suppression_admin'];
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Catégorie non valide.']);
        exit();
}

try {
    // Préparer la requête avec la liste des types d'actions
    $placeholders = implode(',', array_fill(0, count($types_to_delete), '?'));
    
    // Supprimer les actions de la base de données
    $sql = "DELETE FROM historique_actions WHERE type_action IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    // Exécuter la requête avec les types d'actions comme paramètres
    $stmt->execute($types_to_delete);
    
    // Vérifier si des lignes ont été affectées
    $rows_affected = $stmt->rowCount();
    
    if ($rows_affected > 0) {
        // Ajouter une entrée dans l'historique pour la suppression massive
        $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, details, date_action) 
                          VALUES (:id_admin, :type_action, :details, NOW())";
        $stmt_historique = $pdo->prepare($sql_historique);
        $stmt_historique->execute([
            'id_admin' => $_SESSION['admin_id'],
            'type_action' => 'suppression_historique',
            'details' => 'Suppression massive de ' . $rows_affected . ' entrées de l\'historique (Catégorie: ' . $category . ')'
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $rows_affected . ' actions ont été supprimées.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Aucune action à supprimer dans cette catégorie.']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
} 