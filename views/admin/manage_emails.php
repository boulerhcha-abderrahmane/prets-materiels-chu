<?php
session_start(); // Démarre la session
ob_start(); // Démarre la mise en mémoire tampon de sortie
require_once '../../config/config.php';

// Fonction pour vérifier la validité de l'email via l'API
function verifyEmailViaAPI($email) {
    $apiKey = '02a5b70f28db410c8935688e6fd03c68';
    $url = "https://emailvalidation.abstractapi.com/v1/?api_key=" . $apiKey . "&email=" . urlencode($email);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Vérifier les erreurs curl
    if(curl_errno($ch)) {
        error_log("Curl error: " . curl_error($ch));
        curl_close($ch);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Debug détaillé
    error_log("URL appelée: " . $url);
    error_log("Code HTTP: " . $httpCode);
    error_log("Réponse API pour $email: " . print_r($result, true));
    
    // Si erreur de quota ou autre erreur API
    if (isset($result['error'])) {
        error_log("API Error: " . $result['error']['message']);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    if ($httpCode !== 200) {
        error_log("API Error: HTTP Code $httpCode");
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Vérification plus souple
    $isValidFormat = isset($result['is_valid_format']['value']) ? 
                     $result['is_valid_format']['value'] : 
                     filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
                     
    $isDeliverable = isset($result['deliverability']) && 
                     in_array($result['deliverability'], ['DELIVERABLE', 'UNKNOWN', 'RISKY']);
    
    return $isValidFormat && $isDeliverable;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        $email = $_POST['email'];
        $admin_id = $_SESSION['admin_id'];
        
        // Vérifier si l'email existe via l'API
        if (!verifyEmailViaAPI($email)) {
            $_SESSION['error'] = "L'adresse email n'existe pas.";
            header("Location: manage_emails.php");
            exit();
        } else {
            // Vérifier si l'email existe déjà dans la base de données
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM email_autorise WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetchColumn() > 0) {
                $_SESSION['error'] = "L'email existe déjà, veuillez en utiliser un autre.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Ajouter l'email (sans id_admin car la colonne n'existe pas)
                    $sql = "INSERT INTO email_autorise (email) VALUES (:email)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'email' => $email
                    ]);

                    // Récupérer l'id de l'email qui vient d'être ajouté
                    $email_id = $pdo->lastInsertId();

                    // Enregistrer dans l'historique
                    $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, date_action, details, id_email) 
                                     VALUES (:id_admin, :type_action, NOW(), :details, :id_email)";
                    $stmt_historique = $pdo->prepare($sql_historique);
                    $stmt_historique->execute([
                        'id_admin' => $admin_id,
                        'type_action' => 'AJOUT_EMAIL',
                        'details' =>  $email,
                        'id_email' => $email_id
                    ]);

                    $pdo->commit();
                    $_SESSION['success'] = "L'email a été ajouté avec succès!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Erreur SQL: " . $e->getMessage());
                    $_SESSION['error'] = "Une erreur est survenue lors de l'ajout de l'email: " . $e->getMessage();
                }
            }
        }
        header("Location: manage_emails.php");
        exit();
    } elseif ($_POST['action'] === 'delete') {
        if (!isset($_SESSION['admin_id'])) {
            $_SESSION['error'] = "Session administrateur non valide. Veuillez vous reconnecter.";
            header("Location: manage_emails.php");
            exit();
        }

        $email_id = $_POST['email_id'];
        $admin_id = $_SESSION['admin_id'];

        // Récupérer les informations de l'admin
        $adminStmt = $pdo->prepare("SELECT nom, prenom FROM administrateur WHERE id_admin = ?");
        $adminStmt->execute([$admin_id]);
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        $admin_nom = $admin['nom'];
        $admin_prenom = $admin['prenom'];

        // Vérifier si l'email est utilisé par des utilisateurs
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_email = ?");
        $checkStmt->execute([$email_id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Impossible de supprimer cet email car il est utilisé par des utilisateurs.";
        } else {
            try {
                $pdo->beginTransaction();

                // Récupérer l'email avant la suppression
                $stmt = $pdo->prepare("SELECT email FROM email_autorise WHERE id_email = ?");
                $stmt->execute([$email_id]);
                $email = $stmt->fetchColumn();

                // Mettre à NULL les références dans historique_actions
                

                // Supprimer l'email
                $stmt = $pdo->prepare("DELETE FROM email_autorise WHERE id_email = ?");
                $stmt->execute([$email_id]);

                // Enregistrer dans l'historique
                $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, date_action, details) 
                                 VALUES (:id_admin, :type_action, NOW(), :details)";
                $stmt_historique = $pdo->prepare($sql_historique);
                $stmt_historique->execute([
                    'id_admin' => $admin_id,
                    'type_action' => 'SUPPRESSION_EMAIL',
                    'details' => "Email supprimé : " . $email 
                ]);

                $pdo->commit();
                $_SESSION['success'] = "L'email a été supprimé avec succès!";
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Erreur SQL: " . $e->getMessage());
                $_SESSION['error'] = "Une erreur est survenue lors de la suppression de l'email: " . $e->getMessage();
            }
        }
        header("Location: manage_emails.php");
        exit();
    } elseif ($_POST['action'] === 'edit') {
        $email_id = $_POST['email_id'];
        $new_email = $_POST['new_email'];
        
        // Vérifier si le nouvel email existe déjà
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM email_autorise WHERE email = ? AND id_email != ?");
        $checkStmt->execute([$new_email, $email_id]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Cet email existe déjà, veuillez en utiliser un autre.";
        } else {
            // Vérifier si l'email est utilisé par des utilisateurs
            $userCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_email = ?");
            $userCheckStmt->execute([$email_id]);
            
            if ($userCheckStmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Impossible de modifier cet email car il est utilisé par des utilisateurs.";
            } else {
                $stmt = $pdo->prepare("UPDATE email_autorise SET email = ? WHERE id_email = ?");
                $stmt->execute([$new_email, $email_id]);
                $_SESSION['success'] = "L'email a été modifié avec succès!";
            }
        }
        header("Location: manage_emails.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emails Autorisés</title>
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
            <h1 class="mb-4">Gestion des Emails Autorisés</h1>
            
            <?php
            // Afficher le message d'erreur s'il existe
            if (isset($_SESSION['error'])) {
                echo "<div class='alert alert-danger alert-dismissible fade show'>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>" 
                        . htmlspecialchars($_SESSION['error']) . 
                    "</div>";
                unset($_SESSION['error']);
            }
            // Afficher le message de succès s'il existe
            if (isset($_SESSION['success'])) {
                echo "<div class='alert alert-success alert-dismissible fade show'>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>" 
                        . htmlspecialchars($_SESSION['success']) . 
                    "</div>";
                unset($_SESSION['success']);
            }
            ?>
            
            <!-- Formulaire pour ajouter un nouvel email -->
            <form action="" method="POST" class="mb-4">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email autorisé" required>
                </div>
                <button type="submit" name="action" value="add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter Email
                </button>
            </form>
            
            <h3>Emails Autorisés Existants</h3>
            <ul class="list-group">
                <?php
                // Récupérer tous les emails autorisés avec les informations de l'admin qui les a ajoutés
                $stmt = $pdo->query("
                    SELECT ea.id_email, ea.email, 
                           COALESCE(a.nom, 'Inconnu') as admin_nom,
                           COALESCE(a.prenom, '') as admin_prenom,
                           ha.date_action
                    FROM email_autorise ea
                    LEFT JOIN historique_actions ha ON ea.id_email = ha.id_email 
                        AND ha.type_action = 'AJOUT_EMAIL'
                    LEFT JOIN administrateur a ON ha.id_admin = a.id_admin
                ");
                while ($email = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $date_ajout = isset($email['date_action']) ? 
                        date('d/m/Y H:i', strtotime($email['date_action'])) : 
                        'Date inconnue';
                    
                    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                            <div>
                                " . htmlspecialchars($email['email']) . "
                                <small class='text-muted ms-2'>
                                    (Ajouté par : <strong>" . htmlspecialchars($email['admin_prenom'] . ' ' . $email['admin_nom']) . "</strong>
                                    le " . $date_ajout . ")
                                </small>
                            </div>
                            <div>
                                <form action='' method='POST' class='d-inline'>
                                    <input type='hidden' name='email_id' value='" . htmlspecialchars($email['id_email']) . "'>
                                    <button type='submit' name='action' value='delete' class='btn btn-danger btn-sm'>
                                        <i class='fas fa-trash'></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        // Auto-hide alerts after 3 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000); // Délai de 3 secondes
        });
    </script>
</body>
</html>