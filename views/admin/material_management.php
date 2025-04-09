<?php
// Inclure le fichier de configuration
include '../../config/config.php';
session_start();

// Ajouter un matériel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    try {
        $pdo->beginTransaction();
        
        $materialName = $_POST['material_name'];
        $materialType = $_POST['material_type'];
        $materialLocation = $_POST['material_location'];
        $materialDescription = $_POST['material_description'];
        $materialQuantity = $_POST['material_quantity'];

        // Vérifier d'abord si une photo existante a été sélectionnée
        if (isset($_POST['existing_photo']) && !empty($_POST['existing_photo'])) {
            $materialPhoto = $_POST['existing_photo'];
        }
        // Sinon, traiter le nouvel upload de photo
        elseif (isset($_FILES['material_photo']) && $_FILES['material_photo']['error'] == 0) {
            $materialPhoto = $_FILES['material_photo']['name'];
            $targetDir = "uploads/";

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetFile = $targetDir . basename($materialPhoto);
            if (move_uploaded_file($_FILES['material_photo']['tmp_name'], $targetFile)) {
                $materialPhoto = $targetFile;
            } else {
                echo "Erreur lors du téléchargement de la photo.";
            }
        } else {
            $materialPhoto = null;
        }

        $sql = "INSERT INTO materiel (nom, type, emplacement, description, quantite_disponible, photo) VALUES (:name, :type, :location, :description, :quantity, :photo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $materialName,
            'type' => $materialType,
            'location' => $materialLocation,
            'description' => $materialDescription,
            'quantity' => $materialQuantity,
            'photo' => $materialPhoto
        ]);

        $material_id = $pdo->lastInsertId();

        // Enregistrer dans l'historique
        $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, date_action, details) 
                          VALUES (:id_admin, :type_action, NOW(), :details)";
        $stmt_historique = $pdo->prepare($sql_historique);
        $stmt_historique->execute([
            'id_admin' => $_SESSION['admin_id'],
            'type_action' => 'AJOUT_MATERIEL',
            'details' => "Ajout du matériel : " . $materialName
        ]);

        $pdo->commit();
        $_SESSION['success_message'] = "Le matériel \"" . $materialName . "\" a été ajouté avec succès.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur lors de l'ajout du matériel : " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Modifier un matériel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    try {
        $pdo->beginTransaction();

        $materialId = $_POST['material_id'];
        $materialName = $_POST['material_name'];
        $materialType = $_POST['material_type'];
        $materialLocation = $_POST['material_location'];
        $materialDescription = $_POST['material_description'];
        $materialQuantity = $_POST['material_quantity'];

        // Récupérer les anciennes valeurs avant modification
        $stmt = $pdo->prepare("SELECT nom, type, emplacement, description, quantite_disponible, photo FROM materiel WHERE id_materiel = :id");
        $stmt->execute(['id' => $materialId]);
        $oldMaterial = $stmt->fetch(PDO::FETCH_ASSOC);

        // Gérer la mise à jour de la photo
        if (isset($_FILES['material_photo']) && $_FILES['material_photo']['error'] == 0) {
            // Si une nouvelle photo est téléchargée
            $materialPhoto = $_FILES['material_photo']['name'];
            $targetDir = "uploads/";
            $targetFile = $targetDir . basename($materialPhoto);
            move_uploaded_file($_FILES['material_photo']['tmp_name'], $targetFile);
            $materialPhoto = $targetFile;
        } elseif (isset($_POST['existing_photo']) && !empty($_POST['existing_photo'])) {
            // Si une photo existante est sélectionnée
            $materialPhoto = $_POST['existing_photo'];
        } else {
            // Garder l'ancienne photo
            $materialPhoto = $oldMaterial['photo'];
        }

        // Mettre à jour le matériel
        $sql = "UPDATE materiel SET nom = :name, type = :type, emplacement = :location, description = :description, quantite_disponible = :quantity, photo = :photo WHERE id_materiel = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $materialName,
            'type' => $materialType,
            'location' => $materialLocation,
            'description' => $materialDescription,
            'quantity' => $materialQuantity,
            'photo' => $materialPhoto,
            'id' => $materialId
        ]);

        // Préparer les détails des modifications
        $changes = [];
        if ($oldMaterial['nom'] !== $materialName) {
            $changes[] = sprintf("Nom: %s → %s", $oldMaterial['nom'], $materialName);
        }
        if ($oldMaterial['type'] !== $materialType) {
            $changes[] = sprintf("Type: %s → %s", $oldMaterial['type'], $materialType);
        }
        if ($oldMaterial['emplacement'] !== $materialLocation) {
            $changes[] = sprintf("Emplacement: %s → %s", $oldMaterial['emplacement'], $materialLocation);
        }
        if ($oldMaterial['description'] !== $materialDescription) {
            $changes[] = sprintf("Description: %s → %s", $oldMaterial['description'], $materialDescription);
        }
        if ($oldMaterial['quantite_disponible'] !== $materialQuantity) {
            $changes[] = sprintf("Quantité: %s → %s", $oldMaterial['quantite_disponible'], $materialQuantity);
        }
        if ($oldMaterial['photo'] !== $materialPhoto) {
            $changes[] = "Photo modifiée";
        }

        $details = "Modification du matériel " . $materialName . " - Changements : " . implode(", ", $changes);

        // Enregistrer dans l'historique
        $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, date_action, details) 
                          VALUES (:id_admin, :type_action, NOW(), :details)";
        $stmt_historique = $pdo->prepare($sql_historique);
        $stmt_historique->execute([
            'id_admin' => $_SESSION['admin_id'],
            'type_action' => 'MODIFICATION_MATERIEL',
            'details' => $details
        ]);

        $pdo->commit();
        $_SESSION['success_message'] = "Le matériel \"" . $materialName . "\" a été modifié avec succès.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur lors de la modification du matériel : " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Supprimer un matériel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    try {
        $pdo->beginTransaction();

        $materialId = $_POST['material_id'];
        
        // Récupérer toutes les informations du matériel avant la suppression
        $stmt = $pdo->prepare("SELECT nom, type, emplacement, description, quantite_disponible FROM materiel WHERE id_materiel = :id");
        $stmt->execute(['id' => $materialId]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Préparer les détails complets
        $details = sprintf(
            "Suppression du matériel - Nom: %s, Type: %s, Emplacement: %s, Description: %s, Quantité: %s",
            $material['nom'],
            $material['type'],
            $material['emplacement'],
            $material['description'],
            $material['quantite_disponible']
        );
        
        // Ensuite, supprimer le matériel
        $sql = "DELETE FROM materiel WHERE id_materiel = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $materialId]);

        // Créer une nouvelle entrée dans l'historique avec les détails complets
        $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, date_action, details) 
                          VALUES (:id_admin, :type_action, NOW(), :details)";
        $stmt_historique = $pdo->prepare($sql_historique);
        $stmt_historique->execute([
            'id_admin' => $_SESSION['admin_id'],
            'type_action' => 'SUPPRESSION_MATERIEL',
            'details' => $details
        ]);

        $pdo->commit();
        $_SESSION['success_message'] = "Le matériel \"" . $material['nom'] . "\" a été supprimé avec succès.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur lors de la suppression du matériel : " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Ajouter le traitement pour l'ajout de photos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_photos'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $uploadedFiles = $_FILES['photos'];
    $fileCount = count($uploadedFiles['name']);
    $uploadedCount = 0;
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($uploadedFiles['error'][$i] == 0) {
            $fileName = basename($uploadedFiles['name'][$i]);
            $targetFile = $targetDir . $fileName;
            
            // Vérifier si c'est bien une image
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($uploadedFiles['tmp_name'][$i], $targetFile)) {
                    $uploadedCount++;
                }
            }
        }
    }
    
    if ($uploadedCount > 0) {
        $_SESSION['success_message'] = "$uploadedCount photo(s) ajoutée(s) avec succès.";
    } else {
        $_SESSION['error_message'] = "Aucune photo n'a été ajoutée.";
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Ajouter le traitement pour renommer les photos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rename_photo'])) {
    $oldPath = $_POST['old_path'];
    $newName = trim($_POST['new_name']); // Utiliser trim() pour enlever les espaces
    
    if (empty($newName)) {
        $_SESSION['error_message'] = "Le nouveau nom ne peut pas être vide.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (file_exists($oldPath)) {
        $pathInfo = pathinfo($oldPath);
        $oldName = $pathInfo['filename']; // Obtenir l'ancien nom sans extension
        
        // Vérifier si le nouveau nom est différent de l'ancien
        if ($oldName !== $newName) {
            // Construire le nouveau chemin
            $newPath = $pathInfo['dirname'] . '/' . $newName . '.' . $pathInfo['extension'];
            
            // Vérifier si un fichier avec le nouveau nom existe déjà
            if (file_exists($newPath)) {
                $_SESSION['error_message'] = "Ce nom de fichier existe déjà. Veuillez en choisir un autre.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            if (rename($oldPath, $newPath)) {
                // Mettre à jour tous les enregistrements dans la base de données qui utilisent cette photo
                $stmt = $pdo->prepare("UPDATE materiel SET photo = :newPath WHERE photo = :oldPath");
                $stmt->execute([
                    'newPath' => $newPath,
                    'oldPath' => $oldPath
                ]);
                
                $_SESSION['success_message'] = "Le fichier " . basename($oldPath) . " a été renommé en " . basename($newPath) . " avec succès.";
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error_message'] = "Erreur lors du renommage de la photo.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $_SESSION['warning_message'] = "Le nouveau nom doit être différent de l'ancien.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Le fichier n'existe pas : " . htmlspecialchars($oldPath);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Ajouter le traitement pour la suppression multiple
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_multiple'])) {
    try {
        $pdo->beginTransaction();
        $selectedMaterials = $_POST['selected_materials'] ?? [];
        $deletedCount = 0;
        $materialNames = [];
        
        foreach ($selectedMaterials as $materialId) {
            // Récupérer le nom du matériel avant la suppression
            $stmt = $pdo->prepare("SELECT nom, photo FROM materiel WHERE id_materiel = :id");
            $stmt->execute(['id' => $materialId]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($material) {
                $materialNames[] = $material['nom'];
                
                // Supprimer le matériel de la base de données
                $sql = "DELETE FROM materiel WHERE id_materiel = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $materialId]);
                $deletedCount++;
                
                // Créer une entrée dans l'historique des actions
                $details = "Suppression du matériel - Nom: " . $material['nom'];
                $sql_historique = "INSERT INTO historique_actions (id_admin, type_action, date_action, details) 
                                  VALUES (:id_admin, :type_action, NOW(), :details)";
                $stmt_historique = $pdo->prepare($sql_historique);
                $stmt_historique->execute([
                    'id_admin' => $_SESSION['admin_id'],
                    'type_action' => 'SUPPRESSION_MATERIEL',
                    'details' => $details
                ]);
            }
        }
        
        $pdo->commit();
        
        if ($deletedCount > 0) {
            if ($deletedCount == 1) {
                $_SESSION['success_message'] = "Le matériel \"" . $materialNames[0] . "\" a été supprimé avec succès.";
            } else {
                $_SESSION['success_message'] = "$deletedCount matériels ont été supprimés avec succès.";
            }
        } else {
            $_SESSION['error_message'] = "Aucun matériel n'a été supprimé.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur lors de la suppression multiple : " . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Ajouter le traitement pour la suppression multiple des photos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_selected_photos'])) {
    $selectedPhotos = $_POST['selected_photos'] ?? [];
    $deletedCount = 0;
    
    foreach ($selectedPhotos as $photoPath) {
        // Vérifier si la photo existe
        if (!file_exists($photoPath)) {
            continue;
        }
        
        // Mettre à jour la base de données pour retirer la référence à la photo
        $stmt = $pdo->prepare("UPDATE materiel SET photo = NULL WHERE photo = :photoPath");
        $stmt->execute(['photoPath' => $photoPath]);
        
        // Supprimer le fichier physique
        if (unlink($photoPath)) {
            $deletedCount++;
        }
    }
    
    if ($deletedCount > 0) {
        $_SESSION['success_message'] = "$deletedCount photo(s) supprimée(s) avec succès.";
    } else {
        $_SESSION['error_message'] = "Erreur lors de la suppression des photos.";
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Récupérer tous les matériaux
$sql = "SELECT * FROM materiel";
$stmt = $pdo->query($sql);
$materials = $stmt->fetchAll();

// Récupérer toutes les photos existantes
$existingPhotos = glob("uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE); // Récupérer toutes les photos dans le dossier uploads
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matériels</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1 class="text-center mb-4">Gestion des Matériels</h1>

            <!-- Affichage des messages de notification -->
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning_message'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $_SESSION['warning_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['warning_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Barre de recherche -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group w-100">
                        <label for="searchInput" class="sr-only">Rechercher un matériel</label>
                        <input type="text" 
                               class="form-control" 
                               id="searchInput" 
                               placeholder="Rechercher un matériel..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="clearSearch" aria-label="Effacer la recherche">
                                <i class="fas fa-times"></i> Effacer
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addMaterialModal">
                        <i class="fas fa-plus"></i> Ajouter un Matériel
                    </button>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#managePhotosModal">
                        <i class="fas fa-images"></i> Gérer les Photos
                    </button>
                </div>
            </div>

            <!-- Modal pour gérer les photos -->
            <div class="modal fade" id="managePhotosModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Gestion des Photos</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeManagePhotosModal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Formulaire d'ajout de photos -->
                            <form method="POST" enctype="multipart/form-data" class="mb-4">
                                <div class="form-group">
                                    <label for="photos_input">Ajouter des photos</label>
                                    <input type="file" class="form-control-file" id="photos_input" name="photos[]" multiple accept="image/*" required>
                                </div>
                                <button type="submit" name="add_photos" class="btn btn-primary">Ajouter les photos</button>
                            </form>

                            <hr>

                            <!-- Liste des photos existantes -->
                            <form method="POST" id="photosForm">
                                <div class="mb-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="select_all_photos">
                                        <label class="custom-control-label" for="select_all_photos">Sélectionner toutes les photos</label>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <?php foreach ($existingPhotos as $photo): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="<?php echo $photo; ?>" class="card-img-top" alt="Photo" style="height: 200px; object-fit: cover;">
                                            <div class="card-body text-center">
                                                <p class="mb-1">Nom actuel : <?php echo pathinfo($photo, PATHINFO_FILENAME); ?></p>
                                                <form method="POST" class="mb-2">
                                                    <input type="hidden" name="old_path" value="<?php echo $photo; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <?php $newNameId = "new_name_" . md5($photo); ?>
                                                        <label for="<?php echo $newNameId; ?>" class="sr-only">Nouveau nom pour <?php echo basename($photo); ?></label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="<?php echo $newNameId; ?>"
                                                               name="new_name" 
                                                               placeholder="Nouveau nom"
                                                               value="<?php echo pathinfo($photo, PATHINFO_FILENAME); ?>">
                                                                
                                                        <div class="input-group-append">
                                                            <button type="submit" 
                                                                    id="rename_btn_<?php echo md5($photo); ?>"
                                                                    name="rename_photo" 
                                                                    class="btn btn-outline-secondary btn-sm"
                                                                    aria-label="Renommer la photo">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" 
                                                           class="custom-control-input photo-checkbox" 
                                                           id="photo_<?php echo md5($photo); ?>" 
                                                           name="selected_photos[]" 
                                                           value="<?php echo $photo; ?>">
                                                    <label class="custom-control-label" for="photo_<?php echo md5($photo); ?>">Sélectionner</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="closePhotosModalBtn" data-dismiss="modal">Fermer</button>
                            <button type="button" class="btn btn-danger" id="deletePhotosBtn" style="display: none;">
                                <i class="fas fa-trash"></i> Supprimer la sélection
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de confirmation pour la suppression des photos -->
            <div class="modal fade" id="confirmDeletePhotosModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmer la suppression</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeConfirmPhotoModal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Êtes-vous sûr de vouloir supprimer <span id="photoCount">0</span> photo(s) ?</p>
                            <p class="text-danger"><strong>Attention:</strong> Cette action est irréversible et supprimera également les références à ces photos dans les matériels.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                            <button type="submit" form="photosForm" name="delete_selected_photos" class="btn btn-danger" id="confirmDeletePhotosBtn">
                                Confirmer la suppression
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de confirmation pour la suppression multiple de matériels -->
            <div class="modal fade" id="confirmDeleteMaterialsModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmer la suppression</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeConfirmMaterialModal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Êtes-vous sûr de vouloir supprimer <span id="materialCount">0</span> matériel(s) ?</p>
                            <p class="text-danger"><strong>Attention:</strong> Cette action est irréversible.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                            <button type="submit" form="materialsForm" name="delete_multiple" class="btn btn-danger" id="confirmDeleteMaterialsBtn">
                                Confirmer la suppression
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal pour ajouter un matériel -->
            <div class="modal fade" id="addMaterialModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
        
                            <h5 class="modal-title">Ajouter un Matériel</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeAddMaterialModal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="material_name_add">Nom du Matériel</label>
                                    <input type="text" class="form-control form-control-sm" id="material_name_add" name="material_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="material_type_add">Type</label>
                                    <select class="form-control form-control-sm" id="material_type_add" name="material_type" required>
                                        <option value="consommable">Consommable</option>
                                        <option value="non-consommable">Non-consommable</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="material_location_add">Emplacement</label>
                                    <input type="text" class="form-control form-control-sm" id="material_location_add" name="material_location" required>
                                </div>
                                <div class="form-group">
                                    <label for="material_description_add">Description</label>
                                    <textarea class="form-control form-control-sm" id="material_description_add" name="material_description" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="material_quantity_add">Quantité Disponible</label>
                                    <input type="number" class="form-control form-control-sm" id="material_quantity_add" name="material_quantity" required>
                                </div>
                                <div class="form-group">
                                    <label for="material_photo_add">Photo</label>
                                    <input type="file" class="form-control form-control-sm" id="material_photo_add" name="material_photo" accept="image/*">
                                </div>

                                <fieldset class="form-group">
                                    <legend class="col-form-label">Photo existante</legend>
                                    <div class="row">
                                        <?php foreach ($existingPhotos as $photo): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="card">
                                                    <img src="<?php echo $photo; ?>" class="card-img-top" alt="<?php echo basename($photo); ?>" style="height: 100px; object-fit: cover;">
                                                    <div class="card-body p-2">
                                                        <div class="custom-control custom-radio">
                                                            <?php $photoAddId = "photo_add_" . md5($photo); ?>
                                                            <input type="radio" 
                                                                   class="custom-control-input" 
                                                                   id="<?php echo $photoAddId; ?>" 
                                                                   name="existing_photo" 
                                                                   value="<?php echo $photo; ?>">
                                                            <label class="custom-control-label small" for="<?php echo $photoAddId; ?>">
                                                                <?php echo basename($photo); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>

                                
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" id="closeAddModalBtn" data-dismiss="modal">Fermer</button>
                                <button type="submit" name="add" class="btn btn-success btn-sm" id="addMaterialBtn">Ajouter Matériel</button>
                                 
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Liste des matériaux -->
            <h2 class="mb-4">Liste des Matériaux</h2>
            <form method="POST" id="materialsForm">
                <div class="mb-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="select_all">
                        <label class="custom-control-label" for="select_all">Sélectionner tout</label>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm mt-2" id="deleteSelected" style="display: none;">
                        <i class="fas fa-trash"></i> Supprimer la sélection
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th width="50px">
                                    <i class="fas fa-check-square"></i>
                                </th>
                                <th>Photo</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Emplacement</th>
                                <th>Description</th>
                                <th>Quantité Disponible</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $row): ?>
                                <tr>
                                    <td>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input material-checkbox" 
                                                   id="material_<?php echo $row['id_materiel']; ?>" 
                                                   name="selected_materials[]" 
                                                   value="<?php echo $row['id_materiel']; ?>">
                                            <label class="custom-control-label" for="material_<?php echo $row['id_materiel']; ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                         
                                            <img src="<?php echo (isset($row['photo']) && !empty($row['photo']) && file_exists($row['photo'])) ? $row['photo'] : '../../assets/images/image.png'; ?>" alt="Photo" style="height: 50px; width: 50px; object-fit: cover;">
                                    
                                    </td>
                                    <td><?php echo $row['nom']; ?></td>
                                    <td><?php echo $row['type']; ?></td>
                                    <td><?php echo $row['emplacement']; ?></td>
                                    <td><?php echo $row['description']; ?></td>
                                    <td><?php echo $row['quantite_disponible']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateModal<?php echo $row['id_materiel']; ?>">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal<?php echo $row['id_materiel']; ?>">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Modals pour chaque matériel -->
            <?php foreach ($materials as $row): ?>
                <!-- Modal de modification -->
                <div class="modal fade" id="updateModal<?php echo $row['id_materiel']; ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Modifier Matériel</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeUpdateModalX<?php echo $row['id_materiel']; ?>">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" id="material_id_<?php echo $row['id_materiel']; ?>" name="material_id" value="<?php echo $row['id_materiel']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="material_name_<?php echo $row['id_materiel']; ?>">Nom du Matériel</label>
                                        <input type="text" 
                                               class="form-control form-control-sm" 
                                               id="material_name_<?php echo $row['id_materiel']; ?>" 
                                               name="material_name" 
                                               value="<?php echo htmlspecialchars($row['nom']); ?>" 
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label for="material_type_<?php echo $row['id_materiel']; ?>">Type</label>
                                        <select class="form-control form-control-sm" 
                                                id="material_type_<?php echo $row['id_materiel']; ?>" 
                                                name="material_type" 
                                                required>
                                            <option value="consommable" <?php echo $row['type'] == 'consommable' ? 'selected' : ''; ?>>Consommable</option>
                                            <option value="non-consommable" <?php echo $row['type'] == 'non-consommable' ? 'selected' : ''; ?>>Non-consommable</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="material_location_<?php echo $row['id_materiel']; ?>">Emplacement</label>
                                        <input type="text" 
                                               class="form-control form-control-sm" 
                                               id="material_location_<?php echo $row['id_materiel']; ?>" 
                                               name="material_location" 
                                               value="<?php echo htmlspecialchars($row['emplacement']); ?>" 
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label for="material_description_<?php echo $row['id_materiel']; ?>">Description</label>
                                        <textarea class="form-control form-control-sm" 
                                                  id="material_description_<?php echo $row['id_materiel']; ?>" 
                                                  name="material_description" 
                                                  rows="2"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="material_quantity_<?php echo $row['id_materiel']; ?>">Quantité Disponible</label>
                                        <input type="number" 
                                               class="form-control form-control-sm" 
                                               id="material_quantity_<?php echo $row['id_materiel']; ?>" 
                                               name="material_quantity" 
                                               value="<?php echo $row['quantite_disponible']; ?>" 
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label for="material_photo_<?php echo $row['id_materiel']; ?>">Photo</label>
                                        <input type="file" 
                                               class="form-control-file" 
                                               id="material_photo_<?php echo $row['id_materiel']; ?>" 
                                               name="material_photo" 
                                               accept="image/*">
                                    </div>

                                    <fieldset class="form-group">
                                        <legend class="col-form-label">Photo existante</legend>
                                        <div class="row">
                                            <?php foreach ($existingPhotos as $photo): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="card">
                                                        <img src="<?php echo $photo; ?>" class="card-img-top" alt="<?php echo basename($photo); ?>" style="height: 100px; object-fit: cover;">
                                                        <div class="card-body p-2">
                                                            <div class="custom-control custom-radio">
                                                                <?php $photoId = "photo_" . $row['id_materiel'] . "_" . md5($photo); ?>
                                                                <input type="radio" 
                                                                       class="custom-control-input" 
                                                                       id="<?php echo $photoId; ?>" 
                                                                       name="existing_photo" 
                                                                       value="<?php echo $photo; ?>"
                                                                       <?php echo $photo == $row['photo'] ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label small" for="<?php echo $photoId; ?>">
                                                                    <?php echo basename($photo); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="closeUpdateModalBtn<?php echo $row['id_materiel']; ?>" data-dismiss="modal">Fermer</button>
                                    <button type="submit" name="update" class="btn btn-primary" id="updateMaterialBtn<?php echo $row['id_materiel']; ?>">Mettre à jour</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal de suppression -->
                <div class="modal fade" id="deleteModal<?php echo $row['id_materiel']; ?>" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmer la suppression</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeDeleteModal<?php echo $row['id_materiel']; ?>">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Êtes-vous sûr de vouloir supprimer le matériel "<?php echo $row['nom']; ?>" ?</p>
                            </div>
                            <div class="modal-footer">
                                <form method="POST">
                                    <input type="hidden" id="delete_material_id_<?php echo $row['id_materiel']; ?>" name="material_id" value="<?php echo $row['id_materiel']; ?>">
                                    <button type="button" class="btn btn-secondary btn-sm" id="cancelDeleteBtn<?php echo $row['id_materiel']; ?>" data-dismiss="modal">Annuler</button>
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" id="confirmDeleteBtn<?php echo $row['id_materiel']; ?>">Confirmer la suppression</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modifier l'ordre et les versions des scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('select_all');
            const materialCheckboxes = document.querySelectorAll('.material-checkbox');
            const deleteSelectedButton = document.getElementById('deleteSelected');

            // Gérer la sélection/désélection de tous les matériels
            selectAllCheckbox.addEventListener('change', function() {
                materialCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonVisibility();
            });

            // Gérer la visibilité du bouton de suppression
            materialCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButtonVisibility();
                    // Mettre à jour la case "Sélectionner tout"
                    selectAllCheckbox.checked = Array.from(materialCheckboxes).every(cb => cb.checked);
                });
            });

            function updateDeleteButtonVisibility() {
                const hasChecked = Array.from(materialCheckboxes).some(cb => cb.checked);
                deleteSelectedButton.style.display = hasChecked ? 'inline-block' : 'none';
            }

            // Confirmation avant suppression multiple
            document.getElementById('materialsForm').addEventListener('submit', function(e) {
                if (e.submitter && e.submitter.name === 'delete_multiple' && 
                    e.submitter.id !== 'confirmDeleteMaterialsBtn') {
                    e.preventDefault();
                    const checkedCount = document.querySelectorAll('.material-checkbox:checked').length;
                    document.getElementById('materialCount').textContent = checkedCount;
                    $('#confirmDeleteMaterialsModal').modal('show');
                }
            });

            // Ouvrir le modal de confirmation pour la suppression multiple de matériels
            deleteSelectedButton.addEventListener('click', function() {
                const checkedCount = document.querySelectorAll('.material-checkbox:checked').length;
                document.getElementById('materialCount').textContent = checkedCount;
                $('#confirmDeleteMaterialsModal').modal('show');
            });

            const searchInput = document.getElementById('searchInput');
            const clearSearch = document.getElementById('clearSearch');
            const tableRows = document.querySelectorAll('table tbody tr');

            // Fonction de recherche
            function filterTable(searchTerm) {
                searchTerm = searchTerm.toLowerCase();
                
                tableRows.forEach(row => {
                    const name = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    if (name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Événement de saisie dans la barre de recherche
            searchInput.addEventListener('input', function() {
                filterTable(this.value);
            });

            // Bouton pour effacer la recherche
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                filterTable('');
            });

            const selectAllPhotos = document.getElementById('select_all_photos');
            const photoCheckboxes = document.querySelectorAll('.photo-checkbox');
            const deletePhotosBtn = document.getElementById('deletePhotosBtn');
            const photoCountElement = document.getElementById('photoCount');

            // Gérer la sélection/désélection de toutes les photos
            selectAllPhotos.addEventListener('change', function() {
                photoCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeletePhotosButtonVisibility();
            });

            // Gérer la visibilité du bouton de suppression
            photoCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeletePhotosButtonVisibility();
                    selectAllPhotos.checked = Array.from(photoCheckboxes).every(cb => cb.checked);
                });
            });

            function updateDeletePhotosButtonVisibility() {
                const checkedPhotos = Array.from(photoCheckboxes).filter(cb => cb.checked);
                const hasChecked = checkedPhotos.length > 0;
                deletePhotosBtn.style.display = hasChecked ? 'inline-block' : 'none';
            }

            // Ouvrir le modal de confirmation pour la suppression des photos
            deletePhotosBtn.addEventListener('click', function() {
                const checkedCount = document.querySelectorAll('.photo-checkbox:checked').length;
                photoCountElement.textContent = checkedCount;
                $('#confirmDeletePhotosModal').modal('show');
            });

            // Auto-dismissible alerts
            const notificationAlerts = document.querySelectorAll('.alert-success, .alert-warning, .alert-danger');
            notificationAlerts.forEach(function(alert) {
                setTimeout(function() {
                    $(alert).fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000); // Disparaît après 5 secondes
            });
        });
    </script>
</body>
</html>