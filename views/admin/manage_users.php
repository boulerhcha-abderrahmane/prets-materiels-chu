<?php
ob_start(); // Démarre la mise en mémoire tampon de sortie
session_start();
require_once '../../config/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
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
            flex-grow: 1;
        }
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1>Gestion des Utilisateurs</h1>
            
            <?php if (isset($_SESSION['user_management_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo htmlspecialchars($_SESSION['user_management_error']);
                    unset($_SESSION['user_management_error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire pour ajouter un nouvel utilisateur -->
            <form action="" method="POST" class="mb-4">
                <div class="form-group mb-2">
                    <label for="add_nom">Nom</label>
                    <input type="text" id="add_nom" name="nom" class="form-control" placeholder="Nom" autocomplete="family-name" required>
                </div>
                <div class="form-group mb-2">
                    <label for="add_prenom">Prénom</label>
                    <input type="text" id="add_prenom" name="prenom" class="form-control" placeholder="Prénom" autocomplete="given-name" required>
                </div>
                <div class="form-group mb-2">
                    <label for="add_email">Email autorisé</label>
                    <input type="email" id="add_email" name="email" class="form-control" placeholder="Email autorisé" autocomplete="email" required>
                </div>
                <div class="form-group mb-2">
                    <label for="add_password">Mot de passe</label>
                    <input type="password" id="add_password" name="password" class="form-control" placeholder="Mot de passe" autocomplete="new-password" required>
                </div>
                <div class="form-group mb-2">
                    <label for="add_role">Type d'utilisateur</label>
                    <select id="add_role" name="role" class="form-control" autocomplete="organization-title" required>
                        <option value="">Sélectionnez le type d'utilisateur</option>
                        <option value="technicien">Technicien</option>
                        <option value="ingénieur informatique">Ingénieur Informatique</option>
                    </select>
                </div>
                <button type="submit" name="action" value="add" class="btn btn-primary">Ajouter Utilisateur</button>
            </form>
            
            <h3>Utilisateurs Existants</h3>
            <ul class="list-group">
                <?php
                // Récupérer tous les utilisateurs de la base de données avec leurs emails
                $stmt = $pdo->query("
                    SELECT u.id_utilisateur, u.nom, u.prenom, e.email, u.role, u.mot_de_passe, u.date_creation,
                           ha.id_admin, a.nom as admin_nom, a.prenom as admin_prenom 
                    FROM utilisateur u 
                    JOIN email_autorise e ON u.id_email = e.id_email
                    LEFT JOIN historique_actions ha ON u.id_utilisateur = ha.id_utilisateur AND ha.type_action = 'AJOUT_UTILISATEUR'
                    LEFT JOIN administrateur a ON ha.id_admin = a.id_admin");

                while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                            <div>
                                " . htmlspecialchars($user['nom']) . " " . htmlspecialchars($user['prenom']) . " - " . 
                                htmlspecialchars($user['email']) . " - " . 
                                htmlspecialchars($user['role']) . " - " . 
                                htmlspecialchars($user['mot_de_passe']) . "
                                <small class='text-muted d-block'>
                                    Ajouté par: " . 
                                    ($user['admin_nom'] ? htmlspecialchars($user['admin_nom'] . ' ' . $user['admin_prenom']) : 'N/A') . 
                                    " le " . 
                                    ($user['date_creation'] ? date('d/m/Y H:i', strtotime($user['date_creation'])) : 'N/A') . 
                                "</small>
                            </div>
                            <div class='action-buttons'>
                                <form action='' method='POST' class='d-inline'>
                                    <input type='hidden' name='user_id' value='" . htmlspecialchars($user['id_utilisateur']) . "'>
                                    <button type='submit' name='action' value='delete' class='btn btn-danger btn-sm'>Supprimer</button>
                                </form>
                                <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#updateModal" . htmlspecialchars($user['id_utilisateur']) . "'>Modifier</button>
                            </div>
                        </li>
                        <!-- Modal pour modifier l'utilisateur -->
                        <div class='modal fade' id='updateModal" . htmlspecialchars($user['id_utilisateur']) . "' tabindex='-1' role='dialog'>
                            <div class='modal-dialog' role='document'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h5 class='modal-title'>Modifier Utilisateur</h5>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                            <span aria-hidden='true'>&times;</span>
                                        </button>
                                    </div>
                                    <form method='POST'>
                                        <div class='modal-body'>
                                            <input type='hidden' name='user_id' value='" . htmlspecialchars($user['id_utilisateur']) . "'>
                                            <div class='form-group'>
                                                <label for='new_nom_" . htmlspecialchars($user['id_utilisateur']) . "'>Nom</label>
                                                <input type='text' id='new_nom_" . htmlspecialchars($user['id_utilisateur']) . "' class='form-control' name='new_nom' value='" . htmlspecialchars($user['nom']) . "' autocomplete='family-name' required>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_prenom_" . htmlspecialchars($user['id_utilisateur']) . "'>Prénom</label>
                                                <input type='text' id='new_prenom_" . htmlspecialchars($user['id_utilisateur']) . "' class='form-control' name='new_prenom' value='" . htmlspecialchars($user['prenom']) . "' autocomplete='given-name' required>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_email_" . htmlspecialchars($user['id_utilisateur']) . "'>Email</label>
                                                <input type='email' id='new_email_" . htmlspecialchars($user['id_utilisateur']) . "' class='form-control' name='new_email' value='" . htmlspecialchars($user['email']) . "' autocomplete='email' required>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_role_" . htmlspecialchars($user['id_utilisateur']) . "'>Rôle</label>
                                                <select id='new_role_" . htmlspecialchars($user['id_utilisateur']) . "' name='new_role' class='form-control' autocomplete='organization-title' required>
                                                    <option value='technicien' " . ($user['role'] === 'technicien' ? 'selected' : '') . ">Technicien</option>
                                                    <option value='ingénieur informatique' " . ($user['role'] === 'ingénieur informatique' ? 'selected' : '') . ">Ingénieur Informatique</option>
                                                </select>
                                            </div>
                                            <div class='form-group'>
                                                <label for='new_password_" . htmlspecialchars($user['id_utilisateur']) . "'>Mot de passe (laisser vide pour ne pas changer)</label>
                                                <input type='password' id='new_password_" . htmlspecialchars($user['id_utilisateur']) . "' class='form-control' name='new_password' placeholder='Nouveau mot de passe' autocomplete='new-password'>
                                            </div>
                                        </div>
                                        <div class='modal-footer'>
                                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Fermer</button>
                                            <button type='submit' name='action' value='update' class='btn btn-primary'>Mettre à jour</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>";
                }

                // Traitement des actions
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if ($_POST['action'] === 'add') {
                        try {
                            $pdo->beginTransaction();
                            
                            $nom = $_POST['nom'];
                            $prenom = $_POST['prenom'];
                            $email = $_POST['email'];
                            $password = $_POST['password'];
                            $role = $_POST['role'];

                            // Vérifier si l'email existe déjà
                            $stmt = $pdo->prepare("SELECT id_email FROM email_autorise WHERE email = ?");
                            $stmt->execute([$email]);
                            $id_email = $stmt->fetchColumn();

                            if (!$id_email) {
                                throw new Exception("L'email n'est pas autorisé,vous devez d'abord ajouter cet email.");
                            }

                            // Vérifier si l'utilisateur existe déjà avec cet id_email
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_email = ?");
                            $stmt->execute([$id_email]);
                            $userExists = $stmt->fetchColumn();

                            if ($userExists > 0) {
                                throw new Exception("Un utilisateur avec cet email existe déjà.");
                            }

                            // Ajouter l'utilisateur
                            $stmt = $pdo->prepare("INSERT INTO utilisateur (id_email, nom, prenom, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$id_email, $nom, $prenom, $password, $role]);
                            $userId = $pdo->lastInsertId();

                            // Enregistrer dans l'historique
                            $details = sprintf(
                                "Ajout de l'utilisateur - Nom: %s, Prénom: %s, Email: %s, Rôle: %s",
                                $nom, $prenom, $email, $role
                            );

                            $stmt_historique = $pdo->prepare("INSERT INTO historique_actions (id_admin, type_action, date_action, details) VALUES (?, ?, NOW(), ?)");
                            $stmt_historique->execute([$_SESSION['admin_id'], 'AJOUT_UTILISATEUR', $details]);

                            $pdo->commit();
                            header("Location: manage_users.php");
                            exit();

                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $_SESSION['user_management_error'] = $e->getMessage();
                            header("Location: manage_users.php");
                            exit();
                        }
                    } elseif ($_POST['action'] === 'delete') {
                        try {
                            $pdo->beginTransaction();
                            
                            $user_id = $_POST['user_id'];

                            // Récupérer les informations de l'utilisateur avant suppression
                            $stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.role, e.email FROM utilisateur u JOIN email_autorise e ON u.id_email = e.id_email WHERE u.id_utilisateur = ?");
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                            // Supprimer d'abord les entrées dans l'historique
                            $stmt = $pdo->prepare("DELETE FROM historique_actions WHERE id_utilisateur = ?");
                            $stmt->execute([$user_id]);

                            // Supprimer les notifications associées
                            $stmt = $pdo->prepare("DELETE FROM notification WHERE id_utilisateur = ?");
                            $stmt->execute([$user_id]);

                            // Supprimer les demandes de prêt associées
                            $stmt = $pdo->prepare("DELETE FROM demande_pret WHERE id_utilisateur = ?");
                            $stmt->execute([$user_id]);
                            
                            // Supprimer l'utilisateur
                            $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
                            $stmt->execute([$user_id]);

                            // Enregistrer dans l'historique
                            $details = sprintf(
                                "Suppression de l'utilisateur - Nom: %s, Prénom: %s, Email: %s, Rôle: %s",
                                $user['nom'], $user['prenom'], $user['email'], $user['role']
                            );

                            $stmt_historique = $pdo->prepare("INSERT INTO historique_actions (id_admin, type_action, date_action, details) VALUES (?, ?, NOW(), ?)");
                            $stmt_historique->execute([$_SESSION['admin_id'], 'SUPPRESSION_UTILISATEUR', $details]);

                            $pdo->commit();
                            header("Location: manage_users.php");
                            exit();

                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $_SESSION['user_management_error'] = "Erreur lors de la suppression : " . $e->getMessage();
                            header("Location: manage_users.php");
                            exit();
                        }
                    } elseif ($_POST['action'] === 'update') {
                        try {
                            $pdo->beginTransaction();

                            $user_id = $_POST['user_id'];
                            $new_email = $_POST['new_email'];
                            $new_nom = $_POST['new_nom'];
                            $new_prenom = $_POST['new_prenom'];
                            $new_role = $_POST['new_role'];
                            $new_password = $_POST['new_password'];

                            // Récupérer les anciennes informations
                            $stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.role, e.email FROM utilisateur u JOIN email_autorise e ON u.id_email = e.id_email WHERE u.id_utilisateur = ?");
                            $stmt->execute([$user_id]);
                            $oldUser = $stmt->fetch(PDO::FETCH_ASSOC);

                            // Vérifier si le nouvel email existe et obtenir son id_email
                            $stmt = $pdo->prepare("SELECT id_email FROM email_autorise WHERE email = ?");
                            $stmt->execute([$new_email]);
                            $id_email = $stmt->fetchColumn();

                            if (!$id_email) {
                                throw new Exception("Cet email n'est pas autorisé, vous devez d'abord ajouter cet email.");
                            }

                            // Préparer la requête de mise à jour
                            $sql = "UPDATE utilisateur SET id_email = ?, nom = ?, prenom = ?, role = ?";
                            $params = [$id_email, $new_nom, $new_prenom, $new_role];

                            if ($new_password) {
                                $sql .= ", mot_de_passe = ?";
                                $params[] = $new_password;
                            }

                            $sql .= " WHERE id_utilisateur = ?";
                            $params[] = $user_id;

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);

                            // Préparer les détails des modifications
                            $changes = [];
                            if ($oldUser['nom'] !== $new_nom) {
                                $changes[] = sprintf("Nom: %s → %s", $oldUser['nom'], $new_nom);
                            }
                            if ($oldUser['prenom'] !== $new_prenom) {
                                $changes[] = sprintf("Prénom: %s → %s", $oldUser['prenom'], $new_prenom);
                            }
                            if ($oldUser['email'] !== $new_email) {
                                $changes[] = sprintf("Email: %s → %s", $oldUser['email'], $new_email);
                            }
                            if ($oldUser['role'] !== $new_role) {
                                $changes[] = sprintf("Rôle: %s → %s", $oldUser['role'], $new_role);
                            }
                            if ($new_password) {
                                $changes[] = "Mot de passe modifié";
                            }

                            $details = "Modification de l'utilisateur " . $new_nom . " " . $new_prenom . " - Changements : " . implode(", ", $changes);

                            // Enregistrer dans l'historique
                            $stmt_historique = $pdo->prepare("INSERT INTO historique_actions (id_admin, type_action, date_action, details) VALUES (?, ?, NOW(), ?)");
                            $stmt_historique->execute([$_SESSION['admin_id'], 'MODIFICATION_UTILISATEUR', $details]);

                            $pdo->commit();
                            header("Location: manage_users.php");
                            exit();

                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $_SESSION['user_management_error'] = "Erreur lors de la modification : " . $e->getMessage();
                            header("Location: manage_users.php");
                            exit();
                        }
                    }
                }
                ?>
            </ul>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // supprimer l'alerte après 1 secondes  
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
        
    </script>
</body>
</html>
