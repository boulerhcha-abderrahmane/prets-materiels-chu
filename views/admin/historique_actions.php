<?php
require_once '../../config/config.php';
session_start();

// Vérification si l'utilisateur est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

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

// Récupérer toutes les actions
$sql = "SELECT 
    ha.*,
    a.nom as admin_nom,
    a.prenom as admin_prenom
FROM historique_actions ha
LEFT JOIN administrateur a ON ha.id_admin = a.id_admin
ORDER BY ha.date_action DESC";

$stmt = $pdo->prepare($sql);
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
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .action-card {
            width: 100%;
            height: 200px;  /* Hauteur fixe pour toutes les cartes */
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            transition: transform 0.2s ease;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .action-header {
            flex: 0 0 60px;  /* Hauteur fixe pour l'en-tête */
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background-color: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }

        .action-body {
            flex: 1;
            padding: 15px 20px;
            overflow-y: auto;  /* Ajouter un défilement si le contenu est trop long */
        }

        .action-type {
            font-weight: 600;
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 20px;
            background: #f8f9fa;
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

        /* Types d'actions */
        .validation { border-left: 4px solid #4CAF50; }
        .validation .action-type { color: #4CAF50; background: rgba(76, 175, 80, 0.1); }
        
        .refus { border-left: 4px solid #f44336; }
        .refus .action-type { color: #f44336; background: rgba(244, 67, 54, 0.1); }
        
        .retour { border-left: 4px solid #2196F3; }
        .retour .action-type { color: #2196F3; background: rgba(33, 150, 243, 0.1); }
        
        .materiel { border-left: 4px solid #FF9800; }
        .materiel .action-type { color: #FF9800; background: rgba(255, 152, 0, 0.1); }
        
        .email { border-left: 4px solid #9C27B0; }
        .email .action-type { color: #9C27B0; background: rgba(156, 39, 176, 0.1); }
        
        .utilisateur { border-left: 4px solid #00BCD4; }
        .utilisateur .action-type { color: #00BCD4; background: rgba(0, 188, 212, 0.1); }

        /* Formulaire */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e9ecef;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(44, 62, 80, 0.1);
            border-color: #2c3e50;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        .btn-primary {
            background: #2c3e50;
            border: none;
        }

        .btn-primary:hover {
            background: #34495e;
        }

        .btn-outline-secondary {
            border-color: #e9ecef;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #343a40;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .section-body {
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            max-width: 100%;
        }

        .action-card {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            transition: transform 0.2s ease;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .action-header {
            padding: 15px 20px;
            width: 100%;
            border-bottom: 1px solid #eee;
            background-color: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }

        .action-body {
            padding: 15px 20px;
            width: 100%;
        }

        .action-type {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
        }

        /* Styles spécifiques par type d'action */
        .type-ajout { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .type-modification { background: rgba(33, 150, 243, 0.1); color: #2196F3; }
        .type-suppression { background: rgba(244, 67, 54, 0.1); color: #f44336; }
        .type-validation { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .type-refus { background: rgba(244, 67, 54, 0.1); color: #f44336; }
        .type-retour { background: rgba(33, 150, 243, 0.1); color: #2196F3; }

        .subtype-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .subtype-title {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .nav-buttons {
            position: sticky;
            top: 20px;
            z-index: 100;
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .category-btn {
            min-width: 160px;
            padding: 15px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin: 5px;
            white-space: nowrap;
        }

        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .category-btn.active {
            background-color: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }

        .category-section {
            display: none;
            animation: fadeIn 0.3s ease;
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
    </style>
</head>
<body> 
    <?php include '../../includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <h2 class="mb-4">Historique des Actions</h2>

            <!-- Uniquement les boutons de navigation -->
            <div class="nav-buttons">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-primary category-btn active" data-category="utilisateur">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </button>
                    <button type="button" class="btn btn-outline-primary category-btn" data-category="materiel">
                        <i class="fas fa-laptop me-2"></i>Matériel
                    </button>
                    <button type="button" class="btn btn-outline-primary category-btn" data-category="demandes">
                        <i class="fas fa-file-alt me-2"></i>Demandes
                    </button>
                    <button type="button" class="btn btn-outline-primary category-btn" data-category="email">
                        <i class="fas fa-envelope me-2"></i>Emails
                    </button>
                    <button type="button" class="btn btn-outline-primary category-btn" data-category="administrateur">
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
                                        <span class="badge bg-light text-dark ms-2">
                                            <?= count($group['actions']) ?>
                                        </span>
                                    <?php endif; ?>
                                </h3>
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
                                            <div class="action-card">
                                                <div class="action-header d-flex justify-content-between align-items-center">
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
                                                    <div class="action-card">
                                                        <div class="action-header d-flex justify-content-between align-items-center">
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
                                                        <span class="badge bg-secondary ms-2">
                                                            <?= count($subtype['actions']) ?>
                                                        </span>
                                                    </h4>
                                                    <?php if (!empty($subtype['actions'])): ?>
                                                        <?php foreach ($subtype['actions'] as $action): ?>
                                                            <div class="action-card">
                                                                <div class="action-header d-flex justify-content-between align-items-center">
                                                                    <span class="action-type type-<?= $subtype_key ?>">
                                                                        <i class="fas <?= $subtype['icon'] ?> me-1"></i>
                                                                        <?= str_replace('_', ' ', $action['type_action']) ?>
                                                                    </span>
                                                                    <span class="text-muted">
                                                                        <i class="far fa-clock me-1"></i>
                                                                        <?= date('d/m/Y H:i', strtotime($action['date_action'])) ?>
                                                                    </span>
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
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Afficher la première section par défaut
            document.querySelector('#utilisateur-section').style.display = 'block';

            // Gérer les clics sur les boutons
            document.querySelectorAll('.category-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Retirer la classe active de tous les boutons
                    document.querySelectorAll('.category-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });

                    // Cacher toutes les sections
                    document.querySelectorAll('.category-section').forEach(section => {
                        section.style.display = 'none';
                    });

                    // Activer le bouton cliqué
                    this.classList.add('active');

                    // Afficher la section correspondante
                    const categoryId = this.dataset.category;
                    document.querySelector(`#${categoryId}-section`).style.display = 'block';
                });
            });

            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                locale: "fr"
            });
        });
    </script>
</body>
</html> 