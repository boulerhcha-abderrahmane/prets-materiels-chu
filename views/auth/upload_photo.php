<?php
function uploadPhoto($photo) {
    $target_dir = "../../uploads/user_photos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $fileExtension = strtolower(pathinfo($photo["name"], PATHINFO_EXTENSION));
    $newFileName = uniqid() . '.' . $fileExtension;
    $target_file = $target_dir . $newFileName;

    // Vérifier le type de fichier
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedTypes)) {
        return [
            'success' => false, 
            'message' => 'Seuls les fichiers JPG, JPEG, PNG & GIF sont autorisés.'
        ];
    }

    // Vérifier la taille du fichier (5MB max)
    if ($photo["size"] > 5000000) {
        return [
            'success' => false, 
            'message' => 'Le fichier est trop volumineux (max 5MB).'
        ];
    }

    // Vérifier si c'est une vraie image
    if (!getimagesize($photo["tmp_name"])) {
        return [
            'success' => false, 
            'message' => 'Le fichier doit être une image.'
        ];
    }

    if (move_uploaded_file($photo["tmp_name"], $target_file)) {
        // Important: retourner le chemin à stocker dans la BDD (sans le ../../)
        $db_path = 'uploads/user_photos/' . $newFileName;
        return [
            'success' => true, 
            'filename' => $newFileName, 
            'path' => $db_path
        ];
    } else {
        return [
            'success' => false, 
            'message' => 'Erreur lors du téléchargement du fichier.'
        ];
    }
}
?> 