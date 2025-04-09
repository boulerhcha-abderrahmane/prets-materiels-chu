<?php
session_start();
require_once '../../config/config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$debug_logs = []; // Pour stocker les informations de débogage

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Gestion du changement de mot de passe
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        try {
            // Vérifier le mot de passe actuel
            $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            $currentPassword = $stmt->fetchColumn();
            
            $debug_logs[] = "Vérification du mot de passe pour l'utilisateur ID: " . $userId;
            
            // Comparaison directe du mot de passe (sans password_verify)
            if ($_POST['current_password'] !== $currentPassword) {
                $errors[] = "Le mot de passe actuel est incorrect.";
                $debug_logs[] = "Échec de la vérification du mot de passe actuel";
            } else {
                // La validation de la longueur du mot de passe et la correspondance 
                // sont maintenant gérées par le modal côté client
                
                // Stocker le mot de passe directement (sans hachage)
                $newPassword = $_POST['new_password'];
                
                // Mise à jour du mot de passe dans la base de données
                $sql = "UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?";
                $debug_logs[] = "Exécution de la requête: " . $sql;
                
                $stmt = $pdo->prepare($sql);
                $params = [$newPassword, $userId];
                $result = $stmt->execute($params);
                
                if ($result) {
                    $rowCount = $stmt->rowCount();
                    $debug_logs[] = "Requête exécutée avec succès. Lignes affectées: " . $rowCount;
                    
                    if ($rowCount > 0) {
                        $success = "Mot de passe mis à jour avec succès.";
                    } else {
                        $errors[] = "Aucune modification effectuée. Vérifiez que le mot de passe est différent du précédent.";
                        $debug_logs[] = "Aucune ligne modifiée dans la base de données";
                    }
                } else {
                    $errors[] = "Erreur lors de la mise à jour du mot de passe.";
                    $debug_logs[] = "Échec de l'exécution de la requête";
                    $debug_logs[] = "PDO Error: " . json_encode($stmt->errorInfo());
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données: " . $e->getMessage();
            $debug_logs[] = "Exception PDO: " . $e->getMessage();
        }
    }

    // Si seule la photo est mise à jour (via le clic sur la photo)
    if (isset($_FILES['photo']) && empty($_POST['nom']) && empty($_POST['prenom'])) {
        // Récupérer les valeurs actuelles
        $stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateur WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch();
        $nom = $currentUser['nom'];
        $prenom = $currentUser['prenom'];
    } else {
        // Mise à jour normale avec tous les champs
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
    }

    // Gestion de l'upload de la photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        
        // Vérification du type de fichier
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($photo['type'], $allowed)) {
            $errors[] = "Format de fichier non autorisé. Utilisez JPG, PNG ou GIF.";
        } else if ($photo['size'] > 5 * 1024 * 1024) {
            $errors[] = "La taille du fichier doit être inférieure à 5MB.";
        } else {
            // Création du dossier uploads s'il n'existe pas
            $uploadDir = '../../uploads/user_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Génération d'un nom de fichier unique
            $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            // Suppression de l'ancienne photo
            $stmt = $pdo->prepare("SELECT photo FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            $oldPhoto = $stmt->fetchColumn();
            
            if ($oldPhoto && file_exists('../../' . $oldPhoto)) {
                unlink('../../' . $oldPhoto);
            }
            
            // Upload du nouveau fichier
            if (move_uploaded_file($photo['tmp_name'], $uploadPath)) {
                $photoPath = 'uploads/user_photos/' . $filename;
                
                // Mise à jour de la base de données
                $stmt = $pdo->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, photo = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nom, $prenom, $photoPath, $userId]);
                $success = "Profil mis à jour avec succès.";
            } else {
                $errors[] = "Erreur lors de l'upload du fichier.";
            }
        }
    } else if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Mise à jour sans nouvelle photo - mais seulement si ce n'est pas une erreur de fichier manquant
        $stmt = $pdo->prepare("UPDATE utilisateur SET nom = ?, prenom = ? WHERE id_utilisateur = ?");
        $stmt->execute([$nom, $prenom, $userId]);
        
        // Ne pas écraser le message de succès du changement de mot de passe
        if (!isset($success)) {
            $success = "Profil mis à jour avec succès.";
        }
    }
    
    // Stocker les variables de débogage dans la session pour les afficher après la redirection
    if (!empty($debug_logs)) {
        $_SESSION['debug_logs'] = $debug_logs;
    }
    
    // Stocker les erreurs et succès dans la session pour les afficher après la redirection
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
    
    if (isset($success)) {
        $_SESSION['success'] = $success;
    }
    
    // Redirection pour éviter la soumission multiple du formulaire
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Récupérer les messages de la session après redirection
if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['debug_logs'])) {
    $debug_logs = $_SESSION['debug_logs'];
    unset($_SESSION['debug_logs']);
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom, u.role, u.photo, u.date_creation, e.email 
    FROM utilisateur u
    JOIN email_autorise e ON u.id_email = e.id_email
    WHERE u.id_utilisateur = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Les erreurs doivent être disponibles pour l'affichage
if (empty($errors)) {
    $errors = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Ajout des styles CSS directement dans le head -->
    <style>
        /* Profile page styles */
        .profile-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 15px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.8) inset,
                0 0 100px rgba(255, 255, 255, 0.2) inset;
            backdrop-filter: blur(20px);
            overflow: hidden;
            position: relative;
            animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Profile container border effect */
        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                #4a90e2,
                #63b3ed,
                #4a90e2);
            background-size: 200% 100%;
            animation: gradientMove 6s ease-in-out infinite;
        }

        /* Profile photo styles */
        .photo-container {
            width: 120px;
            height: 120px;
            border: 4px solid #fff;
            border-radius: 50%;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.15),
                0 0 0 2px rgba(255, 255, 255, 0.9) inset;
            overflow: hidden;
            position: relative;
            cursor: zoom-in;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Zoom Icon */
        .zoom-icon {
            position: absolute;
            inset: 0;
            background: linear-gradient(165deg,
                rgba(74, 144, 226, 0.2) 0%,
                rgba(0, 0, 0, 0.6) 100%
            );
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .zoom-icon i {
            color: white;
            font-size: 24px;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Hover Effects */
        .photo-container:hover {
            transform: scale(1.05);
            box-shadow: 
                0 18px 35px rgba(0, 0, 0, 0.2),
                0 0 0 2px var(--primary-color, #4a90e2) inset;
        }

        .photo-container:hover img {
            transform: scale(1.15);
        }

        .photo-container:hover .zoom-icon {
            opacity: 1;
        }

        .photo-container:hover .zoom-icon i {
            transform: scale(1);
            opacity: 1;
        }

        /* Remove old hover styles */
        .photo-container::after,
        .photo-container:hover::after {
            display: none;
        }

        /* Profile header styles */
        .profile-header {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex: 1;
            min-width: 250px;
        }

        .profile-info h2 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            word-wrap: break-word;
            max-width: 100%;
            overflow-wrap: break-word;
        }

        /* User info styles */
        .user-header {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .user-info-line {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .user-role, .user-email, .user-since {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            color: #4a5568;
        }

        .user-role i, .user-email i, .user-since i {
            color: #4a90e2;
        }

        /* Edit form styles */
        .edit-form {
            background: linear-gradient(165deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            padding: 20px;
            border-radius: 25px;
            margin: 15px;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.05),
                0 0 0 1px rgba(255, 255, 255, 0.9) inset;
            position: relative;
        }

        .edit-form h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 20px;
        }

        /* Form controls */
        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        

        /* Logout button */
.logout-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.logout-btn {
    padding: 10px 50px;
    font-size: 20px;
   margin-bottom: auto;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

        /* Animations */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes gradientMove {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }
        /* Update button */
        .btn-update {
    background: linear-gradient(45deg,rgb(90, 151, 221) 0%, #357abd 100%);
 
    padding: 12px 30px;

    font-weight: 600;
    color: white;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
   
    margin-top: 15px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    width: 100%;
    max-width: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-update:hover {
    transform: translateY(-3px) scale(1.02);
    background: linear-gradient(45deg, #357abd 0%, #4a90e2 100%);
    box-shadow: 
        0 18px 35px rgba(74, 144, 226, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.2) inset;
}

.btn-update:active {
    transform: translateY(1px) scale(0.98);
    box-shadow: 
        0 8px 15px rgba(160, 190, 224, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.btn-update i {
    font-size: 1.2em;
    transition: transform 0.3s ease;
}

.btn-update:hover i {
    transform: rotate(180deg);
}

.btn-update::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(253, 245, 245, 0.2) 0%, transparent 60%);
    transform: scale(0);
    transition: transform 0.6s ease-out;
}

.btn-update:hover::before {
    transform: scale(1);
}

.photo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.photo-container {
    width: 120px;
    height: 120px;
    border: 4px solid #fff;
    border-radius: 50%;
    box-shadow: 
        0 15px 35px rgba(0, 0, 0, 0.15),
        0 0 0 2px rgba(255, 255, 255, 0.9) inset;
    overflow: hidden;
    position: relative;
    cursor: zoom-in;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.photo-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.photo-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
}

.photo-btn {
    width: 100%;
    padding: 8px 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.photo-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.photo-btn i {
    font-size: 16px;
}

/* Supprimer les styles suivants s'ils existent */
.photo-container::after,
.photo-container:hover::after,
.photo-actions,
.photo-action-btn {
    display: none;
}

/* Système de notifications en CSS */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    z-index: 2000;
    max-width: 360px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    transform: translateY(-20px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.notification.success {
    background: linear-gradient(135deg, #43a047, #2e7d32);
    color: white;
    border-left: 5px solid #1b5e20;
}

.notification.error {
    background: linear-gradient(135deg, #e53935, #c62828);
    color: white;
    border-left: 5px solid #b71c1c;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification i {
    font-size: 20px;
}

/* Optimisation mobile */
@media (max-width: 768px) {
    .profile-container {
        margin: 10px;
        padding: 10px;
        border-radius: 15px;
    }
    
    .profile-header {
        flex-direction: column;
        align-items: center;
        gap: 20px;
        padding: 15px 10px;
    }
    
    .profile-info {
        width: 100%;
        align-items: center;
        text-align: center;
    }
    
    .user-info-line {
        justify-content: center;
        gap: 15px;
    }
    
    .user-role, .user-email, .user-since {
        font-size: 14px;
    }
    
    .edit-form {
        padding: 15px;
        margin: 10px;
    }
    
    .logout-container {
        position: static;
        margin: 20px auto;
        display: flex;
        justify-content: center;
    }
    
    .logout-btn {
        padding: 8px 30px;
        font-size: 16px;
        width: 100%;
        max-width: 300px;
    }
    
    .notification {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: calc(100% - 20px);
    }
    
    .photo-container {
        width: 100px;
        height: 100px;
    }
    
    .btn-update {
        max-width: 100%;
    }
}

/* Effet de transition pour l'application entière */
.modal-content {
    transform: scale(0.9);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal.show .modal-content {
    transform: scale(1);
    opacity: 1;
}

/* Ajout d'effets de survol pour les éléments interactifs */
input:focus, select:focus, textarea:focus {
    transition: all 0.3s ease;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.3);
}

.form-control {
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    border-color: #4a90e2;
}

    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="profile-container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($debug_logs) && $_SESSION['user_id'] == 1): ?>
            <div class="alert alert-info alert-dismissible fade show m-3" role="alert">
                <h5>Logs de débogage</h5>
                <ul class="mb-0">
                    <?php foreach ($debug_logs as $log): ?>
                        <li><?= htmlspecialchars($log) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Ajout du conteneur pour le bouton de déconnexion -->
        <div class="logout-container">
            <a href="../logout.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-danger logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
            </a>
        </div>

        <div class="profile-header">
            <div class="photo-section">
                <div class="photo-container" onclick="openPhotoZoom()">
                    <img src="<?= !empty($user['photo']) ? '../../' . $user['photo'] : '../../uploads/user_photos/default_profile.png' ?>" alt="Photo de Profil" id="previewImage">
                    <div class="zoom-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="photo-buttons">
                    <button type="button" class="btn btn-primary btn-sm photo-btn" onclick="document.getElementById('photoInput').click();">
                        <i class="fas fa-camera"></i> Modifier la photo
                    </button>
                    <?php if (!empty($user['photo'])): ?>
                    <button type="button" class="btn btn-danger btn-sm photo-btn" onclick="deletePhoto()">
                        <i class="fas fa-trash"></i> Supprimer la photo
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-info">
                <div class="user-header">
                    <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
                    <div class="user-info-line">
                        <div class="user-role">
                            <i class="fas fa-user-tie"></i>
                            <?= htmlspecialchars($user['role']) ?>
                        </div>
                        <div class="user-email">
                            <i class="fas fa-envelope"></i>
                            <?= htmlspecialchars($user['email']) ?>
                        </div>
                        <div class="user-since">
                            <i class="fas fa-calendar-alt"></i>
                            Membre depuis: <?= (new DateTime($user['date_creation']))->format('d/m/Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-form">
            <h3><i class="fas fa-edit"></i> Modifier le Profil</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <h5 for="prenom">Prénom</h5>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <h5 for="nom">Nom</h5>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <h5 for="current_password">Mot de passe actuel</h5>
                    <input type="password" class="form-control" id="current_password" name="current_password">
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">  
                        <div class="form-group">
                            <h5 for="new_password">Nouveau mot de passe</h5>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <h5 for="confirm_password">Confirmer le nouveau mot de passe</h5>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                </div>
                
                <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;">
                
                <button type="submit" class="btn btn-update mt-4">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
            </form>
        </div>
    </div>
    <div class="modal fade" id="photoConfirmModal" tabindex="-1" aria-labelledby="photoConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoConfirmModalLabel">Confirmer le changement de photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="modalPreviewImage" src="" alt="Prévisualisation" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Format accepté : JPG, PNG, GIF (max 5MB)
                    </div>
                    <p>Voulez-vous vraiment changer votre photo de profil ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirmPhotoChange">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-labelledby="deletePhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePhotoModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Êtes-vous sûr de vouloir supprimer votre photo de profil ?
                    </div>
                    <p>Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDeletePhoto()">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal pour le zoom de la photo -->
    <div class="modal fade" id="photoZoomModal" tabindex="-1" aria-labelledby="photoZoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoZoomModalLabel">Photo de profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="zoomedImage" src="" alt="Photo de profil en grand format" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Nouvelle modal pour la validation du mot de passe -->
    <div class="modal fade" id="passwordValidationModal" tabindex="-1" aria-labelledby="passwordValidationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="passwordValidationModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Validation du mot de passe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <p id="passwordValidationMessage">Le nouveau mot de passe doit contenir au moins 8 caractères.</p>
                    </div>
                    <div class="password-strength-info mt-3">
                        <h6>Un mot de passe fort devrait contenir :</h6>
                        <ul>
                            <li>Au moins 8 caractères</li>
                            <li>Au moins une lettre majuscule</li>
                            <li>Au moins une lettre minuscule</li>
                            <li>Au moins un chiffre</li>
                            <li>Au moins un caractère spécial (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Compris</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal pour les mots de passe qui ne correspondent pas -->
    <div class="modal fade" id="passwordMismatchModal" tabindex="-1" aria-labelledby="passwordMismatchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="passwordMismatchModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Erreur de mot de passe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <p>Les nouveaux mots de passe ne correspondent pas.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Compris</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Champ caché pour l'ID utilisateur -->
    <input type="hidden" id="userId" value="<?= htmlspecialchars($userId) ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/profile.js"></script>
    
    <script>
        // Script supplémentaire pour la validation par modal du mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    const currentPassword = document.getElementById('current_password').value;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    // Si les champs de mot de passe sont remplis, on vérifie
                    if (currentPassword && newPassword && confirmPassword) {
                        
                        // Vérification de la longueur du mot de passe
                        if (newPassword.length < 8) {
                            e.preventDefault(); // Empêcher la soumission du formulaire
                            
                            // Afficher la modal pour mot de passe trop court
                            const passwordValidationModal = new bootstrap.Modal(document.getElementById('passwordValidationModal'));
                            passwordValidationModal.show();
                            return false;
                        }
                        
                        // Vérification que les mots de passe correspondent
                        if (newPassword !== confirmPassword) {
                            e.preventDefault(); // Empêcher la soumission du formulaire
                            
                            // Afficher la modal pour mots de passe qui ne correspondent pas
                            const passwordMismatchModal = new bootstrap.Modal(document.getElementById('passwordMismatchModal'));
                            passwordMismatchModal.show();
                            return false;
                        }
                    }
                    
                    // Si tout est OK, le formulaire est soumis normalement
                    return true;
                });
            }
        });
    </script>
</body>
</html>