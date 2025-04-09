<?php
session_start();
require_once '../config/config.php';

// Check if the user is logged in and has a reset email
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['user_type'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifier si les mots de passe correspondent
    if ($new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Prepare the SQL query based on user type
        if ($user_type === 'administrateur') {
            // Update password for administrateur
            $sql = "UPDATE administrateur SET mot_de_passe = ? WHERE email = ?";
        } else {
            // Update password for utilisateur
            $sql = "UPDATE utilisateur u 
                    JOIN email_autorise e ON u.id_email = e.id_email 
                    SET u.mot_de_passe = ? WHERE e.email = ?";
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_password, $email]);
            
            // Configuration de Resend
            $curl = curl_init();
            
            $data = array(
                "from" => 'Gestion des Prêts <onboarding@resend.dev>',
                "to" => $email,
                "subject" => "Confirmation de réinitialisation du mot de passe",
                "text" => "Bonjour,\n\n" .
                         "Votre mot de passe a été réinitialisé avec succès.\n" .
                         "Si vous n'êtes pas à l'origine de cette action, veuillez contacter l'administrateur immédiatement.\n\n" .
                         "Cordialement,\nL'équipe de support"
            );

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.resend.com/emails',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer re_BtCXgwta_AphCeiYV8M6cGxT1qsqqeYgp',
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            
            // Nettoyer les variables de session
            unset($_SESSION['reset_email']);
            unset($_SESSION['user_type']);
            
            header("Location: success_page.php");
            exit();
        } catch (PDOException $e) {
            die("Erreur lors de la mise à jour du mot de passe : " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Réinitialiser le mot de passe</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --background-start: rgb(229, 237, 245);
            --background-end: rgb(224, 230, 234);
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, var(--background-start) 0%, var(--background-end) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .reset-container {
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

        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
            display: flex;
            align-items: flex-start;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .password-container {
            margin-bottom: 2rem;
        }

        .password-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.75rem;
            display: block;
            font-size: 0.95rem;
        }

        .password-input {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }

        .password-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: white;
            outline: none;
        }

        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8fafc;
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--secondary-color);
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

        @media (max-width: 640px) {
            .reset-container {
                padding: 2rem;
                margin: 0 1rem;
                border-radius: 16px;
            }

            .reset-title {
                font-size: 1.875rem;
            }
        }

        .password-match-message {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .password-match-message.success {
            color: var(--success-color);
        }

        .password-match-message.error {
            color: var(--danger-color);
        }

        .password-input.error {
            border-color: var(--danger-color);
        }

        .password-input.success {
            border-color: var(--success-color);
        }

        /* Ajout des nouveaux styles */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            border-radius: 2px;
            background-color: #e2e8f0;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .strength-weak { width: 33%; background-color: var(--danger-color); }
        .strength-medium { width: 66%; background-color: #eab308; }
        .strength-strong { width: 100%; background-color: var(--success-color); }

        .password-input-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--secondary-color);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
        }

        .requirement.valid {
            color: var(--success-color);
        }

        .requirement i {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <h2 class="reset-title">Réinitialiser le mot de passe</h2>

            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-info-circle me-2" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                <div>
                    <p class="mb-0">Veuillez choisir un nouveau mot de passe sécurisé pour votre compte.</p>
                </div>
            </div>

            <form method="POST" id="resetForm">
                <div class="password-container">
                    <label for="new_password" class="password-label">Nouveau mot de passe :</label>
                    <div class="password-input-wrapper">
                        <input type="password" 
                               name="new_password" 
                               id="new_password" 
                               class="password-input mb-3"
                               required>
                        <button type="button" class="toggle-password" data-target="new_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar"></div>
                    </div>
                    
                    <label for="confirm_password" class="password-label">Confirmer le mot de passe :</label>
                    <div class="password-input-wrapper">
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password" 
                               class="password-input"
                               required>
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div id="password-match-message" class="password-match-message"></div>
                    
                    <div class="password-requirements">
                        <div class="mb-2">Votre mot de passe doit contenir :</div>
                        <ul class="mb-0 ps-3">
                            <li class="requirement" id="length"><i class="bi bi-circle"></i>Au moins 8 caractères</li>
                            <li class="requirement" id="uppercase"><i class="bi bi-circle"></i>Au moins une lettre majuscule</li>
                            <li class="requirement" id="lowercase"><i class="bi bi-circle"></i>Au moins une lettre minuscule</li>
                            <li class="requirement" id="number"><i class="bi bi-circle"></i>Au moins un chiffre</li>
                            <li class="requirement" id="special"><i class="bi bi-circle"></i>Au moins un caractère spécial</li>
                        </ul>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">Réinitialiser le mot de passe</button>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('resetForm');
                    const newPassword = document.getElementById('new_password');
                    const confirmPassword = document.getElementById('confirm_password');
                    const matchMessage = document.getElementById('password-match-message');
                    const submitBtn = document.getElementById('submitBtn');

                    function validatePasswords() {
                        const newVal = newPassword.value;
                        const confirmVal = confirmPassword.value;

                        if (confirmVal.length === 0) {
                            matchMessage.textContent = '';
                            matchMessage.className = 'password-match-message';
                            confirmPassword.className = 'password-input';
                            submitBtn.disabled = true;
                            return;
                        }

                        if (newVal === confirmVal) {
                            matchMessage.textContent = 'Les mots de passe correspondent';
                            matchMessage.className = 'password-match-message success';
                            confirmPassword.className = 'password-input success';
                            submitBtn.disabled = false;
                        } else {
                            matchMessage.textContent = 'Les mots de passe ne correspondent pas';
                            matchMessage.className = 'password-match-message error';
                            confirmPassword.className = 'password-input error';
                            submitBtn.disabled = true;
                        }
                    }

                    // Fonction pour vérifier la force du mot de passe
                    function checkPasswordStrength(password) {
                        const requirements = {
                            length: password.length >= 8,
                            uppercase: /[A-Z]/.test(password),
                            lowercase: /[a-z]/.test(password),
                            number: /[0-9]/.test(password),
                            special: /[^A-Za-z0-9]/.test(password)
                        };

                        const strengthBar = document.querySelector('.password-strength-bar');
                        const validCount = Object.values(requirements).filter(Boolean).length;

                        // Mise à jour des indicateurs de requirements
                        Object.keys(requirements).forEach(req => {
                            const element = document.getElementById(req);
                            if (requirements[req]) {
                                element.classList.add('valid');
                                element.querySelector('i').className = 'bi bi-check-circle-fill';
                            } else {
                                element.classList.remove('valid');
                                element.querySelector('i').className = 'bi bi-circle';
                            }
                        });

                        // Mise à jour de la barre de force
                        if (validCount <= 2) {
                            strengthBar.className = 'password-strength-bar strength-weak';
                        } else if (validCount <= 4) {
                            strengthBar.className = 'password-strength-bar strength-medium';
                        } else {
                            strengthBar.className = 'password-strength-bar strength-strong';
                        }
                    }

                    // Gestion des boutons afficher/masquer mot de passe
                    document.querySelectorAll('.toggle-password').forEach(button => {
                        button.addEventListener('click', function() {
                            const input = document.getElementById(this.dataset.target);
                            const icon = this.querySelector('i');
                            
                            if (input.type === 'password') {
                                input.type = 'text';
                                icon.className = 'bi bi-eye-slash';
                            } else {
                                input.type = 'password';
                                icon.className = 'bi bi-eye';
                            }
                        });
                    });

                    newPassword.addEventListener('input', function() {
                        checkPasswordStrength(this.value);
                        validatePasswords();
                    });

                    confirmPassword.addEventListener('input', validatePasswords);

                    form.addEventListener('submit', function(e) {
                        if (newPassword.value !== confirmPassword.value) {
                            e.preventDefault();
                            matchMessage.textContent = 'Les mots de passe ne correspondent pas';
                            matchMessage.className = 'password-match-message error';
                        }
                    });
                });
            </script>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
