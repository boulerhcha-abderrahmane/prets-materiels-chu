<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation réussie</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(30, 74, 117) ;
            --primary-dark:rgb(23, 133, 207);
            --success-color: #059669;
            --background-start: rgb(189, 203, 217);
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

        .success-container {
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
            text-align: center;
        }

        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color),var(--primary-dark) );
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #ecfdf5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            color: var(--success-color);
        }

        .success-title {
            color: #1e293b;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 1rem;
            letter-spacing: -0.025em;
        }

        .success-message {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-primary {
            display: inline-block;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            color: white;
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
            .success-container {
                padding: 2rem;
                margin: 0 1rem;
                border-radius: 16px;
            }

            .success-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="success-title">Mot de passe réinitialisé avec succès !</h1>
            <p class="success-message">
                Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
            </p>
            <a href="../index.php" class="btn btn-primary">Se connecter</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 