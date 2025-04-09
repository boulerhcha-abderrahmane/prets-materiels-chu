<?php
session_start();
require_once '../../config/config.php';

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Récupérer l'historique des demandes avec les informations des utilisateurs et du matériel
$query = "SELECT 
    d.id_demande,
    d.date_demande,
    d.statut,
    d.date_retour_prevue,
    rp.date_retour,
    u.nom as nom_utilisateur,
    u.prenom,
    
    m.nom,
    m.type
FROM demande_pret d
JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur
JOIN materiel m ON d.id_materiel = m.id_materiel
LEFT JOIN retour_pret rp ON d.id_demande = rp.id_demande
ORDER BY d.date_demande DESC";

$stmt = $pdo->query($query);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Demandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            color: #1a1f36;
            line-height: 1.6;
        }

        .main-content {
            padding: 2rem;
            margin-left: 250px; /* Ajustez selon la largeur de votre sidebar */
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: #1a1f36;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e0e6ed;
            padding-bottom: 0.5rem;
        }

        /* Styles des filtres */
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .form-select, .form-control {
            border: 1.5px solid #e0e6ed;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #1a1f36;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        /* Styles de la table */
        .table-responsive {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1rem;
        }

        .table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            padding: 1.2rem 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .table td {
            padding: 1.2rem 1rem;
            color: #334155;
            font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Styles des badges de statut */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.8;
        }

        /* Styles pour les dates */
        .date-cell {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Style pour le nom d'utilisateur */
        .user-info {
            font-weight: 500;
            color: #1e293b;
        }

        /* Style pour le type de matériel */
        .material-type {
            color: #64748b;
            font-size: 0.85rem;
            background: #f1f5f9;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            display: inline-block;
        }

        /* Couleurs personnalisées pour les badges */
        .bg-warning {
            background-color: #fbbf24 !important;
        }

        .bg-success {
            background-color: #10b981 !important;
        }

        .bg-danger {
            background-color: #ef4444 !important;
        }

        .bg-info {
            background-color: #3b82f6 !important;
        }

        .bg-secondary {
            background-color: #64748b !important;
        }

        /* Responsive design */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .filters .row {
                gap: 1rem;
            }

            .col-md-3, .col-md-6 {
                width: 100%;
            }
        }

        /* Style du compteur de demandes */
        .demande-counter {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.1);
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .demande-counter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.2);
        }

        .demande-counter i {
            font-size: 1.5rem;
            opacity: 0.9;
        }

        .counter-text {
            font-size: 1rem;
            font-weight: 500;
        }

        .counter-number {
            font-weight: 700;
            font-size: 1.2rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1 class="mb-4">Historique des Demandes de Prêt</h1>

            <div class="filters mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">Tous les statuts</option>
                            <option value="en attente">En attente</option>
                            <option value="attente retour">En attente de retour</option>
                            <option value="validé">Validé</option>
                            <option value="refusé">Refusé</option>
                            <option value="retourné">Retourné</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="typeFilter">
                            <option value="">Tous les types</option>
                            <option value="Consommable">Consommable</option>
                            <option value="Non-consommable">Non-consomable</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select type="text" class="form-control" id="searchInput" placeholder="Rechercher par nom d'utilisateur ou matériel...">
                            <option value="">Tous les utilisateurs</option>
                            <?php
                            $query = "SELECT nom, prenom FROM utilisateur";
                            $stmt = $pdo->query($query);
                            $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($utilisateurs as $utilisateur) {
                                echo "<option value='" . $utilisateur['nom'] . " " . $utilisateur['prenom'] . "'>" . $utilisateur['nom'] . " " . $utilisateur['prenom'] . "</option>";
                            }
                          ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <div id="demandeCount" class="demande-counter" style="display: none;">
                            <i class="fas fa-clipboard-list"></i>
                            <span class="counter-text">
                                Demandes pour ce utilisateur : <span id="countNumber" class="counter-number">0</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date Demande</th>
                            <th>Utilisateur</th>
                            <th>Matériel</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Date de retour prévue</th>
                            <th>Date de retour</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $demande): ?>
                            <tr>
                                <td class="date-cell"><?= date('d/m/Y ', strtotime($demande['date_demande'])) ?></td>
                                <td class="user-info"><?= htmlspecialchars($demande['nom_utilisateur'] . ' ' . $demande['prenom']) ?></td>
                                <td><?= htmlspecialchars($demande['nom']) ?></td>
                                <td><span class="material-type"><?= ucfirst(htmlspecialchars($demande['type'])) ?></span></td>
                                
                                <td>
                                    <?php
                                    $statusClass = match($demande['statut']) {
                                        'en_attente' => 'bg-warning',
                                        'valide en attente retour' => 'bg-success',
                                        'validé' => 'bg-success',
                                        'refusé' => 'bg-danger',
                                        'retourné' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                    $statusText = match($demande['statut']) {
                                        'en_attente' => 'En attente',
                                        'valide en attente retour' => ' attente retour',
                                        'validé' => 'validé', 
                                        'refusé' => 'Refusé',
                                        'retourné' => 'Retourné',
                                        default => 'Inconnu'
                                    };
                                  

                                    ?>
                                    <span class="status-badge text-white <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td class="date-cell"><?= $demande['date_retour_prevue'] ? date('d/m/Y ', strtotime($demande['date_retour_prevue'])) : 'N/A' ?></td>
                                <td class="date-cell"><?= $demande['date_retour'] ? date('d/m/Y ', strtotime($demande['date_retour'])) : 'N/A' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const searchInput = document.getElementById('searchInput');
            const demandeCount = document.getElementById('demandeCount');
            const countNumber = document.getElementById('countNumber');

            function filterTable() {
                const statusValue = statusFilter.value.toLowerCase().trim();
                const typeValue = typeFilter.value.toLowerCase().trim();
                const searchValue = searchInput.value.toLowerCase().trim();

                let visibleRows = 0;

                rows.forEach(row => {
                    const status = row.querySelector('.status-badge').textContent.toLowerCase().trim();
                    const type = row.cells[3].textContent.toLowerCase().trim();
                    const searchText = (row.cells[0].textContent + ' ' + row.cells[1].textContent).toLowerCase().trim();

                    const matchesStatus = !statusValue || status.includes(statusValue);
                    const matchesType = !typeValue || type === typeValue;
                    const matchesSearch = !searchValue || searchText.includes(searchValue);

                    if (matchesStatus && matchesType && matchesSearch) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Afficher ou masquer le compteur
                if (searchValue) {
                    demandeCount.style.display = 'block';
                    countNumber.textContent = visibleRows;
                } else {
                    demandeCount.style.display = 'none';
                }
            }

            statusFilter.addEventListener('change', filterTable);
            typeFilter.addEventListener('change', filterTable);
            searchInput.addEventListener('change', filterTable);
            searchInput.addEventListener('input', filterTable);
        });

        
    </script>
</body>
</html> 