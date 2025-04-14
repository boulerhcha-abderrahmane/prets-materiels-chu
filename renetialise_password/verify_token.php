<?php
session_start();
require_once '../config/config.php';

// Vérification de la session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['user_type'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$type = $_SESSION['user_type'];

// Initialize variables
$expiry_time = null;
$time_remaining = null;

// Fonction pour vérifier l'expiration du token
function checkTokenExpiration($pdo, $email, $type) {
    $sql = ($type === 'utilisateur')
        ? "SELECT reset_token_expiry FROM utilisateur u 
           JOIN email_autorise e ON u.id_email = e.id_email 
           WHERE e.email = ?"
        : "SELECT reset_token_expiry FROM administrateur WHERE email = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch();
}

// Vérification de l'expiration
date_default_timezone_set('Africa/Casablanca');
$result = checkTokenExpiration($pdo, $email, $type);
$time_remaining = 0;

if ($result) {
    $expiry_time = new DateTime($result['reset_token_expiry']);
    $current_time = new DateTime('now');
    $time_remaining = max(0, $expiry_time->getTimestamp() - $current_time->getTimestamp());
    
    // Si le token est expiré, le supprimer de la base de données
    if ($time_remaining <= 0) {
        $clear_sql = ($type === 'utilisateur')
            ? "UPDATE utilisateur u 
               JOIN email_autorise e ON u.id_email = e.id_email 
               SET u.token = NULL, u.reset_token_expiry = NULL 
               WHERE e.email = ?"
            : "UPDATE administrateur 
               SET token = NULL, reset_token_expiry = NULL 
               WHERE email = ?";
        
        $clear_stmt = $pdo->prepare($clear_sql);
        $clear_stmt->execute([$email]);
    }
} else {
    // Handle case where the email is not found in either table
    $error = "Aucun utilisateur trouvé avec cet email.";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $current_time = date('Y-m-d H:i:s');
    
    // Requête SQL selon le type d'utilisateur
    $sql = ($type === 'utilisateur')
        ? "SELECT * FROM utilisateur u 
           JOIN email_autorise e ON u.id_email = e.id_email 
           WHERE e.email = ? AND u.token = ? AND u.reset_token_expiry >= ?"
        : "SELECT * FROM administrateur WHERE email = ? AND token = ? AND reset_token_expiry >= ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $token, $current_time]);
    
    if ($stmt->rowCount() > 0) {
        // Mise à jour du token
        $update_sql = ($type === 'utilisateur')
            ? "UPDATE utilisateur u 
               JOIN email_autorise e ON u.id_email = e.id_email 
               SET u.token = NULL, u.reset_token_expiry = NULL 
               WHERE e.email = ?"
            : "UPDATE administrateur 
               SET token = NULL, reset_token_expiry = NULL 
               WHERE email = ?";
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$email]);

        $_SESSION['token_verified'] = true;
        $_SESSION['reset_email'] = $email;
        $_SESSION['user_type'] = $type;
        
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Code de réinitialisation invalide ou expiré.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vérification du code</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(23, 133, 207) ;
            --primary-dark:rgb(30, 74, 117) ;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --background-start:rgb(229, 233, 237);
            --background-end:rgb(223, 231, 236);
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg,rgb(15, 83, 139), #3498db);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .verify-container {
            background-color: white;
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

        .verify-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg,var(--primary-dark) ,var(--primary-color) );
        }

        .verify-title {
            color: #1e293b;
            text-align: center;
            margin-bottom: 2.5rem;
            font-weight: 800;
            font-size: 2.25rem;
            letter-spacing: -0.025em;
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

        .btn-primary {
            width: 100%;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            background: linear-gradient(90deg, var(--primary-dark), var(--primary-color));
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

        .btn-primary:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        #countdown {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-top: 1rem;
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
        }

        .timer-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 80px;
        }

        .timer-value {
            font-family: 'Inter', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            background: #eff6ff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 2px solid rgba(37, 99, 235, 0.1);
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.05);
            margin-bottom: 0.5rem;
            min-width: 70px;
            text-align: center;
        }

        .timer-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .timer-separator {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-top: -1rem;
        }

        .timer-expired {
            color: var(--danger-color);
            background: #fef2f2;
            padding: 1.25rem;
            border-radius: 12px;
            border: 2px solid #fecaca;
            margin-top: 1rem;
            text-align: center;
            font-weight: 500;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .timer-expired a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .timer-expired a:hover {
            text-decoration: underline;
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
            .verify-container {
                padding: 2rem;
                margin: 0 1rem;
                border-radius: 16px;
            }

            .verify-title {
                font-size: 1.875rem;
            }
        }

        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
            display: flex;
            align-items: flex-start;
            padding: 1.25rem;
            border-radius: 12px;
        }

        .alert-info svg {
            flex-shrink: 0;
            margin-top: 0.25rem;
        }

        .alert-info p {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-info strong {
            color: #1e40af;
        }

        /* Ajout des nouveaux styles */
        .code-input {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5rem;
            font-family: monospace;
        }

        .code-input::-webkit-inner-spin-button,
        .code-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .input-help {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            text-align: center;
        }

        .input-feedback {
            height: 20px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--danger-color);
            text-align: center;
            transition: all 0.3s ease;
        }

        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        /* Styles pour la boîte de code manuel */
        .manual-code-box {
            background: linear-gradient(to right, rgba(255,250,240,0.9), rgba(255,248,230,0.9));
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(234, 179, 8, 0.15);
            border: 1px solid rgba(234, 179, 8, 0.3);
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            animation: fadeIn 0.5s ease;
        }

        .code-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #92400e;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .code-header i {
            color: #f59e0b;
        }

        .code-message {
            color: #78350f;
            margin-bottom: 1.2rem;
        }

        .code-display {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            border: 1px solid rgba(234, 179, 8, 0.2);
            margin-bottom: 1rem;
        }

        .code-value {
            font-family: monospace;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.25rem;
            color: #1e293b;
        }

        .copy-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #64748b;
        }

        .copy-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .code-footer {
            text-align: center;
            color: #92400e;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .expired-message {
            text-align: center;
            color: #b91c1c;
            font-weight: 500;
        }

        .expired-message a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .expired-message a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verify-container">
            <h2 class="verify-title">Vérification du code de réinitialisation</h2>
            
            <div class="alert alert-info mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle me-2" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                <div>
                    <p class="mb-1"><strong>Un code de réinitialisation a été envoyé à votre adresse email : <?php echo htmlspecialchars($email); ?></strong></p>
                    <p class="mb-0">Veuillez vérifier votre boîte de réception (et vos spams) et entrer le code à 6 chiffres ci-dessous. Ce code est valable pour une durée limitée.</p>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="verifyForm">
                <div class="form-group">
                    <label for="token" class="form-label">Code de réinitialisation</label>
                    <input type="number" 
                           name="token" 
                           id="token" 
                           class="form-control code-input" 
                           required
                           minlength="6" 
                           maxlength="6" 
                           pattern="\d{6}"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           placeholder="000000">
                    <div class="input-help">Entrez le code à 6 chiffres reçu par email</div>
                    <div class="input-feedback" id="tokenFeedback"></div>
                    
                    <?php if ($time_remaining > 0): ?>
                        <div id="countdown">
                            <div class="timer-section">
                                <div class="timer-value" id="hours">00</div>
                                <div class="timer-label">Heures</div>
                            </div>
                            <div class="timer-separator">:</div>
                            <div class="timer-section">
                                <div class="timer-value" id="minutes">00</div>
                                <div class="timer-label">Minutes</div>
                            </div>
                            <div class="timer-separator">:</div>
                            <div class="timer-section">
                                <div class="timer-value" id="seconds">00</div>
                                <div class="timer-label">Secondes</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="timer-expired">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-circle mb-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg><br>
                            Le code a expiré. Veuillez <a href="forgot_password.php">demander un nouveau code</a>.
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn" <?php echo ($time_remaining <= 0) ? 'disabled' : ''; ?>>
                    Vérifier le code
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($time_remaining > 0): ?>
    <script>
        // Initialiser le compte à rebours
        let timeRemaining = <?php echo $time_remaining; ?>;
        
        function updateTimer() {
            const hours = Math.floor(timeRemaining / 3600);
            const minutes = Math.floor((timeRemaining % 3600) / 60);
            const seconds = timeRemaining % 60;
            
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                document.getElementById('countdown').innerHTML = `
                    <div class="timer-expired">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-circle mb-2" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg><br>
                        Le code a expiré. Veuillez <a href="forgot_password.php">demander un nouveau code</a>.
                    </div>
                `;
                document.querySelector('button[type="submit"]').disabled = true;
            }
            
            timeRemaining--;
        }
        
        // Mettre à jour le timer chaque seconde
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>

    <script>
        // Ajout de la validation en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const tokenInput = document.getElementById('token');
            const tokenFeedback = document.getElementById('tokenFeedback');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('verifyForm');

            // Auto-focus sur le champ
            tokenInput.focus();

            tokenInput.addEventListener('input', function(e) {
                // Limiter à 6 chiffres
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }

                // Validation en temps réel
                if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
                    tokenFeedback.textContent = '';
                    submitBtn.disabled = false;
                } else {
                    tokenFeedback.textContent = this.value.length > 0 ? 'Le code doit contenir exactement 6 chiffres' : '';
                    submitBtn.disabled = true;
                }
            });

            form.addEventListener('submit', function(e) {
                if (tokenInput.value.length !== 6 || !/^\d{6}$/.test(tokenInput.value)) {
                    e.preventDefault();
                    tokenInput.classList.add('shake');
                    setTimeout(() => tokenInput.classList.remove('shake'), 500);
                    tokenFeedback.textContent = 'Veuillez entrer un code valide à 6 chiffres';
                }
            });
        });
    </script>
</body>
</html>
