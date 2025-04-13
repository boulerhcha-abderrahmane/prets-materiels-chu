<?php
require_once '../../config/config.php';
require_once '../../config/EmailServices.php';
require_once '../../config/api_config.php';

session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Traitement du formulaire de retour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_demande']) && isset($_POST['action'])) {
    if ($_POST['action'] === 'valider') {
        $id_demande = $_POST['id_demande'];
        $etat_retour = $_POST['etat_retour'] ?? 'fonctionnel';
        $commentaire = $_POST['commentaire'] ?? '';
        
        // Récupérer les informations de la demande
        $getDemande = $pdo->prepare("SELECT d.*, 
                                   m.nom AS nom_materiel, 
                                   u.id_utilisateur,
                                   u.nom AS nom_utilisateur,
                                   u.prenom AS prenom_utilisateur
                                   FROM demande_pret d 
                                   JOIN materiel m ON d.id_materiel = m.id_materiel 
                                   JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur 
                                   WHERE d.id_demande = ?");
        $getDemande->execute([$id_demande]);
        $demande = $getDemande->fetch(PDO::FETCH_ASSOC);

        try {
            $pdo->beginTransaction();

            // Mettre à jour le stock du matériel seulement si l'état est fonctionnel
            if ($etat_retour === 'fonctionnel') {
                $updateStock = $pdo->prepare("UPDATE materiel SET quantite_disponible = quantite_disponible + ? WHERE id_materiel = ?");
                $updateStock->execute([$demande['quantite'], $demande['id_materiel']]);
            }
            
            // Mettre à jour le statut de la demande
            $updateDemande = $pdo->prepare("UPDATE demande_pret SET statut = 'retourné' WHERE id_demande = ?");
            $updateDemande->execute([$id_demande]);

            // Vérifier si l'id_admin existe dans la session
            if (!isset($_SESSION['admin_id'])) {
                throw new Exception("ID administrateur non trouvé dans la session");
            }

            // ajoute une entrée dans la table retour_pret
            $insertRetour = $pdo->prepare("INSERT INTO retour_pret (id_demande, date_retour, etat_retour, commentaire, id_admin) VALUES (?, NOW(), ?, ?, ?)");
            $insertRetour->execute([$id_demande, $etat_retour, $commentaire, $_SESSION['admin_id']]);

            // Ajouter une entrée dans l'historique des actions
            $actionDescription = "Retour de matériel : {$demande['nom_materiel']} (Quantité : {$demande['quantite']}) - État : {$etat_retour} - Retourné par {$demande['prenom_utilisateur']} {$demande['nom_utilisateur']}";
            $insertHistorique = $pdo->prepare("INSERT INTO historique_actions (
                id_admin, 
                type_action, 
                date_action,
                details
            ) VALUES (?, ?, NOW(), ?)");
            $insertHistorique->execute([
                $_SESSION['admin_id'],
                'validation_retour',
                $actionDescription
            ]);

            // Créer la notification pour l'utilisateur
            $message = "Votre retour de " . $demande['quantite'] . " " . $demande['nom_materiel'] . " a été validé";
            $stmt = $pdo->prepare("INSERT INTO notification (id_utilisateur, message, type, date_envoi, lu) 
                                 VALUES (?, ?, 'retour', NOW(), FALSE)");
            $stmt->execute([
                $demande['id_utilisateur'],
                $message
            ]);

            // Valider la transaction pour les opérations de base de données
            $pdo->commit();

            // Essayer d'envoyer l'email (en dehors de la transaction)
            try {
                // Récupérer les informations de l'utilisateur et son email autorisé
                $getEmailInfo = $pdo->prepare("
                    SELECT u.nom, u.prenom, e.email 
                    FROM utilisateur u 
                    JOIN email_autorise e ON u.id_email = e.id_email 
                    WHERE u.id_utilisateur = ?");
                $getEmailInfo->execute([$demande['id_utilisateur']]);
                $utilisateur = $getEmailInfo->fetch(PDO::FETCH_ASSOC);

                // Préparer le contenu HTML de l'email
                $htmlContent = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Confirmation de retour de matériel</h2>
                    <p>Bonjour {$utilisateur['prenom']} {$utilisateur['nom']},</p>
                    <p>Nous confirmons le retour de <strong>{$demande['quantite']} {$demande['nom_materiel']}</strong>.</p>
                    <p>État du matériel : <strong>{$etat_retour}</strong></p>";

                if ($commentaire) {
                    $htmlContent .= "<p>Commentaire : {$commentaire}</p>";
                }

                $htmlContent .= "
                    <p>Cordialement,<br>L'équipe de gestion du matériel</p>
                </body>
                </html>";

                // Utiliser EmailService pour envoyer l'email
                $emailService = new EmailService(RESEND_API_KEY, RESEND_FROM_EMAIL);
                $result = $emailService->sendEmail($utilisateur['email'], 'Confirmation de retour de matériel', $htmlContent);

                if (!$result['success']) {
                    throw new Exception("Erreur lors de l'envoi de l'email");
                }

                $_SESSION['return_success'] = "Le retour a été validé avec succès et l'email de confirmation a été envoyé à l'utilisateur.";
            } catch(Exception $emailError) {
                $_SESSION['return_warning'] = "Le retour a été validé mais l'envoi de l'email a échoué.";
                error_log("Erreur d'envoi d'email : " . $emailError->getMessage());
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur lors du traitement du retour : " . $e->getMessage());
            $_SESSION['return_error'] = "Une erreur est survenue lors du traitement du retour.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch(Exception $e) {
            $pdo->rollBack();
            error_log("Erreur : " . $e->getMessage());
            $_SESSION['return_error'] = "Une erreur est survenue lors du traitement du retour.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Après la validation du retour
if (isset($_POST['valider_retour'])) {
    try {
        $pdo->beginTransaction();
        
        $id_pret = $_POST['id_pret'];
        $commentaire = $_POST['commentaire'] ?? '';
        
        // Mettre à jour le statut du prêt
        $stmt = $pdo->prepare("UPDATE pret SET statut = 'retourné', date_retour = NOW() WHERE id = ?");
        $stmt->execute([$id_pret]);
        
        // Récupérer l'ID de l'utilisateur concerné
        $stmt = $pdo->prepare("SELECT id_utilisateur FROM pret WHERE id = ?");
        $stmt->execute([$id_pret]);
        $userId = $stmt->fetchColumn();
        
        // Créer la notification
        $message = "Votre retour de matériel a été validé.";
        if ($commentaire) {
            $message .= " Commentaire: " . $commentaire;
        }
        
        $stmt = $pdo->prepare("INSERT INTO notification (id_utilisateur, type, message) VALUES (?, 'validation', ?)");
        $stmt->execute([$userId, $message]);
        
        $pdo->commit();
        $_SESSION['success'] = "Le retour a été validé avec succès.";
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la validation du retour : " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la validation du retour.";
    }
    
    header("Location: gestion_retours.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Retours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container">
            <header>
                <h1>Gestion des Retours</h1>
            </header>

            <section class="return-materials">
                <?php
                // Requête pour compter les demandes en attente de retour
                $countStmt = $pdo->query("
                    SELECT COUNT(*) as count
                    FROM demande_pret 
                    WHERE statut IN ( 'valide en attente retour')
                ");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="mb-4">
                    <h4>Nombre de demandes en attente de retour : <?php echo $count['count']; ?></h4>
                    
                    <!-- Ajout du formulaire de filtrage -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="date_filter" class="form-label">Date de retour prévue</label>
                            <select name="date" id="date_filter" class="form-select">
                                <option value="">Toutes les dates</option>
                                <option value="retard" <?php echo (isset($_GET['date']) && $_GET['date'] === 'retard') ? 'selected' : ''; ?>>En retard</option>
                                <option value="aujourdhui" <?php echo (isset($_GET['date']) && $_GET['date'] === 'aujourdhui') ? 'selected' : ''; ?>>Aujourd'hui</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>
                </div>

                
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Nom Matériel</th>
                            <th>Quantité</th>
                            <th>Date de retour prévue</th>
                            <th>État</th>
                            <th>Commentaire</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Supprimer la pagination par page
                        // $per_page = 10; 
                        // $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        // $offset = ($page - 1) * $per_page;
                        
                        // Modifier la requête pour inclure les filtres
                        $where_conditions = ["d.statut IN ( 'valide en attente retour')"];
                        $params = [];

                        if (!empty($_GET['date'])) {
                            switch($_GET['date']) {
                                case 'retard':
                                    $where_conditions[] = "d.date_retour_prevue < NOW()";
                                    break;
                                case 'aujourdhui':
                                    $where_conditions[] = "DATE(d.date_retour_prevue) = CURRENT_DATE()";
                                    break;
                            }
                        }

                        $where_clause = implode(" AND ", $where_conditions);

                        // Requête principale sans pagination - afficher toutes les demandes
                        $sql = "
                            SELECT 
                                d.id_demande, 
                                m.id_materiel,
                                m.type, 
                                m.nom AS nom_materiel, 
                                d.quantite, 
                                d.date_retour_prevue,
                                u.nom AS nom_utilisateur, 
                                u.prenom AS prenom_utilisateur,
                                u.id_utilisateur
                            FROM demande_pret d 
                            JOIN materiel m ON d.id_materiel = m.id_materiel 
                            JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur 
                            WHERE {$where_clause}
                            ORDER BY d.id_demande DESC
                        ";

                        $stmt = $pdo->prepare($sql);

                        // Ajouter les paramètres de filtre
                        foreach ($params as $key => $value) {
                            $stmt->bindValue($key, $value);
                        }

                        // Exécuter la requête
                        $stmt->execute();

                        while ($demande = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $dateRetourAffichage = $demande['type'] === 'consommable' ? 
                                "N/A" : 
                                (empty($demande['date_retour_prevue']) ? 'Date à définir' : date('Y-m-d/H:i:s', strtotime($demande['date_retour_prevue'])) );

                            $isLate = !empty($demande['date_retour_prevue']) && strtotime($demande['date_retour_prevue']) < time();
                            $rowClass = $isLate ? 'table-danger' : '';

                            echo "<tr class='$rowClass'>
                                      <td>
                                        <div class='d-flex align-items-center'>
                                            <i class='fas fa-user me-2'></i>
                                            " . htmlspecialchars($demande['nom_utilisateur'] . " " . $demande['prenom_utilisateur']) . "
                                        </div>
                                      </td>
                                      <td><span class='badge bg-info'>" . htmlspecialchars($demande['type']) . "</span></td>   
                                    <td>" . htmlspecialchars($demande['nom_materiel']) . "</td>
                                    
                                    <td><span class='badge bg-secondary'>" . htmlspecialchars($demande['quantite']) . "</span></td>
                                    <td>
                                        " . ($isLate ? "<span class='text-danger'><i class='fas fa-exclamation-triangle me-1'></i>" : '') . "
                                        " . $dateRetourAffichage . "
                                        " . ($isLate ? "</span>" : '') . "
                                    </td>
                                  
                                    <td>
                                        <form action='" . $_SERVER['PHP_SELF'] . "' method='POST' style='display:flex; align-items:center;'>
                                            <input type='hidden' name='id_demande' value='" . htmlspecialchars($demande['id_demande']) . "'>
                                            <input type='hidden' name='action' value='valider'>
                                            <label for='etat_retour_" . htmlspecialchars($demande['id_demande']) . "' class='visually-hidden'>État du retour</label>
                                            <select name='etat_retour' id='etat_retour_" . htmlspecialchars($demande['id_demande']) . "' class='form-select form-select-sm' style='width: auto;'>
                                                <option value='fonctionnel'>Fonctionnel</option>
                                                <option value='défectueux'>Défectueux</option>
                                            </select>
                                        </td>
                                        <td>
                                            <label for='commentaire_" . htmlspecialchars($demande['id_demande']) . "' class='visually-hidden'>Commentaire du retour</label>
                                            <input type='text' name='commentaire' id='commentaire_" . htmlspecialchars($demande['id_demande']) . "' placeholder='Commentaire du retour' class='form-control form-control-sm' style='width: 200px;'>
                                        </td>
                                        <td>
                                            <button type='submit' class='btn btn-success'>
                                                <i class='fas fa-check'></i>
                                                <span>Valider le Retour</span>
                                            </button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <div class="alert-container">
        <?php
        if (isset($_SESSION['return_success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($_SESSION['return_success']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <span class="visually-hidden">Fermer</span>
                    </button>
                  </div>';
            unset($_SESSION['return_success']);
        }

        if (isset($_SESSION['return_error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($_SESSION['return_error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <span class="visually-hidden">Fermer</span>
                    </button>
                  </div>';
            unset($_SESSION['return_error']);
        }

        if (isset($_SESSION['return_warning'])) {
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($_SESSION['return_warning']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <span class="visually-hidden">Fermer</span>
                    </button>
                  </div>';
            unset($_SESSION['return_warning']);
        }
        ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 10000);
            });
        });
    </script>
    
    <script>
        // Solution simplifiée pour l'accessibilité ARIA des modals
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion simplifiée des modals pour éviter les problèmes d'accessibilité
            const modals = document.querySelectorAll('.modal');
            let lastFocusedElement = null;
            
            // Gestionnaire pour l'ouverture des modals
            modals.forEach(modal => {
                // Avant que le modal s'ouvre
                modal.addEventListener('show.bs.modal', function() {
                    // Stocker l'élément actuellement focalisé
                    lastFocusedElement = document.activeElement;
                    
                    // S'assurer que le modal n'est pas caché des lecteurs d'écran
                    this.removeAttribute('aria-hidden');
                });
                
                // Quand le modal est ouvert
                modal.addEventListener('shown.bs.modal', function() {
                    // Focus sur le premier élément interactif
                    const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    if (firstFocusable) {
                        firstFocusable.focus();
                    }
                });
                
                // Quand le modal est fermé
                modal.addEventListener('hidden.bs.modal', function() {
                    // Remettre le focus sur l'élément qui était focalisé avant l'ouverture
                    if (lastFocusedElement) {
                        lastFocusedElement.focus();
                    }
                });
            });
            
            // Empêcher le focus de rester sur les boutons qui ferment le modal
            document.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
                button.addEventListener('click', function() {
                    // Éviter de retourner true qui signalerait une réponse asynchrone
                    this.blur();
                });
            });
        });
    </script>
</body>
</html>