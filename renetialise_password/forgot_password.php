<?php
// Désactiver la mise en mémoire tampon de sortie
ob_start();

// Démarrer la session une seule fois
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ne pas détruire complètement la session, juste nettoyer les variables spécifiques
$_SESSION = array_intersect_key($_SESSION, array_flip(['user_type']));

// Inclure la configuration en premier
require_once '../config/config.php';
require_once '../config/api_config.php';
require_once '../config/EmailServices.php';

// Initialiser le service d'email
$emailService = new EmailService(RESEND_API_KEY, RESEND_FROM_EMAIL);

// Définir les headers
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Définir le fuseau horaire
date_default_timezone_set('Africa/Casablanca');

// Maintenant on peut nettoyer les tokens expirés
try {
    cleanExpiredTokens($pdo);
} catch (Exception $e) {
    error_log("Erreur lors du nettoyage des tokens expirés : " . $e->getMessage());
}

// Nettoyer complètement la session sauf pour l'administrateur
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'administrateur') {
    // Sauvegarder le type d'utilisateur si c'est un admin
    $is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrateur';
    
    // Nettoyer toutes les variables de session
    $_SESSION = array();
    
    // Détruire le cookie de session si existant
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Restaurer le statut admin si nécessaire
    if ($is_admin) {
        $_SESSION['user_type'] = 'administrateur';
    }
}

// Au début du fichier, après session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Modifier la fonction sendResetEmail pour utiliser l'instance créée
function sendResetEmail($email, $token) {
    global $emailService; // Ajouter cette ligne pour accéder à la variable globale
    
    $htmlContent = "
        <h2>Réinitialisation de votre mot de passe</h2>
        <p>Voici votre code de réinitialisation : <strong>{$token}</strong></p>
        <p>Ce code expirera dans 5 minutes.</p>
        <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
    ";
    
    try {
        $result = $emailService->sendEmail($email, 'Réinitialisation de votre mot de passe', $htmlContent);
        if (!$result['success']) {
            throw new Exception('Erreur lors de l\'envoi de l\'email. Code: ' . $result['httpCode']);
        }
        return true;
    } catch (Exception $e) {
        throw new Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    }
}

// Ajouter cette fonction après la fonction sendResetEmail
function cleanExpiredTokens($pdo) {
    $current_date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
    $current_timestamp = $current_date->format('Y-m-d H:i:s');
    
    // Nettoyer les tokens expirés dans la table administrateur
    $sql = "UPDATE administrateur SET token = NULL, reset_token_expiry = NULL 
            WHERE reset_token_expiry < ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_timestamp]);
    
    // Nettoyer les tokens expirés dans la table utilisateur
    $sql = "UPDATE utilisateur SET token = NULL, reset_token_expiry = NULL 
            WHERE reset_token_expiry < ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_timestamp]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyer les tokens expirés avant de traiter une nouvelle demande
    cleanExpiredTokens($pdo);
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $userFound = false;
    
    // Vérifier d'abord dans la table administrateur
    $sql = "SELECT id_admin FROM administrateur WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $userFound = true;
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $current_date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
        $reset_token_expiry = $current_date->modify('+5 minutes')->format('Y-m-d H:i:s');
        
        $sql = "UPDATE administrateur SET token = ?, reset_token_expiry = ? WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token, $reset_token_expiry, $email]);
        
        $_SESSION['reset_email'] = $email;
        $_SESSION['user_type'] = 'administrateur';
        
        // Envoi de l'email avec le token
        try {
            if (sendResetEmail($email, $token)) {
                $_SESSION['success_message'] = "Un code de vérification a été envoyé à votre adresse email.";
                $_SESSION['reset_in_progress'] = true;
                header("Location: verify_token.php");
                exit();
            }
        } catch (Exception $e) {
            echo "<div style='background: #fee; padding: 20px; margin: 20px; border: 1px solid #faa;'>";
            echo "<h4>Exception attrapée :</h4>";
            echo $e->getMessage();
            echo "</div>";
        }
    }
    
    // Si pas trouvé dans administrateur, chercher dans utilisateur
    if (!$userFound) {
        $sql = "SELECT u.id_utilisateur FROM utilisateur u JOIN EMAIL_AUTORISE e ON u.id_email = e.id_email WHERE e.email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $userFound = true;
            $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $current_date = new DateTime('now', new DateTimeZone('Africa/Casablanca'));
            $reset_token_expiry = $current_date->modify('+5 minutes')->format('Y-m-d H:i:s');
            
            $sql = "UPDATE utilisateur SET token = ?, reset_token_expiry = ? WHERE id_email = (SELECT id_email FROM EMAIL_AUTORISE WHERE email = ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$token, $reset_token_expiry, $email]);
            
            $_SESSION['reset_email'] = $email;
            $_SESSION['user_type'] = 'utilisateur';
            
            // Envoi de l'email avec le token
            try {
                if (sendResetEmail($email, $token)) {
                    $_SESSION['success_message'] = "Un code de vérification a été envoyé à votre adresse email.";
                    $_SESSION['reset_in_progress'] = true;
                    header("Location: verify_token.php");
                    exit();
                }
            } catch (Exception $e) {
                echo "<div style='background: #fee; padding: 20px; margin: 20px; border: 1px solid #faa;'>";
                echo "<h4>Exception attrapée :</h4>";
                echo $e->getMessage();
                echo "</div>";
            }
        }
    }
    
    // Afficher le message d'erreur uniquement si l'utilisateur n'a pas été trouvé
    if (!$userFound) {
        $error = "Cet email n'existe pas dans notre base de données.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation du mot de passe</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color:rgb(30, 74, 117) ;
            --primary-dark:rgb(23, 133, 207);
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --background-start:rgb(234, 239, 245);
            --background-end:rgb(239, 244, 248);
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: #f5f5f5;
            background-image: 
                radial-gradient(at 47% 33%, hsl(214.93, 71%, 90%) 0, transparent 59%), 
                radial-gradient(at 82% 65%, hsl(218.08, 39%, 91%) 0, transparent 55%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .reset-container {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 20px 40px var(--shadow-color);
            padding: 3.5rem;
            max-width: 550px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        }

        .reset-title {
            color: #1e293b;
            text-align: center;
            margin-bottom: 2.5rem;
            font-weight: 800;
            font-size: 2.25rem;
            letter-spacing: -0.025em;
        }

        .reset-subtitle {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.75rem;
            display: block;
            font-size: 0.95rem;
        }

        .form-control {
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 1rem;
            width: 100%;
            background-color: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: white;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            margin-bottom: 2rem;
            padding: 1.25rem;
            border: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.4s ease-out;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-success {
            background-color: #f0fdf4;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 640px) {
            .reset-container {
                padding: 2rem;
                margin: 0 1rem;
                border-radius: 16px;
            }

            .reset-title {
                font-size: 1.875rem;
            }

            .reset-subtitle {
                font-size: 1rem;
            }

            .form-control, .btn-primary {
                padding: 0.875rem;
            }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .back-link svg {
            transition: transform 0.3s ease;
        }

        .back-link:hover svg {
            transform: translateX(-4px);
        }

        /* Ajout d'un indicateur de chargement */
        .btn-primary.loading {
            position: relative;
            color: transparent;
        }

        .btn-primary.loading::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Style pour l'indicateur de force du mot de passe */
        .email-suggestion {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-top: 0.5rem;
            display: none;
        }

        .email-suggestion.show {
            display: block;
        }

        /* Ajout de styles pour le feedback visuel */
        .form-control.is-valid {
            border-color: var(--success-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* Style pour le loader avec progression */
        .progress-loader {
            width: 100%;
            height: 4px;
            position: absolute;
            top: 0;
            left: 0;
            overflow: hidden;
            background-color: #f3f4f6;
            display: none;
        }

        .progress-loader::after {
            content: '';
            width: 40%;
            height: 100%;
            background: var(--primary-color);
            position: absolute;
            left: -40%;
            animation: loading 1s linear infinite;
        }

        @keyframes loading {
            from { left: -40%; }
            to { left: 100%; }
        }

        /* Style pour le message de confirmation */
        .confirmation-message {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }

        /* Ajout d'un effet de profondeur subtil */
        .reset-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <a href="../index.php" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Retour
            </a>
            <h2 class="reset-title">Mot de passe oublié ?</h2>
            <p class="reset-subtitle">
                Entrez votre adresse e-mail et nous vous enverrons un code pour réinitialiser votre mot de passe.
            </p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <div class="progress-loader" id="progressLoader"></div>
                <div class="form-group">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="form-control" 
                           placeholder="exemple@domaine.com"
                           required
                           autocomplete="email"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                    <div class="email-suggestion" id="emailSuggestion"></div>
                    <small class="form-text text-muted">Nous vous enverrons un code de vérification à cette adresse.</small>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    Réinitialiser le mot de passe
                </button>
                <div class="confirmation-message" id="confirmationMessage">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p>Envoi du code de réinitialisation en cours...</p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        const loader = document.getElementById('progressLoader');
        const confirmationMessage = document.getElementById('confirmationMessage');
        
        // Afficher le loader et le message de confirmation
        loader.style.display = 'block';
        confirmationMessage.style.display = 'block';
        btn.classList.add('loading');
        btn.disabled = true;
    });

    // Validation en temps réel de l'email
    document.getElementById('email').addEventListener('input', function(e) {
        const email = e.target.value;
        const input = e.target;
        const suggestion = document.getElementById('emailSuggestion');
        
        // Expression régulière pour la validation email
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        
        if (emailRegex.test(email)) {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }

        if (email.includes('@')) {
            const [localPart, domain] = email.split('@');
            const commonDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
            
            const closestDomain = commonDomains.find(d => 
                d.startsWith(domain) || 
                domain.length > 2 && d.includes(domain)
            );
            
            if (closestDomain && domain !== closestDomain) {
                suggestion.innerHTML = `Vouliez-vous dire : <a href="#" class="suggest-email">${localPart}@${closestDomain}</a> ?`;
                suggestion.classList.add('show');
            } else {
                suggestion.classList.remove('show');
            }
        }
    });

    // Permettre de cliquer sur la suggestion d'email
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('suggest-email')) {
            e.preventDefault();
            document.getElementById('email').value = e.target.textContent;
            document.getElementById('emailSuggestion').classList.remove('show');
        }
    });
    </script>
</body>
</html> 
</html> 