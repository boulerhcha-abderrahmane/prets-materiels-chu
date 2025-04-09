<?php
session_start();
require_once '../../config/config.php';

// Si l'utilisateur est déjà connecté, rediriger vers la page appropriée
if (isset($_SESSION['user_type'])) {
    header('Location: ../user/home.php');
    exit;
}

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérifier les identifiants
    $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE email = ? AND actif = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {

        
        // Connexion réussie
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['user_type'] = 'user';
        header('Location: ../user/home.php');
        exit;
    } else {
        $error_message = "Email ou mot de passe incorrect";
    }
}
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
    <link href="assets/logo/logo-chu.png" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
  
            --secondary-color: #3498db;
            --accent-color: #2980b9;
            --success-color: #2ecc71;
           
            --border-color: #e0e0e0;
        }

        body {
            background: linear-gradient(135deg, #2c3e50, #3498db);
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
                        <h1 class="text-center mb-4">interface utilisateur</h1>
                        <form id="loginFormElement">
                            <div class="mb-3">
                                <label for="loginEmail" class="form-label">Email professionnel</label>
                                <input type="email" class="form-control" id="loginEmail" required>
                                <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                            </div>
                            <div class="mb-3">
                                <label for="loginPassword" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="loginPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('loginPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                        </form>
                        <div class="text-center mt-3">
                            <span class="toggle-form" onclick="toggleForms()">Créer un compte</span>
                        </div>
                    </div>

                    <!-- Formulaire d'Inscription -->
                    <div id="registerForm" style="display: none;">
                        <h3 class="text-center mb-4">Création de compte</h3>
                        <form id="registerFormElement">
                            <div class="mb-3">
                                <label for="registerNom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="registerNom" required>
                            </div>
                            <div class="mb-3">
                                <label for="registerPrenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="registerPrenom" required>
                            </div>
                            <div class="mb-3">
                                <label for="registerEmail" class="form-label">Email professionnel</label>
                                <input type="email" class="form-control" id="registerEmail" required>
                                <div class="form-text">Utilisez votre email professionnel du CHU.</div>
                            </div>
                            <div class="mb-3">
                                <label for="registerRole" class="form-label">Rôle</label>
                                <select class="form-select" id="registerRole" required>
                                    <option value="">Sélectionnez votre rôle</option>
                                    <option value="technicien">Technicien</option>
                                    <option value="ingénieur informatique">Ingénieur informatique</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="registerPassword" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="registerPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('registerPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength"></div>
                                <div class="password-requirements mt-2">
                                    <div id="length-req"><i class="fas fa-circle"></i> 8 caractères minimum</div>
                                    <div id="uppercase-req"><i class="fas fa-circle"></i> Une majuscule</div>
                                    <div id="lowercase-req"><i class="fas fa-circle"></i> Une minuscule</div>
                                    <div id="number-req"><i class="fas fa-circle"></i> Un chiffre</div>
                                    <div id="special-req"><i class="fas fa-circle"></i> Un caractère spécial</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="registerConfirmPassword" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="registerConfirmPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="registerPhoto" class="form-label">Photo de profil</label>
                                <input type="file" class="form-control" id="registerPhoto" accept="image/*">
                                <div class="form-text">Format accepté : JPG, PNG, GIF (max 5MB)</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Créer le compte</button>
                        </form>
                        <div class="text-center mt-3">
                            <span class="toggle-form" onclick="toggleForms()">Déjà inscrit ? Se connecter</span>
                        </div>
                        
                    </div><div class="text-center mt-3">
                            <a href="../../renetialise_password/forgot_password.php" class="toggle-form text-success text-decoration-none">
                                <i class="fas fa-key me-1"></i>Mot de passe oublié ?
                            </a>
                        </div>

                    <!-- Conteneur pour les alertes -->
                    <div id="alertContainer" class="mt-3">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class FormValidator {
            constructor() {
                this.passwordRequirements = {
                    minLength: 8,
                    hasUpperCase: /[A-Z]/,
                    hasLowerCase: /[a-z]/,
                    hasNumbers: /\d/,
                    hasSpecialChar: /[!@#$%^&*(),.?":{}|<>]/
                };
            }

            validatePassword(password) {
                const errors = [];
                if (password.length < this.passwordRequirements.minLength) {
                    errors.push("Le mot de passe doit contenir au moins 8 caractères");
                }
                if (!this.passwordRequirements.hasUpperCase.test(password)) {
                    errors.push("Le mot de passe doit contenir au moins une majuscule");
                }
                if (!this.passwordRequirements.hasLowerCase.test(password)) {
                    errors.push("Le mot de passe doit contenir au moins une minuscule");
                }
                if (!this.passwordRequirements.hasNumbers.test(password)) {
                    errors.push("Le mot de passe doit contenir au moins un chiffre");
                }
                if (!this.passwordRequirements.hasSpecialChar.test(password)) {
                    errors.push("Le mot de passe doit contenir au moins un caractère spécial");
                }
                return errors;
            }

            validateEmail(email) {
                const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return emailRegex.test(email);
            }

            checkPasswordStrength(password) {
                let strength = 0;
                if (password.length >= this.passwordRequirements.minLength) strength++;
                if (this.passwordRequirements.hasUpperCase.test(password)) strength++;
                if (this.passwordRequirements.hasLowerCase.test(password)) strength++;
                if (this.passwordRequirements.hasNumbers.test(password)) strength++;
                if (this.passwordRequirements.hasSpecialChar.test(password)) strength++;
                return (strength / 5) * 100;
            }
        }

        class BruteForceProtection {
            constructor() {
                this.attempts = new Map();
                this.maxAttempts = 5;
                this.lockoutDuration = 15 * 60 * 1000; // 15 minutes
            }

            checkAttempts(email) {
                const now = Date.now();
                const userAttempts = this.attempts.get(email) || { count: 0, timestamp: now };

                if (userAttempts.count >= this.maxAttempts) {
                    const timeDiff = now - userAttempts.timestamp;
                    if (timeDiff < this.lockoutDuration) {
                        const remainingTime = Math.ceil((this.lockoutDuration - timeDiff) / 60000);
                        return {
                            locked: true,
                            message: `Compte temporairement bloqué. Réessayez dans ${remainingTime} minutes.`
                        };
                    } else {
                        this.attempts.delete(email);
                    }
                }
                return { locked: false };
            }

            recordAttempt(email) {
                const now = Date.now();
                const userAttempts = this.attempts.get(email) || { count: 0, timestamp: now };
                userAttempts.count++;
                userAttempts.timestamp = now;
                this.attempts.set(email, userAttempts);
            }

            resetAttempts(email) {
                this.attempts.delete(email);
            }
        }

        const validator = new FormValidator();
        const bruteForceProtection = new BruteForceProtection();

        function toggleForms() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            loginForm.style.display = loginForm.style.display === 'none' ? 'block' : 'none';
            registerForm.style.display = registerForm.style.display === 'none' ? 'block' : 'none';
            document.getElementById('alertContainer').innerHTML = '';
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
        }

        function updatePasswordStrength(password) {
            const strength = validator.checkPasswordStrength(password);
            const strengthBar = document.querySelector('.password-strength');
            strengthBar.style.width = `${strength}%`;
            
            if (strength < 40) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 80) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#198754';
            }

            const requirements = {
                'length-req': password.length >= 8,
                'uppercase-req': /[A-Z]/.test(password),
                'lowercase-req': /[a-z]/.test(password),
                'number-req': /\d/.test(password),
                'special-req': /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            for (const [id, isMet] of Object.entries(requirements)) {
                const element = document.getElementById(id);
                element.className = isMet ? 'requirement-met' : 'requirement-unmet';
                element.querySelector('i').className = isMet ? 'fas fa-check-circle' : 'fas fa-circle';
            }
        }

        document.getElementById('registerPassword').addEventListener('input', function(e) {
            updatePasswordStrength(e.target.value);
        });

        document.getElementById('loginFormElement').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            try {
                const email = document.getElementById('loginEmail').value;
                const password = document.getElementById('loginPassword').value;

                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);

                const response = await fetch('auth_login.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    showAlert('Connexion réussie !', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur de connexion au serveur', 'danger');
            } finally {
                submitButton.disabled = false;
            }
        });

        document.getElementById('registerFormElement').addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('registerEmail').value;
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;
            const nom = document.getElementById('registerNom').value;
            const prenom = document.getElementById('registerPrenom').value;
            const role = document.getElementById('registerRole').value;
            const photoInput = document.getElementById('registerPhoto');

            if (!validator.validateEmail(email)) {
                showAlert("L'email doit être une adresse CHU valide", 'danger');
                return;
            }

            const passwordErrors = validator.validatePassword(password);
            if (passwordErrors.length > 0) {
                showAlert(passwordErrors.join('<br>'), 'danger');
                return;
            }

            if (password !== confirmPassword) {
                showAlert('Les mots de passe ne correspondent pas', 'danger');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);
                formData.append('nom', nom);
                formData.append('prenom', prenom);
                formData.append('role', role);
                
                if (photoInput.files[0]) {
                    formData.append('photo', photoInput.files[0]);
                }

                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Inscription réussie ! Vous allez être redirigé vers la page de connexion.', 'success');
                    setTimeout(() => {
                        toggleForms();
                        this.reset();
                        document.querySelector('.password-strength').style.width = '0';
                    }, 2000);
                } else {
                    showAlert(data.message || "Erreur lors de l'inscription", 'danger');
                }
            } catch (error) {
                showAlert('Erreur de connexion au serveur', 'danger');
            }
        });
    </script>
</body>
</html> 