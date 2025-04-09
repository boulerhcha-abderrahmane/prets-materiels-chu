<?php
require_once '../../config/config.php';

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM demande_pret WHERE statut IN ('valide', 'valide en attente retour')");
$stmt->execute();
$valid_requests_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM demande_pret WHERE statut = 'en_attente'");
$pending_requests_count = $stmt->fetchColumn();

echo json_encode([
    'valid_requests' => $valid_requests_count,
    'pending_requests' => $pending_requests_count
]);
?> 