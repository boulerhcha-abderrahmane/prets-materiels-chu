<?php
session_start();
include '../config/config.php';

// Récupérer l'ID de l'administrateur à déconnecter depuis l'URL
$admin_id_to_logout = isset($_GET['admin_id']) ? $_GET['admin_id'] : null;
$user_id_to_logout = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// Mettre à jour le statut actif de l'administrateur
if ($admin_id_to_logout && isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $admin_id_to_logout) {
    // Mettre à jour le statut dans la base de données
    $sql = "UPDATE administrateur SET actif = 0 WHERE id_admin = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id_to_logout]);

    // Détruire les variables de session
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_nom']);
    unset($_SESSION['admin_prenom']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_logged']);
}

// Mettre à jour le statut actif de l'utilisateur
if ($user_id_to_logout && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id_to_logout) {
    // Mettre à jour le statut dans la base de données
    $sql = "UPDATE utilisateur SET actif = 0 WHERE id_utilisateur = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id_to_logout]);

    // Détruire les variables de session
    unset($_SESSION['user_id']);
    unset($_SESSION['user_type']);
    unset($_SESSION['nom']);
    unset($_SESSION['prenom']);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo a {
            font-family: 'Playfair Display', serif;
            color: #2c3e50;
            text-decoration: none;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .logout-icon {
            font-size: 4rem;
            color: #3498db;
            margin: 1.5rem 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }

        p {
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 2rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-login {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(52, 152, 219, 0.4);
        }

        @media (max-width: 480px) {
            .logout-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .logo a {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logo">
            <a href="#">Gestion de Prêt</a>
        </div>
        <i class="fas fa-sign-out-alt logout-icon"></i>
        <h2>Au revoir !</h2>
        <p>Merci d'avoir utilisé notre plateforme</p>
        <div class="loading-spinner"></div>
        <a href="../index.php" class="btn-login">Retour à la connexion</a>
    </div>

    <script>

    //redirection vers la page de connexion apres 2 secondes
    setTimeout(function() {
        window.location.href = '../index.php';
    }, 3000);
    </script>
</body>
</html>