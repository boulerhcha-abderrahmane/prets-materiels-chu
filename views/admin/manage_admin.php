<?php
session_start();
require_once '../../config/config.php';


// // Vérifier si l'utilisateur est un admin en vérifiant admin_role
// if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin' && $_SESSION['admin_role'] !== 'chef') {
//     header('Location: login.php');
//     exit();
// }

// Initialiser les variables de message
$successMessage = '';
$errorMessage = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    if (isset($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], $_POST['role'])) {
                        // Vérifier si l'email existe déjà dans la base
                        $check = $pdo->prepare("SELECT COUNT(*) FROM administrateur WHERE email = ?");
                        $check->execute([$_POST['email']]);
                        if ($check->fetchColumn() > 0) {
                            throw new Exception("Cet email est déjà utilisé");
                        }

                        // Vérification de base du format de l'email
                        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Format d'email invalide");
                        }

                        // Essayer de vérifier l'email avec l'API Abstract (optionnel)
                        try {
                            $apiKey = '8c1590ee99ec415e8fa34947c4da7378';
                            $email = urlencode($_POST['email']);
                            $url = "https://emailvalidation.abstractapi.com/v1/?api_key={$apiKey}&email={$email}";
                            
                            $response = @file_get_contents($url);
                            if ($response !== false) {
                                $result = json_decode($response, true);

                                // Vérification seulement si l'API a répondu correctement
                                if (isset($result['deliverability']) && $result['deliverability'] !== "DELIVERABLE") {
                                    // Log mais ne bloque pas l'enregistrement
                                    error_log("Avertissement: Email potentiellement non délivrable: " . $_POST['email']);
                                }
                                if (isset($result['is_disposable_email']['value']) && $result['is_disposable_email']['value'] === true) {
                                    throw new Exception("Les adresses email temporaires ne sont pas autorisées");
                                }
                            }
                        } catch (Exception $e) {
                            // Log l'erreur mais continue le processus
                            error_log("Erreur lors de la vérification d'email: " . $e->getMessage());
                        }

                        // Insérer le nouvel administrateur
                        $sql = "INSERT INTO administrateur (nom, prenom, email, mot_de_passe, role, date_creation) 
                                VALUES (:nom, :prenom, :email, :password, :role, NOW())";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':nom' => $_POST['nom'],
                            ':prenom' => $_POST['prenom'],
                            ':email' => $_POST['email'],
                            ':password' => $_POST['password'], 
                            ':role' => $_POST['role']
                        ]);
                        
                        // Debug: Afficher l'ID de l'admin connecté
                        error_log("ID Admin connecté: " . $_SESSION['admin_id']);
                        
                        // Enregistrer dans l'historique avec try-catch pour debug
                        try {
                            $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, details, date_action) 
                                             VALUES (:id_admin, :type_action, :details, NOW())";
                            $stmt_historique = $pdo->prepare($sql_historique);
                            $stmt_historique->execute([
                                ':id_admin' => $_SESSION['admin_id'],
                                ':type_action' => 'ajout_admin',
                                ':details' => "Ajout de l'administrateur : " . $_POST['nom'] . " " . $_POST['prenom']
                            ]);
                            error_log("Historique ajouté avec succès");
                        } catch (Exception $e) {
                            error_log("Erreur lors de l'ajout dans l'historique: " . $e->getMessage());
                        }
                        
                        $successMessage = "Administrateur ajouté avec succès";
                    } else {
                        throw new Exception("Tous les champs sont requis");
                    }
                    break;

                case 'edit':
                    if (isset($_POST['admin_id'], $_POST['new_nom'], $_POST['new_prenom'], $_POST['new_email'], $_POST['new_role'])) {
                        // Vérifier si l'email existe déjà pour un autre administrateur
                        $check = $pdo->prepare("SELECT COUNT(*) FROM administrateur WHERE email = ? AND id_admin != ?");
                        $check->execute([$_POST['new_email'], $_POST['admin_id']]);
                        if ($check->fetchColumn() > 0) {
                            throw new Exception("Cet email est déjà utilisé par un autre administrateur");
                        }
                        
                        // Vérification de base du format de l'email
                        if (!filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Format d'email invalide");
                        }

                        // Essayer de vérifier le nouvel email avec l'API Abstract (optionnel)
                        try {
                            $apiKey = '8c1590ee99ec415e8fa34947c4da7378';
                            $email = urlencode($_POST['new_email']);
                            $url = "https://emailvalidation.abstractapi.com/v1/?api_key={$apiKey}&email={$email}";
                            
                            $response = @file_get_contents($url);
                            if ($response !== false) {
                                $result = json_decode($response, true);

                                // Vérification seulement si l'API a répondu correctement
                                if (isset($result['deliverability']) && $result['deliverability'] !== "DELIVERABLE") {
                                    // Log mais ne bloque pas l'enregistrement
                                    error_log("Avertissement: Email potentiellement non délivrable: " . $_POST['new_email']);
                                }
                                if (isset($result['is_disposable_email']['value']) && $result['is_disposable_email']['value'] === true) {
                                    throw new Exception("Les adresses email temporaires ne sont pas autorisées");
                                }
                            }
                        } catch (Exception $e) {
                            // Log l'erreur mais continue le processus
                            error_log("Erreur lors de la vérification d'email: " . $e->getMessage());
                        }

                        $sql = "UPDATE administrateur SET 
                                nom = :nom,
                                prenom = :prenom,
                                email = :email,
                                role = :role";
                        
                        $params = [
                            ':nom' => $_POST['new_nom'],
                            ':prenom' => $_POST['new_prenom'],
                            ':email' => $_POST['new_email'],
                            ':role' => $_POST['new_role'],
                            ':id' => $_POST['admin_id']
                        ];

                        // Ajouter le mot de passe à la requête uniquement s'il est fourni
                        if (!empty($_POST['new_password'])) {
                            $sql .= ", mot_de_passe = :password";
                            $params[':password'] =  ($_POST['new_password']);
                        }

                        $sql .= " WHERE id_admin = :id";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        
                        try {
                            $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, details, date_action) 
                                             VALUES (:id_admin, :type_action, :details, NOW())";
                            $stmt_historique = $pdo->prepare($sql_historique);
                            $stmt_historique->execute([
                                ':id_admin' => $_SESSION['admin_id'],
                                ':type_action' => 'modification_admin',
                                ':details' => "Modification de l'administrateur : " . $_POST['new_nom'] . " " . $_POST['new_prenom']
                            ]);
                        } catch (Exception $e) {
                            error_log("Erreur lors de l'ajout dans l'historique: " . $e->getMessage());
                        }
                        
                        $successMessage = "Administrateur modifié avec succès";
                    } else {
                        throw new Exception("Données manquantes pour la modification");
                    }
                    break;

                case 'delete':
                    if (isset($_POST['admin_id'])) {
                        // Récupérer les informations de l'admin avant la suppression
                        $stmt_info = $pdo->prepare("SELECT nom, prenom FROM administrateur WHERE id_admin = ?");
                        $stmt_info->execute([$_POST['admin_id']]);
                        $admin_info = $stmt_info->fetch();

                        // Vérifier si l'administrateur existe
                        if ($admin_info) {
                            // Commencer une transaction
                            $pdo->beginTransaction();
                            try {
                                // Supprimer l'administrateur
                                $stmt = $pdo->prepare("DELETE FROM administrateur WHERE id_admin = ?");
                                $stmt->execute([$_POST['admin_id']]);
                                
                                if ($stmt->rowCount() > 0) {
                                    try {
                                        $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, details, date_action) 
                                                         VALUES (:id_admin, :type_action, :details, NOW())";
                                        $stmt_historique = $pdo->prepare($sql_historique);
                                        $stmt_historique->execute([
                                            ':id_admin' => $_SESSION['admin_id'],
                                            ':type_action' => 'suppression_admin',
                                            ':details' => "Suppression de l'administrateur : " . $admin_info['nom'] . " " . $admin_info['prenom']
                                        ]);
                                        
                                        $pdo->commit();
                                        $successMessage = "Administrateur supprimé avec succès";
                                    } catch (Exception $e) {
                                        $pdo->rollBack();
                                        error_log("Erreur lors de l'ajout dans l'historique: " . $e->getMessage());
                                        throw new Exception("Erreur lors de l'enregistrement de l'historique : " . $e->getMessage());
                                    }
                                } else {
                                    throw new Exception("Erreur lors de la suppression de l'administrateur");
                                }
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
                            }
                        } else {
                            throw new Exception("Administrateur non trouvé");
                        }
                    } else {
                        throw new Exception("ID d'administrateur manquant");
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $errorMessage = "Erreur : " . $e->getMessage();
    }
}

// Récupération des données pour l'affichage
$stmt = $pdo->query("SELECT id_admin, nom, prenom, email, role, mot_de_passe, date_creation FROM administrateur");
$admins = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Administrateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #f8f9fa;
            color: #2c3e50;
        }

        .main-content {
            padding: 30px;
            width: 100%;
        }

        .badge {
            font-size: 0.8em;
            padding: 0.35em 0.65em;
        }
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="container">
            <header>
                <h1>Gestion des Administrateurs</h1>
            </header>
        
            <!-- Formulaire pour ajouter un nouvel administrateur -->
            <form action="" method="POST" class="mb-4">
                <div class="form-group">
                    <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                </div>
                <div class="form-group">
                    <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email autorisé" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                </div>
                <div class="form-group">
                    <select name="role" class="form-control" required>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="action" value="add" class="btn btn-primary">Ajouter Administrateur</button>
            </form>
            
            <h3>Administrateurs Existants</h3>
            <ul class="list-group">
                <?php
                // Récupérer tous les administrateurs de la base de données
                $stmt = $pdo->query("SELECT id_admin, nom, prenom, email, role, mot_de_passe, date_creation FROM administrateur");
                while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                            <div>" . 
                                htmlspecialchars($admin['nom']) . " " . 
                                htmlspecialchars($admin['prenom']) . " - " . 
                                htmlspecialchars($admin['email']) . " - " . 
                                htmlspecialchars($admin['role']) . " - " . 
                                htmlspecialchars($admin['mot_de_passe']) . " - " .
                                "Ajouté le : " . date('d/m/Y', strtotime($admin['date_creation'])) . 
                            "</div>
                            <div class='action-buttons'>
                                " . ($admin['role'] !== 'chef' ? "
                                <form action='' method='POST' class='d-inline'>
                                    <input type='hidden' name='admin_id' value='" . htmlspecialchars($admin['id_admin']) . "'>
                                    <button type='submit' name='action' value='delete' class='btn btn-danger btn-sm'>Supprimer</button>" : "") . "
                                <button type='button' class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#updateModal" . htmlspecialchars($admin['id_admin']) . "'>Modifier</button>
                            </div>
                        </li>
                        <!-- Modal pour modifier l'administrateur -->
                        <div class='modal fade' id='updateModal" . htmlspecialchars($admin['id_admin']) . "' tabindex='-1' role='dialog'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h5 class='modal-title'>Modifier Administrateur</h5>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                    </div>
                                    <form method='POST'>
                                        <div class='modal-body'>
                                            <input type='hidden' name='admin_id' value='" . htmlspecialchars($admin['id_admin']) . "'>
                                            <div class='form-group'>
                                                <label for='new_nom'>Nom</label>
                                                <input type='text' class='form-control' name='new_nom' value='" . htmlspecialchars($admin['nom']) . "' required>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_prenom'>Prénom</label>
                                                <input type='text' class='form-control' name='new_prenom' value='" . htmlspecialchars($admin['prenom']) . "' required>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_email'>Email</label>
                                                <input type='email' class='form-control' name='new_email' value='" . htmlspecialchars($admin['email']) . "' required>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_role'>Rôle</label>";
                        if ($admin['role'] !== 'chef') {
                            echo "<select name='new_role' class='form-control' required>
                                    <option value='admin' " . ($admin['role'] === 'admin' ? 'selected' : '') . ">Admin</option>
                                 </select>";
                        } else {
                            echo "<input type='hidden' name='new_role' value='chef'>";
                        }
                        echo "</div>
                                            <div class='form-group'>
                                                <label for='new_password'>Mot de passe (laisser vide pour ne pas changer)</label>
                                                <input type='password' class='form-control' name='new_password' placeholder='Nouveau mot de passe'>
                                            </div>
                                        </div>
                                        <div class='modal-footer'>
                                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Fermer</button>
                                            <button type='submit' name='action' value='edit' class='btn btn-primary'>Mettre à jour</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>";
                }
                ?>
            </ul>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Réinitialiser la page lors du rafraîchissement
        if (performance.navigation.type === 1) {
            window.location.href = window.location.pathname;
        }

        
    </script>
    <script>
        // Auto-hide alerts after 3 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        });
    </script>
</body>
</html>
