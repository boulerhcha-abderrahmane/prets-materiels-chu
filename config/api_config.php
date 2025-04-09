<?php
require_once 'EmailServices.php';

// Configuration de la base de données
$host = 'localhost';
$dbname = 'test6';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit;
}

// Initialisation du service d'email
$emailService = new EmailService();

// Configuration des API
define('RESEND_API_KEY', 're_BtCXgwta_AphCeiYV8M6cGxT1qsqqeYgp');
define('RESEND_FROM_EMAIL', 'Gestion des Prêts <onboarding@resend.dev>');
define('WEBSOCKET_URL', 'ws://localhost:8080');
