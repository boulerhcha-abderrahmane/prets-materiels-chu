<?php
session_start();
require_once '../../config/config.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'admin
$admin_id = $_SESSION['admin_id'];
$admin = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE id_admin = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        // Si aucun admin n'est trouvé avec cet ID, rediriger vers la page de connexion
        header('Location: login.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des données admin : " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Initialisation des variables
$role = ($admin['role'] === 'chef') ? 'Chef' : 'Administrateur';
$canEditEmail = ($admin['role'] === 'chef');
$default_image = 'uploads/admin_photos/default_profile.png';
$errors = [];
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
unset($_SESSION['success_message']);

// Fonction pour gérer l'upload de photo
function handlePhotoUpload($file, $admin_id, $current_photo) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => "Le type de fichier n'est pas autorisé. Utilisez JPG, PNG ou GIF."];
    }
    
    if ($file['size'] > $max_size) {
        return ['error' => "La taille du fichier dépasse 5MB."];
    }
    
    $upload_dir = __DIR__ . '/../../uploads/admin_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
    $new_file_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
        // Supprime l'ancienne photo si elle existe et n'est pas celle par défaut
        if ($current_photo && $current_photo !== $default_image && file_exists(__DIR__ . '/../../' . $current_photo)) {
            unlink(__DIR__ . '/../../' . $current_photo);
        }
        return ['path' => 'uploads/admin_photos/' . $new_filename];
    }
    
    return ['error' => "Erreur lors de l'upload de la photo."];
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des champs de base
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe_actuel = trim($_POST['current_password'] ?? '');
    $nouveau_mot_de_passe = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation des champs requis
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format d'email invalide";

    // Vérifier que l'email n'est modifié que si l'utilisateur a les droits
    if (!$canEditEmail && $email !== $admin['email']) {
        $email = $admin['email']; // Réinitialiser l'email s'il n'a pas les droits
    }

    // Validation du mot de passe
    if (!empty($nouveau_mot_de_passe)) {
        if (empty($mot_de_passe_actuel)) {
            $errors[] = "Le mot de passe actuel est requis pour changer le mot de passe";
        } elseif ($mot_de_passe_actuel !== $admin['mot_de_passe']) {
            $errors[] = "Le mot de passe actuel est incorrect";
        } elseif (strlen($nouveau_mot_de_passe) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
        } elseif ($nouveau_mot_de_passe !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
    }

    // Gestion de la photo
    $photo_path = $admin['photo'] ?: $default_image;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_result = handlePhotoUpload($_FILES['photo'], $admin_id, $photo_path);
        if (isset($photo_result['error'])) {
            $errors[] = $photo_result['error'];
        } else {
            $photo_path = $photo_result['path'];
        }
    }

    // Mise à jour si pas d'erreurs
    if (empty($errors)) {
        try {
            $sql = "UPDATE administrateur SET nom = ?, prenom = ?, email = ?, photo = ?";
            $params = [$nom, $prenom, $email, $photo_path];
            
            if (!empty($nouveau_mot_de_passe)) {
                $sql .= ", mot_de_passe = ?";
                $params[] = $nouveau_mot_de_passe;
            }
            
            $sql .= " WHERE id_admin = ?";
            $params[] = $admin_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $_SESSION['success_message'] = "Profil mis à jour avec succès";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch(PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour du profil";
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styles de base */
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #f8f9fa;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        /* Container principal */
        .profile-container {
            margin-left: 300px;
            height: 100vh;
            padding: 15px;
            background: rgba(255, 255, 255, 0.95);
            overflow: auto;
        }
        
        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4a90e2, #63b3ed, #4a90e2);
            background-size: 200% 100%;
            animation: gradientMove 6s ease-in-out infinite;
        }

        /* Photos */
        .photo-container {
            width: 150px;
            height: 150px;
            border: 4px solid #fff;
            border-radius: 50%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15), 0 0 0 2px rgba(255, 255, 255, 0.9) inset;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
            position: relative;
            cursor: zoom-in;
            animation: subtle-pulse 3s ease-in-out infinite;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .photo-container:hover {
            animation: none;
            transform: scale(1.05) rotate(3deg);
        }

        .photo-container:hover img {
            transform: scale(1.1);
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
            border-radius: 50%;
        }

        .zoom-icon i {
            color: white;
            font-size: 24px;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .photo-container:hover .zoom-icon {
            opacity: 1;
        }

        .photo-container:hover .zoom-icon i {
            transform: scale(1);
            opacity: 1;
        }

        /* Shine Effect */
        .shine-effect {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                45deg,
                transparent 0%,
                rgba(255, 255, 255, 0) 45%,
                rgba(255, 255, 255, 0.4) 50%,
                rgba(255, 255, 255, 0) 55%,
                transparent 100%
            );
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            border-radius: 50%;
        }

        .photo-container:hover .shine-effect {
            opacity: 1;
            animation: shine 1s ease-in-out;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%);
            }
            100% {
                transform: translateX(100%) translateY(100%);
            }
        }
        
        /* Pulse Animation */
        @keyframes subtle-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* Zoom Modal Styles */
        #photoZoomModal .modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: none;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.9) inset;
            animation: modalFadeIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        #photoZoomModal .modal-header {
            border-bottom: none;
            padding: 20px 25px;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
        }
        
        #photoZoomModal .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        #photoZoomModal .btn-close {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            padding: 12px;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        #photoZoomModal .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
            background-color: rgba(0, 0, 0, 0.15);
        }
        
        #photoZoomModal .modal-body {
            padding: 0;
            background: linear-gradient(165deg, rgba(255, 255, 255, 0.5) 0%, rgba(240, 242, 245, 0.5) 100%);
        }
        
        #zoomedImage {
            max-height: 80vh;
            object-fit: contain;
            padding: 25px;
            border-radius: 15px;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
        }
        
        #zoomedImage:hover {
            transform: scale(1.02);
            filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.15));
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal.fade .modal-dialog {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .modal.fade.show .modal-dialog {
            transform: none;
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

        /* Section photo */
        .photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .photo-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        .photo-btn {
            width: 100%;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .photo-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .photo-btn.btn-primary {
            background: linear-gradient(45deg, #4a90e2, #357abd);
            border: none;
        }

        .photo-btn.btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #dc3545);
            border: none;
        }

        /* En-tête du profil */
        .profile-header {
            display: flex;
            align-items: flex-start;
            gap: 40px;
            padding: 15px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(248, 250, 252, 0.8) 100%);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .profile-info h2 {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #2c3e50, #4a90e2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Ligne d'informations */
        .user-info-line {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .user-role, .user-email, .user-since {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #4a5568;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .user-role:hover, .user-email:hover, .user-since:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .user-role i, .user-email i, .user-since i {
            color: #4a90e2;
            font-size: 18px;
        }

        /* Formulaire d'édition */
        .edit-form {
            background: linear-gradient(165deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            padding: 15px;
            margin: 10px 0;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(255, 255, 255, 0.9) inset;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
        }

        .edit-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.9) inset;
        }

        .edit-form h3 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(74, 144, 226, 0.2);
        }

        .edit-form h3 i {
            color: #4a90e2;
            font-size: 22px;
        }

        /* Contrôles du formulaire */
        .form-group {
            margin-bottom: 10px;
            position: relative;
        }

        .form-control {
            border: 2px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            font-size: 16px;
            color: #2c3e50;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.1), 0 8px 20px -8px rgba(74, 144, 226, 0.2);
            transform: translateY(-2px);
        }

        /* Bouton de mise à jour */
        .btn-update {
            background: linear-gradient(45deg, #4a90e2, #357abd);
            border: none;
            padding: 14px 35px;
            border-radius: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 12px 25px rgba(74, 144, 226, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-update::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #357abd, #4a90e2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .btn-update:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 18px 35px rgba(74, 144, 226, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2) inset;
        }

        .btn-update:hover::before {
            left: 0;
        }

        /* Alertes */
        .alert {
            border: none;
            border-radius: 15px;
            padding: 18px 25px;
            margin: 20px 25px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
            backdrop-filter: blur(10px);
            animation: slideInDown 0.5s ease forwards;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.95) 0%, rgba(195, 230, 203, 0.9) 100%);
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.95) 0%, rgba(245, 198, 203, 0.9) 100%);
            border-left: 4px solid #dc3545;
        }

        /* Animations */
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes gradientMove {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }

        /* Media queries */
        @media (min-width: 1600px) {
            .profile-container {
                width: calc(100% - 310px);
            }
        }

        @media (max-width: 1200px) {
            .profile-container {
                width: calc(100% - 310px);
                margin: 0 auto;
            }
        }

        @media (max-width: 992px) {
            body {
                margin-left: 0;
            }
            
            .profile-container {
                width: 95%;
                margin: 20px auto;
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 10px auto;
                padding: 10px;
                border-radius: 15px;
            }

            .user-info-line {
                flex-direction: column;
                gap: 15px;
            }

            .user-role, .user-email, .user-since {
                width: 100%;
                justify-content: center;
            }

            .profile-info h2 {
                font-size: 24px;
            }

            .btn-update {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .profile-container {
                width: 100%;
                margin: 0;
                border-radius: 0;
            }

            .photo-container {
                width: 100px;
                height: 100px;
            }

            .photo-buttons {
                width: 90%;
            }

            .form-group label {
                font-size: 14px;
            }

            .user-role, .user-email, .user-since {
                font-size: 14px;
            }

            .edit-form h3 {
                font-size: 18px;
            }
        }

        @media (max-height: 800px) {
            .photo-container {
                width: 100px;
                height: 100px;
            }
            
            .profile-info h2 {
                font-size: 24px;
                margin-bottom: 5px;
            }
            
            .user-info-line {
                gap: 15px;
            }
            
            .user-role, .user-email, .user-since {
                padding: 5px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>

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

        <div class="profile-header">
            <div class="photo-section">
                <div class="photo-container" onclick="openPhotoZoom()">
                    <img src="<?= !empty($admin['photo']) ? '../../' . $admin['photo'] : '../../' . $default_image ?>" alt="Photo de Profil" id="previewImage">
                    <div class="zoom-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                    <div class="shine-effect"></div>
                </div>
                <div class="photo-buttons">
                    <button type="button" class="btn btn-primary btn-sm photo-btn" onclick="document.getElementById('photoInput').click();">
                        <i class="fas fa-camera"></i> Modifier la photo
                    </button>
                    <?php if (!empty($admin['photo'])): ?>
                    <button type="button" class="btn btn-danger btn-sm photo-btn" id="deletePhotoBtn">
                        <i class="fas fa-trash"></i> Supprimer la photo
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-info">
                <div class="user-header">
                    <h2><?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?></h2>
                    <div class="user-info-line">
                        <div class="user-role">
                            <i class="fas fa-user-tie"></i>
                            <?= htmlspecialchars($role) ?>
                        </div>
                        <div class="user-email">
                            <i class="fas fa-envelope"></i>
                            <?= htmlspecialchars($admin['email']) ?>
                        </div>
                        <div class="user-since">
                            <i class="fas fa-calendar-alt"></i>
                            Membre depuis: <?= (new DateTime($admin['date_creation']))->format('d/m/Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-form">
            <h3><i class="fas fa-edit"></i> Modifier le Profil</h3>
            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($admin['prenom']) ?>" required autocomplete="given-name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($admin['nom']) ?>" required autocomplete="family-name">
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" <?= $canEditEmail ? '' : 'readonly' ?> autocomplete="email">
                    <?php if (!$canEditEmail): ?>
                        <small class="text-muted fst-italic">Seul le chef administrateur peut modifier l'email.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group mt-3">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
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
    
    <!-- Modal de confirmation de changement de photo -->
    <div class="modal fade" id="photoConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer le changement de photo</h5>
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
    
    <!-- Modal de confirmation de suppression de photo -->
    <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
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
                    <button type="button" class="btn btn-danger" id="confirmDeletePhoto">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de zoom photo -->
    <div class="modal fade" id="photoZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photo de profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="zoomedImage" src="" alt="Photo de profil en grand format" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Définition des variables
        const photoInput = document.getElementById('photoInput');
        const previewImage = document.getElementById('previewImage');
        const profileForm = document.getElementById('profileForm');
        const deletePhotoBtn = document.getElementById('deletePhotoBtn');
        const confirmPhotoChangeBtn = document.getElementById('confirmPhotoChange');
        const confirmDeletePhotoBtn = document.getElementById('confirmDeletePhoto');
        const photoContainer = document.querySelector('.photo-container');
        
        // Modals
        const photoConfirmModal = new bootstrap.Modal(document.getElementById('photoConfirmModal'));
        const deletePhotoModal = new bootstrap.Modal(document.getElementById('deletePhotoModal'));
        const photoZoomModal = new bootstrap.Modal(document.getElementById('photoZoomModal'));
        
        // Fonction pour ouvrir le modal de zoom
        function openPhotoZoom() {
            const previewImage = document.getElementById('previewImage');
            const zoomedImage = document.getElementById('zoomedImage');
            
            // Assurer que l'image source est correcte
            let imgSrc = previewImage.src;
            
            // Si le chemin ne contient pas déjà uploads/admin_photos et ce n'est pas un data URL
            if (!imgSrc.includes('uploads/admin_photos/') && !imgSrc.startsWith('data:')) {
                // Extraire le nom du fichier de l'URL
                const fileName = imgSrc.split('/').pop();
                // Reconstruire l'URL avec le chemin correct
                imgSrc = '../../uploads/admin_photos/' + fileName;
            }
            
            zoomedImage.src = imgSrc;
            photoZoomModal.show();
        }
        
        // Ajouter un événement de clic sur la photo pour le zoom
        if (photoContainer) {
            photoContainer.style.cursor = 'pointer';
            photoContainer.addEventListener('click', openPhotoZoom);
        }
        
        // Gestion du changement de photo
        photoInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Vérification de la taille et du format
                if (file.size > 5 * 1024 * 1024) {
                    alert('La taille du fichier doit être inférieure à 5MB.');
                    this.value = '';
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                    this.value = '';
                    return;
                }
                
                // Prévisualisation de l'image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('modalPreviewImage').src = e.target.result;
                    photoConfirmModal.show();
                }
                reader.readAsDataURL(file);
            }
        });

        // Confirmation du changement de photo
        confirmPhotoChangeBtn.addEventListener('click', function() {
            photoConfirmModal.hide();
            profileForm.submit();
        });

        // Ouverture du modal de suppression de photo
        if (deletePhotoBtn) {
            deletePhotoBtn.addEventListener('click', function() {
                deletePhotoModal.show();
            });
        }

        // Suppression de la photo
        confirmDeletePhotoBtn.addEventListener('click', function() {
            fetch('delete_photo_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ admin_id: <?php echo json_encode($admin_id); ?> })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Réponse serveur:', text);
                    throw new Error('Réponse serveur invalide');
                }
            })
            .then(data => {
                if (data.success) {
                    previewImage.src = '../../<?= $default_image ?>';
                    deletePhotoModal.hide();
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Erreur lors de la suppression');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression de la photo: ' + error.message);
            });
        });

        // Réinitialisation du champ fichier si annulation
        document.getElementById('photoConfirmModal').addEventListener('hidden.bs.modal', function() {
            photoInput.value = '';
        });

        // Suppression automatique des alertes de succès après 2 secondes
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => {
                    alert.remove();
                }, 150);
            }, 2000);
        });

        // Validation du formulaire avant soumission
        profileForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
            }
            
            if (newPassword && newPassword.length < 8) {
                e.preventDefault();
                alert('Le nouveau mot de passe doit contenir au moins 8 caractères.');
            }
        });
    });
    </script>
</body>
</html>

   