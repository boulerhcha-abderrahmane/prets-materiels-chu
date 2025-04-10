<?php
// Assurez-vous qu'il n'y a aucun espace ou ligne vide avant cette balise PHP
session_start();
require_once '../../config/config.php';

if(isset($_SESSION['admin_id'])){
    header('Location: admin_dashboard.php');
    exit();
}

// Fonction de traitement de la connexion       
function processLogin($pdo) {
    if(isset($_POST['submit'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if(!$admin) {
                return '<div class="alert alert-danger">Cet email n\'existe pas dans notre système.</div>';
            }
            else if($password === $admin['mot_de_passe']) {
                $_SESSION['admin_id'] = $admin['id_admin'];
                $_SESSION['admin_nom'] = $admin['nom'];
                $_SESSION['admin_prenom'] = $admin['prenom'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email'] = $admin['email'];
                
                // Retourner le message et le script au lieu de les afficher directement
                return '<div class="alert alert-success">Connexion réussie ! Redirection en cours...</div>
                <script>
                    setTimeout(function() {
                        window.location.href = "admin_dashboard.php";
                    }, 2000);
                </script>';
            } else {
                return '<div class="alert alert-danger">Mot de passe incorrect.</div>';
            }
        } catch(PDOException $e) {
            return '<div class="alert alert-danger">Erreur de connexion: ' . $e->getMessage() . '</div>';
        }
    }
    return '';
}

$loginMessage = processLogin($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Gestion des Prêts - CHU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
       

       
        body {
            background: linear-gradient(135deg,rgb(15, 83, 139), #3498db);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .logo-chu {
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }

        .logo-chu:hover {
            transform: scale(1.05);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 14px 18px;
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(41, 98, 255, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2980b9, #3498db);
            border: none;
            border-radius: 16px;
            padding: 14px 28px;
            font-weight: 600;
        }

        .toggle-form {
            color: var(--accent-color);
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .toggle-form:hover {
            color: var(--secondary-color);
        }

        /* Password Strength Styles */
        .password-strength {
            height: 4px;
            background: #f1f1f1;
            border-radius: 4px;
            margin-top: 12px;
            overflow: hidden;
            position: relative;
        }

        .password-strength::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            transition: all 0.4s ease;
        }

        .password-strength[data-strength="weak"]::before {
            width: 33%;
            background-color: var(--danger-color);
        }

        .password-strength[data-strength="medium"]::before {
            width: 66%;
            background-color: var(--warning-color);
        }

        .password-strength[data-strength="strong"]::before {
            width: 100%;
            background-color: var(--success-color);
        }

        .password-requirements {
            margin-top: 12px;
            font-size: 0.9rem;
        }

        .password-requirements div {
            display: flex;
            align-items: center;
            margin: 8px 0;
            transition: all 0.3s ease;
        }

        .password-requirements i {
            font-size: 14px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .requirement-met {
            color: var(--success-color);
        }

        .requirement-met i {
            transform: scale(1.2);
        }

        .requirement-unmet {
            color: var(--text-color);
            opacity: 0.6;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #loginForm, #registerForm {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        /* Input Groups */
        .input-group .btn {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 8px 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }

            .card {
                margin: 16px;
                padding: 24px !important;
            }

            .form-control {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        
                        
                    </div>

                    <!-- Formulaire de Connexion -->
                    <div id="loginForm">
                        <h1 class="text-center mb-4">interface administrateur</h1>
                        <?php echo $loginMessage; ?>
                        <form id="loginFormElement" method="POST" action="">
                            <div class="mb-4">
                                <label for="loginEmail" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email professionnel
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="loginEmail" 
                                       name="email" 
                                       required
                                       placeholder="exemple@gmail.com"
                                       autocomplete="email">
                                <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                            </div>
                            <div class="mb-4">
                                <label for="loginPassword" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Mot de passe
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="loginPassword" 
                                           name="password" 
                                           required
                                           placeholder="Votre mot de passe"
                                           autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('loginPassword')"
                                            aria-label="Afficher/Masquer le mot de passe">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </button>
                        </form>
                        <div class="text-center mt-4">
                            <a href="../../renetialise_password/forgot_password.php" 
                               class="toggle-form text-success text-decoration-none">
                                <i class="fas fa-key me-2"></i>Mot de passe oublié ?
                            </a>
                        </div>
                    </div>

                    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 