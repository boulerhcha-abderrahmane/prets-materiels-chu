    <?php
require_once '../../config/config.php';
session_start();

// Vérification si l'utilisateur est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Paramètres de pagination
$items_per_page = 10; // Nombre d'entrées par page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Assurez-vous que la page est au moins 1

// Récupérer tous les administrateurs pour le filtre
$sql_admins = "SELECT id_admin, nom, prenom FROM administrateur ORDER BY nom, prenom";
$stmt_admins = $pdo->query($sql_admins);
$all_admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

// Initialiser les filtres
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$search_keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$admin_filter = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Afficher la structure de la table
$sql_structure = "DESCRIBE historique_actions";
$result_structure = $pdo->query($sql_structure);
echo "<!-- Debug: Structure de la table: ";
print_r($result_structure->fetchAll(PDO::FETCH_ASSOC));
echo " -->";

// Afficher tous les types d'actions existants
$sql_types = "SELECT DISTINCT type_action FROM historique_actions";
$result_types = $pdo->query($sql_types);
echo "<!-- Debug: Types d'actions existants: ";
print_r($result_types->fetchAll(PDO::FETCH_COLUMN));
echo " -->";

// Structure simplifiée pour les groupes
$grouped_actions = [
    'administrateur' => [
        'title' => 'Actions Administrateurs',
        'icon' => 'fa-user-shield',
        'types' => ['ajout_admin', 'modification_admin', 'suppression_admin'],
        'actions' => []
    ],
    'utilisateur' => [
        'title' => 'Actions Utilisateurs',
        'icon' => 'fa-users',
        'types' => ['ajout_utilisateur', 'modification_utilisateur', 'suppression_utilisateur'],
        'actions' => []
    ],
    'materiel' => [
        'title' => 'Actions Matériel',
        'icon' => 'fa-laptop',
        'subtypes' => [
            'ajout' => [
                'title' => 'Ajouts de matériel',
                'icon' => 'fa-plus-circle',
                'type' => 'ajout_materiel',
                'actions' => []
            ],
            'modification' => [
                'title' => 'Modifications de matériel',
                'icon' => 'fa-edit',
                'type' => 'modification_materiel',
                'actions' => []
            ],
            'suppression' => [
                'title' => 'Suppressions de matériel',
                'icon' => 'fa-minus-circle',
                'type' => 'suppression_materiel',
                'actions' => []
            ]
        ]
    ],
    'demandes' => [
        'title' => 'Actions Demandes',
        'icon' => 'fa-file-alt',
        'subtypes' => [
            'validation' => [
                'title' => 'Validations de demandes',
                'icon' => 'fa-check-circle',
                'type' => 'validation_demande',
                'actions' => []
            ],
            'refus' => [
                'title' => 'Refus de demandes',
                'icon' => 'fa-times-circle',
                'type' => 'refus_demande',
                'actions' => []
            ],
            'retour' => [
                'title' => 'Validations de retours',
                'icon' => 'fa-undo',
                'type' => 'validation_retour',
                'actions' => []
            ]
        ]
    ],
    'email' => [
        'title' => 'Actions Emails',
        'icon' => 'fa-envelope',
        'subtypes' => [
            'ajout' => [
                'title' => 'Ajouts d\'emails',
                'icon' => 'fa-envelope-open',
                'type' => 'ajout_email',
                'actions' => []
            ],
            'suppression' => [
                'title' => 'Suppressions d\'emails',
                'icon' => 'fa-envelope-slash',
                'type' => 'suppression_email',
                'actions' => []
            ]
        ]
    ]
];

// Construire la requête SQL avec les filtres
$sql = "SELECT 
    ha.*,
    a.nom as admin_nom,
    a.prenom as admin_prenom
FROM historique_actions ha
LEFT JOIN administrateur a ON ha.id_admin = a.id_admin
WHERE 1=1";

$params = [];

// Ajouter les filtres à la requête
if (!empty($date_debut)) {
    $sql .= " AND DATE(ha.date_action) >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND DATE(ha.date_action) <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

if (!empty($search_keyword)) {
    $sql .= " AND (ha.details LIKE :keyword OR ha.type_action LIKE :keyword OR a.nom LIKE :keyword OR a.prenom LIKE :keyword)";
    $params[':keyword'] = '%' . $search_keyword . '%';
}

if ($admin_filter > 0) {
    $sql .= " AND ha.id_admin = :admin_id";
    $params[':admin_id'] = $admin_filter;
}

if (!empty($selected_category)) {
    $category_types = [];
    if ($selected_category === 'administrateur') {
        $category_types = ['ajout_admin', 'modification_admin', 'suppression_admin'];
    } elseif ($selected_category === 'utilisateur') {
        $category_types = ['ajout_utilisateur', 'modification_utilisateur', 'suppression_utilisateur'];
    } elseif ($selected_category === 'materiel') {
        $category_types = ['ajout_materiel', 'modification_materiel', 'suppression_materiel'];
    } elseif ($selected_category === 'demandes') {
        $category_types = ['validation_demande', 'refus_demande', 'validation_retour'];
    } elseif ($selected_category === 'email') {
        $category_types = ['ajout_email', 'suppression_email'];
    }
    
    if (!empty($category_types)) {
        $type_placeholders = [];
        foreach ($category_types as $i => $type) {
            $param_name = ":type_" . $i;
            $type_placeholders[] = $param_name;
            $params[$param_name] = $type;
        }
        $sql .= " AND ha.type_action IN (" . implode(', ', $type_placeholders) . ")";
    }
}

// Compter le nombre total d'actions pour la pagination
$count_sql = str_replace("SELECT ha.*, a.nom as admin_nom, a.prenom as admin_prenom", "SELECT COUNT(*) as count", $sql);
$stmt_count = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_items = $stmt_count->fetch(PDO::FETCH_ASSOC)['count'];
$total_pages = ceil($total_items / $items_per_page);

// Limiter les résultats pour la pagination
$offset = ($current_page - 1) * $items_per_page;
$sql .= " ORDER BY ha.date_action DESC LIMIT :offset, :limit";
$params[':offset'] = $offset;
$params[':limit'] = $items_per_page;

// Exécuter la requête
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':offset' || $key === ':limit') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Répartir les actions dans les groupes
foreach ($actions as $action) {
    foreach ($grouped_actions as $key => $group) {
        if (isset($group['types']) && in_array($action['type_action'], $group['types'])) {
            $grouped_actions[$key]['actions'][] = $action;
        } elseif (isset($group['subtypes'])) {
            foreach ($group['subtypes'] as $subtype_key => $subtype) {
                if ($action['type_action'] === $subtype['type']) {
                    $grouped_actions[$key]['subtypes'][$subtype_key]['actions'][] = $action;
                }
            }
        }
    }
}

echo "<!-- Debug: Requête SQL: " . $sql . " -->";
echo "<!-- Debug: Nombre d'actions utilisateurs: " . count($actions) . " -->";
echo "<!-- Debug: Actions utilisateurs: ";
print_r($actions);
echo " -->";

// Récupérer les types d'actions uniques pour le filtre
$sql_types = "SELECT DISTINCT type_action FROM historique_actions ORDER BY type_action";
$types_actions = $pdo->query($sql_types)->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Actions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .content-wrapper {
            margin-left: 250px;
            padding: 30px;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding-top: 20px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }

        .container-fluid {
            max-width: 100%;
            padding: 0;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .filter-card h5 {
            font-size: 1rem;
            margin-bottom: 12px;
            cursor: pointer;
        }
        
        .filter-form-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .filter-form-container.show {
            max-height: 300px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e9ecef;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.05);
            letter-spacing: 0.3px;
            font-size: 0.9rem;
        }

        .action-card {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.25s ease;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .action-header {
            flex: 0 0 60px;  /* Hauteur fixe pour l'en-tête */
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            background-color: #f8f9fd;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
        }

        .action-body {
            flex: 1;
            padding: 18px 22px;
            overflow-y: auto;  /* Ajouter un défilement si le contenu est trop long */
            line-height: 1.6;
        }

        .action-type {
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 14px;
            border-radius: 20px;
            background: #f8f9fa;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .action-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .action-admin {
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .action-details {
            color: #2c3e50;
            line-height: 1.5;
        }

        /* Styles spécifiques par type d'action */
        .type-ajout { 
            background: rgba(76, 175, 80, 0.1); 
            color: #4CAF50; 
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.1);
        }
        .type-modification { 
            background: rgba(33, 150, 243, 0.1); 
            color: #2196F3; 
            box-shadow: 0 2px 5px rgba(33, 150, 243, 0.1);
        }
        .type-suppression { 
            background: rgba(244, 67, 54, 0.1); 
            color: #f44336; 
            box-shadow: 0 2px 5px rgba(244, 67, 54, 0.1);
        }
        .type-validation { 
            background: rgba(76, 175, 80, 0.1); 
            color: #4CAF50; 
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.1);
        }
        .type-refus { 
            background: rgba(244, 67, 54, 0.1); 
            color: #f44336; 
            box-shadow: 0 2px 5px rgba(244, 67, 54, 0.1);
        }
        .type-retour { 
            background: rgba(33, 150, 243, 0.1); 
            color: #2196F3; 
            box-shadow: 0 2px 5px rgba(33, 150, 243, 0.1);
        }

        .subtype-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 3px 15px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }
        
        .subtype-section:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
        }

        .subtype-title {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .nav-buttons {
            position: sticky;
            top: 20px;
            z-index: 100;
            background: white;
            border-radius: 15px;
            padding: 18px 20px;
            margin-bottom: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .nav-buttons:hover {
            box-shadow: 0 6px 25px rgba(0,0,0,0.08);
        }

        .category-btn {
            min-width: 160px;
            padding: 12px 22px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin: 5px;
            white-space: nowrap;
            background-color: #f0f2f5;
            color: #495057;
            border: none;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .category-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: #e9ecef;
        }

        .category-btn.active {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }

        .category-section {
            display: none;
            animation: fadeIn 0.3s ease;
            width: 100%;
        }

        .category-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .d-flex.flex-wrap {
            justify-content: center;
            gap: 10px !important;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            margin-bottom: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            width: 85%;
            margin: 0 auto 35px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .section-card:hover {
            box-shadow: 0 6px 25px rgba(0,0,0,0.08);
        }

        .section-header {
            padding: 22px 25px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px 15px 0 0;
            position: relative;
            box-shadow: 0 3px 10px rgba(44, 62, 80, 0.1);
        }
        
        .section-header h3 {
            font-weight: 600;
            letter-spacing: 0.3px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section-body {
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            max-width: 100%;
        }

        .page-title {
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            padding-bottom: 15px;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .page-title:after {
            content: '';
            position: absolute;
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(44, 62, 80, 0.1);
        }

        .delete-all-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: absolute;
            top: 22px;
            right: 25px;
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.2);
            letter-spacing: 0.3px;
        }
        
        .delete-all-btn:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 6px 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 3px 8px rgba(220, 53, 69, 0.1);
        }
        
        .action-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .row {
            width: 100%;
            margin: 0;
        }

        .pagination {
            margin-top: 2rem;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            color: #2c3e50;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            padding: 8px 15px;
        }
        
        .pagination .page-link:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border-color: #3498db;
            color: white;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background-color: #f8f9fa;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #34495e, #2980b9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-outline-secondary {
            border-color: #e9ecef;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #343a40;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.2);
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.1);
            border-color: #2c3e50;
        }
    </style>
</head>
<body> 
    <?php include '../../includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Ajout des filtres -->
            <div class="filter-card mb-4">
                <h5 class="mb-3" id="filterToggle"><i class="fas fa-filter me-2"></i>Filtres <i class="fas fa-chevron-down ms-2"></i></h5>
                <div class="filter-form-container" id="filterFormContainer">
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="text" class="form-control datepicker" id="date_debut" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="text" class="form-control datepicker" id="date_fin" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="col-md-3">
                            <label for="admin_id" class="form-label">Administrateur</label>
                            <select class="form-select" id="admin_id" name="admin_id">
                                <option value="0">Tous les administrateurs</option>
                                <?php foreach ($all_admins as $admin): ?>
                                    <option value="<?= $admin['id_admin'] ?>" <?= $admin_filter == $admin['id_admin'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($admin['nom'] . ' ' . $admin['prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="keyword" class="form-label">Recherche par mot-clé</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" value="<?= htmlspecialchars($search_keyword) ?>" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Catégorie</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Toutes les catégories</option>
                                <option value="utilisateur" <?= $selected_category === 'utilisateur' ? 'selected' : '' ?>>Utilisateurs</option>
                                <option value="materiel" <?= $selected_category === 'materiel' ? 'selected' : '' ?>>Matériel</option>
                                <option value="demandes" <?= $selected_category === 'demandes' ? 'selected' : '' ?>>Demandes</option>
                                <option value="email" <?= $selected_category === 'email' ? 'selected' : '' ?>>Emails</option>
                                <option value="administrateur" <?= $selected_category === 'administrateur' ? 'selected' : '' ?>>Administrateurs</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filtrer
                                </button>
                                <button type="reset" class="btn btn-outline-secondary" onclick="window.location.href='historique_actions.php'">
                                    <i class="fas fa-undo me-2"></i>Réinitialiser
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <h2 class="page-title">Historique des Actions</h2>

            <!-- Bouton de suppression globale -->
            <div class="d-flex justify-content-end mb-4">
                <button class="btn btn-danger" onclick="deleteAllHistory()">
                    <i class="fas fa-trash-alt me-2"></i>Vider tout l'historique
                </button>
            </div>

            <!-- Uniquement les boutons de navigation -->
            <div class="nav-buttons">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button type="button" class="category-btn active" data-category="utilisateur">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </button>
                    <button type="button" class="category-btn" data-category="materiel">
                        <i class="fas fa-laptop me-2"></i>Matériel
                    </button>
                    <button type="button" class="category-btn" data-category="demandes">
                        <i class="fas fa-file-alt me-2"></i>Demandes
                    </button>
                    <button type="button" class="category-btn" data-category="email">
                        <i class="fas fa-envelope me-2"></i>Emails
                    </button>
                    <button type="button" class="category-btn" data-category="administrateur">
                        <i class="fas fa-user-shield me-2"></i>Administrateurs
                    </button>
                </div>
            </div>

            <!-- Liste des actions -->
            <div class="row">
                <?php foreach ($grouped_actions as $key => $group): ?>
                    <div class="col-12 category-section" id="<?= $key ?>-section">
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="m-0">
                                    <i class="fas <?= $group['icon'] ?> me-2"></i>
                                    <?= $group['title'] ?>
                                    <?php if (isset($group['actions'])): ?>
                                        <!-- Supprimé: badge compteur -->
                                    <?php endif; ?>
                                </h3>
                                <button class="delete-all-btn" onclick="deleteAllActions('<?= $key ?>')" title="Supprimer toutes les actions">
                                    <i class="fas fa-trash-alt"></i> Tout supprimer
                                </button>
                            </div>
                            <div class="section-body">
                                <?php if (isset($group['actions'])): ?>
                                    <!-- Affichage chronologique pour les utilisateurs -->
                                    <?php if (!empty($group['actions'])): ?>
                                        <?php 
                                        // Trier les actions par date décroissante
                                        usort($group['actions'], function($a, $b) {
                                            return strtotime($b['date_action']) - strtotime($a['date_action']);
                                        });
                                        ?>
                                        <?php foreach ($group['actions'] as $action): ?>
                                            <div class="action-card" data-action-id="<?= $action['id_action'] ?>">
                                                <div class="action-header">
                                                    <div class="action-header-content">
                                                        <span class="action-type <?= 'type-' . explode('_', $action['type_action'])[0] ?>">
                                                            <i class="fas <?= $action['type_action'] === 'ajout_utilisateur' ? 'fa-user-plus' : 
                                                                        ($action['type_action'] === 'modification_utilisateur' ? 'fa-user-edit' : 'fa-user-minus') ?> me-1"></i>
                                                            <?= str_replace('_', ' ', $action['type_action']) ?>
                                                        </span>
                                                        <span class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            <?= date('d/m/Y H:i', strtotime($action['date_action'])) ?>
                                                        </span>
                                                    </div>
                                                    <button class="delete-btn" onclick="deleteAction(<?= $action['id_action'] ?>)" title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                <div class="action-body">
                                                    <div class="mb-2 text-muted">
                                                        <i class="far fa-user me-1"></i>
                                                        <?= htmlspecialchars($action['admin_prenom'] . ' ' . $action['admin_nom']) ?>
                                                    </div>
                                                    <div>
                                                        <?= htmlspecialchars($action['details'] ?? '-') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Aucune action trouvée.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Garder l'affichage par sous-types pour les autres catégories -->
                                    <?php if (isset($group['subtypes'])): ?>
                                        <?php if (in_array($key, ['materiel', 'demandes', 'email'])): ?>
                                            <?php
                                            // Fusionner toutes les actions en une seule liste
                                            $all_actions = [];
                                            foreach ($group['subtypes'] as $subtype) {
                                                $all_actions = array_merge($all_actions, $subtype['actions']);
                                            }
                                            
                                            // Trier par date décroissante
                                            usort($all_actions, function($a, $b) {
                                                return strtotime($b['date_action']) - strtotime($a['date_action']);
                                            });
                                            ?>
                                            
                                            <?php if (!empty($all_actions)): ?>
                                                <?php foreach ($all_actions as $action): ?>
                                                    <div class="action-card" data-action-id="<?= $action['id_action'] ?>">
                                                        <div class="action-header">
                                                            <div class="action-header-content">
                                                                <span class="action-type type-<?= explode('_', $action['type_action'])[0] ?>">
                                                                    <i class="fas <?php
                                                                        switch ($action['type_action']) {
                                                                            // Matériel
                                                                            case 'ajout_materiel': echo 'fa-plus-circle'; break;
                                                                            case 'modification_materiel': echo 'fa-edit'; break;
                                                                            case 'suppression_materiel': echo 'fa-minus-circle'; break;
                                                                            // Demandes
                                                                            case 'validation_demande': echo 'fa-check-circle'; break;
                                                                            case 'refus_demande': echo 'fa-times-circle'; break;
                                                                            case 'validation_retour': echo 'fa-undo'; break;
                                                                            // Emails
                                                                            case 'ajout_email': echo 'fa-envelope-open'; break;
                                                                            case 'suppression_email': echo 'fa-envelope-slash'; break;
                                                                            default: echo 'fa-circle';
                                                                        }
                                                                    ?> me-1"></i>
                                                                    <?= str_replace('_', ' ', $action['type_action']) ?>
                                                                </span>
                                                                <span class="text-muted">
                                                                    <i class="far fa-clock me-1"></i>
                                                                    <?= date('d/m/Y H:i', strtotime($action['date_action'])) ?>
                                                                </span>
                                                            </div>
                                                            <button class="delete-btn" onclick="deleteAction(<?= $action['id_action'] ?>)" title="Supprimer">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </div>
                                                        <div class="action-body">
                                                            <div class="mb-2 text-muted">
                                                                <i class="far fa-user me-1"></i>
                                                                <?= htmlspecialchars($action['admin_prenom'] . ' ' . $action['admin_nom']) ?>
                                                            </div>
                                                            <div>
                                                                <?= htmlspecialchars($action['details'] ?? '-') ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted">Aucune action trouvée.</p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Garder l'affichage existant pour les autres catégories -->
                                            <?php foreach ($group['subtypes'] as $subtype_key => $subtype): ?>
                                                <div class="subtype-section mb-4">
                                                    <h4 class="subtype-title">
                                                        <i class="fas <?= $subtype['icon'] ?> me-2"></i>
                                                        <?= $subtype['title'] ?>
                                                    </h4>
                                                    <?php if (!empty($subtype['actions'])): ?>
                                                        <?php foreach ($subtype['actions'] as $action): ?>
                                                            <div class="action-card" data-action-id="<?= $action['id_action'] ?>">
                                                                <div class="action-header">
                                                                    <div class="action-header-content">
                                                                        <span class="action-type type-<?= $subtype_key ?>">
                                                                            <i class="fas <?= $subtype['icon'] ?> me-1"></i>
                                                                            <?= str_replace('_', ' ', $action['type_action']) ?>
                                                                        </span>
                                                                        <span class="text-muted">
                                                                            <i class="far fa-clock me-1"></i>
                                                                            <?= date('d/m/Y H:i', strtotime($action['date_action'])) ?>
                                                                        </span>
                                                                    </div>
                                                                    <button class="delete-btn" onclick="deleteAction(<?= $action['id_action'] ?>)" title="Supprimer">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="action-body">
                                                                    <div class="mb-2 text-muted">
                                                                        <i class="far fa-user me-1"></i>
                                                                        <?= htmlspecialchars($action['admin_prenom'] . ' ' . $action['admin_nom']) ?>
                                                                    </div>
                                                                    <div>
                                                                        <?= htmlspecialchars($action['details'] ?? '-') ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted">Aucune action de ce type.</p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted m-0">Aucune action trouvée dans cette catégorie.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4 mb-4">
                <nav aria-label="Pagination de l'historique">
                    <ul class="pagination">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>" aria-label="Précédent">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <?php
                        // Déterminer les pages à afficher
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        // Afficher un lien vers la première page si nécessaire
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php 
                        // Afficher un lien vers la dernière page si nécessaire
                        if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>" aria-label="Suivant">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmation de suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer <strong>TOUTES</strong> les actions de cette catégorie ?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Cette opération est <strong>irréversible</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash-alt me-2"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression d'une action individuelle -->
    <div class="modal fade" id="deleteSingleConfirmModal" tabindex="-1" aria-labelledby="deleteSingleConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSingleConfirmModalLabel">Confirmation de suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cette action ?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Cette opération est <strong>irréversible</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSingleBtn">
                        <i class="fas fa-trash-alt me-2"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression de tout l'historique -->
    <div class="modal fade" id="deleteAllHistoryModal" tabindex="-1" aria-labelledby="deleteAllHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAllHistoryModalLabel">⚠️ Attention: Suppression totale</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Action très destructive</h5>
                        <p>Vous êtes sur le point de <strong>supprimer TOUT l'historique des actions</strong> de toutes les catégories.</p>
                    </div>
                    <p>Cette opération est <strong>irréversible</strong> et effacera toutes les traces des actions effectuées dans le système.</p>
                    <p>Pour confirmer, veuillez taper "SUPPRIMER" dans le champ ci-dessous:</p>
                    <input type="text" id="confirmText" class="form-control" placeholder="Tapez SUPPRIMER">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteAllBtn" disabled>
                        <i class="fas fa-trash-alt me-2"></i>Supprimer définitivement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        let categoryToDelete = ''; // Variable globale pour stocker la catégorie à supprimer
        let actionIdToDelete = null; // Variable globale pour stocker l'ID de l'action à supprimer
        
        document.addEventListener('DOMContentLoaded', function() {
            // Gérer le toggle des filtres
            const filterToggle = document.getElementById('filterToggle');
            const filterFormContainer = document.getElementById('filterFormContainer');
            
            // Déterminer si les filtres sont actifs pour décider d'afficher ou non la section par défaut
            const hasActiveFilters = <?= (!empty($date_debut) || !empty($date_fin) || !empty($search_keyword) || $admin_filter > 0 || !empty($selected_category)) ? 'true' : 'false' ?>;
            
            if (hasActiveFilters) {
                filterFormContainer.classList.add('show');
            }
            
            filterToggle.addEventListener('click', function() {
                filterFormContainer.classList.toggle('show');
                const icon = this.querySelector('.fa-chevron-down');
                icon.classList.toggle('fa-rotate-180');
            });
            
            // Activer le bon bouton de catégorie si un filtre de catégorie est actif
            const selectedCategory = '<?= $selected_category ?>';
            if (selectedCategory) {
                // Désactiver tous les boutons de catégorie
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Cacher toutes les sections
                document.querySelectorAll('.category-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Trouver et activer le bouton correspondant à la catégorie filtrée
                const categoryBtn = document.querySelector(`.category-btn[data-category="${selectedCategory}"]`);
                if (categoryBtn) {
                    categoryBtn.classList.add('active');
                    
                    // Afficher la section correspondante
                    const categorySection = document.getElementById(`${selectedCategory}-section`);
                    if (categorySection) {
                        categorySection.classList.add('active');
                    }
                }
            } else {
                // Si aucune catégorie n'est sélectionnée, afficher la section utilisateur par défaut
                document.getElementById('utilisateur-section').classList.add('active');
                document.querySelector('.category-btn[data-category="utilisateur"]').classList.add('active');
            }
            
            // Soumettre automatiquement le formulaire quand la catégorie change dans le select
            const categorySelect = document.getElementById('category');
            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }
            
            // Initialiser Flatpickr pour les sélecteurs de date
            flatpickr.localize(flatpickr.l10ns.fr);
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                allowInput: true,
                altInput: true,
                altFormat: "d/m/Y",
                maxDate: "today"
            });
            
            // Gérer les clics sur les boutons
            document.querySelectorAll('.category-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Retirer la classe active de tous les boutons
                    document.querySelectorAll('.category-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });

                    // Cacher toutes les sections
                    document.querySelectorAll('.category-section').forEach(section => {
                        section.classList.remove('active');
                    });

                    // Activer le bouton cliqué
                    this.classList.add('active');

                    // Afficher la section correspondante avec animation
                    const categoryId = this.dataset.category;
                    
                    // Synchroniser avec le select de catégorie dans les filtres
                    const categorySelect = document.getElementById('category');
                    if (categorySelect) {
                        categorySelect.value = categoryId;
                    }
                    
                    setTimeout(() => {
                        document.getElementById(categoryId + '-section').classList.add('active');
                    }, 50);
                });
            });

            // Initialiser les modals
            const deleteModal = document.getElementById('deleteConfirmModal');
            const deleteSingleModal = document.getElementById('deleteSingleConfirmModal');
            
            // Configurer le bouton de confirmation de suppression en masse
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                // Fermer la modal
                bootstrap.Modal.getInstance(deleteModal).hide();
                
                // Exécuter la suppression
                executeDeleteAllActions(categoryToDelete);
            });
            
            // Configurer le bouton de confirmation pour la suppression individuelle
            document.getElementById('confirmDeleteSingleBtn').addEventListener('click', function() {
                // Fermer la modal
                bootstrap.Modal.getInstance(deleteSingleModal).hide();
                
                // Exécuter la suppression
                executeDeleteAction(actionIdToDelete);
            });

            // Configurer le champ de confirmation pour la suppression totale
            const confirmText = document.getElementById('confirmText');
            const confirmDeleteAllBtn = document.getElementById('confirmDeleteAllBtn');
            
            confirmText.addEventListener('input', function() {
                confirmDeleteAllBtn.disabled = this.value !== 'SUPPRIMER';
            });
            
            // Configurer le bouton de confirmation pour la suppression totale
            confirmDeleteAllBtn.addEventListener('click', function() {
                // Fermer la modal
                const deleteAllHistoryModal = document.getElementById('deleteAllHistoryModal');
                bootstrap.Modal.getInstance(deleteAllHistoryModal).hide();
                
                // Exécuter la suppression
                executeDeleteAllHistory();
                
                // Réinitialiser le champ de confirmation
                confirmText.value = '';
                confirmDeleteAllBtn.disabled = true;
            });
        });
        
        // Fonction pour afficher la modal de confirmation pour une action individuelle
        function deleteAction(actionId) {
            // Stocker l'ID de l'action à supprimer
            actionIdToDelete = actionId;
            
            // Afficher la modal
            const deleteSingleModal = new bootstrap.Modal(document.getElementById('deleteSingleConfirmModal'));
            deleteSingleModal.show();
        }
        
        // Fonction pour exécuter la suppression d'une action individuelle
        function executeDeleteAction(actionId) {
            if (!actionId) {
                alert('Erreur: Action non définie');
                return;
            }
            
            fetch('../../controllers/admin/delete_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action_id=' + actionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer l'élément du DOM
                    const actionElement = document.querySelector(`.action-card[data-action-id="${actionId}"]`);
                    if (actionElement) {
                        actionElement.remove();
                        
                        // Afficher un message de succès
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '11';
                        toast.innerHTML = `
                            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        Action supprimée avec succès !
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        
                        const toastElement = new bootstrap.Toast(toast.querySelector('.toast'));
                        toastElement.show();
                        
                        // Supprimer le toast après 3 secondes
                        setTimeout(() => {
                            toast.remove();
                        }, 3000);
                    }
                } else {
                    // Afficher un message d'erreur
                    alert(data.message || 'Une erreur est survenue lors de la suppression de l\'action.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression de l\'action.');
            });
        }
        
        // Fonction pour afficher la modal de confirmation pour suppression en masse
        function deleteAllActions(category) {
            // Stocker la catégorie à supprimer dans la variable globale
            categoryToDelete = category;
            console.log('Catégorie à supprimer:', categoryToDelete); // Debug
            
            // Afficher la modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            deleteModal.show();
        }
        
        // Fonction pour exécuter la suppression de toutes les actions
        function executeDeleteAllActions(category) {
            console.log('Exécution de la suppression pour la catégorie:', category); // Debug
            
            // Vérifier que la catégorie est bien définie
            if (!category) {
                alert('Erreur: Catégorie non définie');
                return;
            }
            
            fetch('../../controllers/admin/delete_all_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'category=' + encodeURIComponent(category)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer tous les éléments de la catégorie du DOM
                    const categorySection = document.getElementById(category + '-section');
                    const actionCards = categorySection.querySelectorAll('.action-card');
                    
                    actionCards.forEach(card => {
                        card.remove();
                    });
                    
                    // Ajouter un message "Aucune action trouvée"
                    const sectionBody = categorySection.querySelector('.section-body');
                    if (sectionBody && actionCards.length > 0) {
                        sectionBody.innerHTML = '<p class="text-muted">Aucune action trouvée.</p>';
                    }
                    
                    // Afficher un message de succès
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '11';
                    toast.innerHTML = `
                        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    ${data.message || 'Toutes les actions ont été supprimées avec succès !'}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    const toastElement = new bootstrap.Toast(toast.querySelector('.toast'));
                    toastElement.show();
                    
                    // Supprimer le toast après 3 secondes
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                } else {
                    // Afficher un message d'erreur
                    alert(data.message || 'Une erreur est survenue lors de la suppression des actions.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression des actions.');
            });
        }

        // Fonction pour afficher la modal de confirmation pour la suppression totale
        function deleteAllHistory() {
            // Afficher la modal
            const deleteAllHistoryModal = new bootstrap.Modal(document.getElementById('deleteAllHistoryModal'));
            deleteAllHistoryModal.show();
        }
        
        // Fonction pour exécuter la suppression de tout l'historique
        function executeDeleteAllHistory() {
            fetch('../../controllers/admin/delete_all_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer tous les éléments de toutes les catégories du DOM
                    const actionCards = document.querySelectorAll('.action-card');
                    actionCards.forEach(card => {
                        card.remove();
                    });
                    
                    // Ajouter un message "Aucune action trouvée" dans chaque section
                    const sectionBodies = document.querySelectorAll('.section-body');
                    sectionBodies.forEach(body => {
                        body.innerHTML = '<p class="text-muted">Aucune action trouvée.</p>';
                    });
                    
                    // Afficher un message de succès
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '11';
                    toast.innerHTML = `
                        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    ${data.message || 'Tout l\'historique a été supprimé avec succès !'}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    const toastElement = new bootstrap.Toast(toast.querySelector('.toast'));
                    toastElement.show();
                    
                    // Supprimer le toast après 3 secondes
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                } else {
                    // Afficher un message d'erreur
                    alert(data.message || 'Une erreur est survenue lors de la suppression de l\'historique.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression de l\'historique.');
            });
        }
    </script>
</body>
</html> 