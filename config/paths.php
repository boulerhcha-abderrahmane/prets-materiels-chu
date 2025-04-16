<?php
// Définir le chemin de base de l'application
$base_url = '';

// Déterminer automatiquement le chemin de base si $_SERVER est disponible
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];
    
    // Détermine le répertoire racine du projet
    $root_dir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Si le root_dir n'est pas /, ajouter au base_url
    if ($root_dir !== '/' && $root_dir !== '\\') {
        // Normaliser les slashes
        $root_dir = str_replace('\\', '/', $root_dir);
        // S'assurer qu'il y a un slash à la fin
        if (substr($root_dir, -1) !== '/') {
            $root_dir .= '/';
        }
        $base_url .= $root_dir;
    } else {
        $base_url .= '/';
    }
}

// Pour nettoyer les doubles slashes éventuels
$base_url = rtrim($base_url, '/') . '/';

// Définir l'URL des assets
$assets_url = $base_url . 'assets/';
$css_url = $assets_url . 'css/';
$js_url = $assets_url . 'js/';
$images_url = $assets_url . 'images/';
?> 