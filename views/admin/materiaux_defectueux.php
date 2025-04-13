<?php
require_once '../../config/config.php';
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matériaux Défectueux</title>
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
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
        }

        .table th {
            background-color: #2c3e50;
            color: white;
        }

        .status-defectueux {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <header class="mb-4">
                <h1><i class="fas fa-exclamation-triangle text-warning me-2"></i>Liste des Matériaux Défectueux</h1>
            </header>

            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date du Retour</th>
                                <th>Nom du Matériel</th>
                                <th>Type</th>
                                <th>Quantité</th>
                                <th>Utilisateur</th>
                                <th>Admin Validateur</th>
                                <th>État</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT DISTINCT
                                        rp.date_retour,
                                        m.nom AS nom_materiel,
                                        m.type,
                                        d.quantite,
                                        u.nom AS nom_utilisateur,
                                        u.prenom AS prenom_utilisateur,
                                        a.nom AS nom_admin,
                                        a.prenom AS prenom_admin,
                                        rp.etat_retour,
                                        rp.commentaire
                                    FROM retour_pret rp
                                    JOIN demande_pret d ON rp.id_demande = d.id_demande
                                    JOIN materiel m ON d.id_materiel = m.id_materiel
                                    JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur
                                    LEFT JOIN administrateur a ON a.id_admin = rp.id_admin
                                    WHERE rp.etat_retour = 'défectueux'
                                    ORDER BY rp.date_retour DESC";

                            try {
                                $stmt = $pdo->query($query);
                                
                                $rowCount = 0;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $rowCount++;
                                    // Gérer le cas où l'administrateur n'existe pas
                                    $admin_info = !empty($row['nom_admin']) ? htmlspecialchars($row['nom_admin'] . ' ' . $row['prenom_admin']) : 'Non spécifié';
                                    
                                    echo "<tr>
                                            <td>" . date('d/m/Y H:i', strtotime($row['date_retour'])) . "</td>
                                            <td>" . htmlspecialchars($row['nom_materiel']) . "</td>
                                            <td>" . htmlspecialchars($row['type']) . "</td>
                                            <td>" . htmlspecialchars($row['quantite']) . "</td>
                                            <td>" . htmlspecialchars($row['nom_utilisateur'] . ' ' . $row['prenom_utilisateur']) . "</td>
                                            <td>" . $admin_info . "</td>
                                            <td class='status-defectueux'>" . htmlspecialchars($row['etat_retour']) . "</td>
                                            <td>" . htmlspecialchars($row['commentaire']) . "</td>
                                            </tr>";
                                }
                                
                                if ($rowCount == 0) {
                                    echo "<tr><td colspan='8' class='text-center'>Aucun matériel défectueux trouvé</td></tr>";
                                    
                                    // Vérification simple pour voir si les tables existent et contiennent des données
                                    $checkTables = $pdo->query("SELECT 
                                                            (SELECT COUNT(*) FROM retour_pret WHERE etat_retour = 'défectueux') as def_count,
                                                            (SELECT COUNT(*) FROM retour_pret) as total_retours,
                                                            (SELECT COUNT(*) FROM retour_pret WHERE id_admin IS NOT NULL) as retours_valides");
                                    $tableStats = $checkTables->fetch(PDO::FETCH_ASSOC);
                                    
                                    echo "<tr><td colspan='8' class='text-center text-muted'>";
                                    echo "Statistiques: {$tableStats['def_count']} retours défectueux sur {$tableStats['total_retours']} retours, ";
                                    echo "{$tableStats['retours_valides']} retours validés par un administrateur";
                                    echo "</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='8' class='text-center text-danger'>Erreur de base de données: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        function refreshNotifications() {
            $.ajax({
                url: window.location.href,
                method: 'GET',
                success: function(response) {
                    // Corriger le sélecteur pour cibler le bon conteneur
                    var newContent = $(response).find('.card-body').html();
                    $('.card-body').html(newContent);
                }
            });
        }

        // Rafraîchir les données toutes les 30 secondes
        setInterval(refreshNotifications, 30000);
    </script>
</body>
</html> 