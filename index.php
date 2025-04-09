<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, width=80%">
    <title>Gestion de Prêts - CHU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <meta name="description" content="Gestion de Prêts de Matériel Informatique au CHU. Accédez à l'espace administrateur ou utilisateur.">
    
    <style>
        :root {
            --primary-color: #1a4f8b;
            --secondary-color: #2d7dd2;
            --accent-color: #45b17f;
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            --gradient-accent: linear-gradient(135deg, #45b17f, #6ad4a0);
            --text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            --box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8faff;
            min-height: 100vh;
            color: #2c3e50;
        }

        .header {
            background: var(--gradient-primary);
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            text-shadow: var(--text-shadow);
            letter-spacing: 1px;
            position: relative;
        }

        .header h2 {
            font-weight: 300;
            color: rgba(255,255,255,0.9);
            font-size: 1.5rem;
            letter-spacing: 1px;
        }

        .card {
            border: none;
            border-radius: 15px;
            background: rgba(255,255,255,0.98);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            padding: 3rem !important;
            margin-top: -3rem;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(44, 62, 80, 0.15);
        }

        .btn-custom {
            border: none;
            padding: 1.2rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s ease;
            letter-spacing: 1px;
        }

        .btn-admin {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-user {
            background: var(--gradient-accent);
            color: white;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .icon {
            margin-right: 12px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .header {
                padding: 3.5rem 0;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .header h2 {
                font-size: 1.2rem;
            }
            
            .btn-custom {
                padding: 1.2rem 2.2rem;
                font-size: 1.05rem;
            }
        }
    </style>
</head>
<body>
    <header class="header text-center text-white mb-5">
        <div class="container">
            <h1>Prêts de Matériel Informatique</h1>
            <p class="lead">Bienvenue dans notre système de gestion de prêts. Choisissez votre espace ci-dessous.</p>
        </div>
    </header>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-5">
                    <div class="d-grid gap-4">
                        <a href="views/admin/login.php" class="btn btn-custom btn-admin" aria-label="Accéder à l'espace Administrateur">
                            <i class="fas fa-user-shield icon"></i>
                            Espace Administrateur
                        </a>
                        <a href="views/auth/login.php" class="btn btn-custom btn-user" aria-label="Accéder à l'espace Utilisateur">
                            <i class="fas fa-user icon"></i>
                            Espace Utilisateur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gardons le débogage pour vérification -->
    <div id="debug-info" style="position: fixed; bottom: 0; left: 0; background: white; padding: 10px; border: 1px solid black; max-width: 80%; z-index: 9999;"></div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
    function debugPath(path) {
        // Afficher les informations de débogage détaillées
        const debugInfo = document.getElementById('debug-info');
        const currentPath = window.location.pathname;
        const fullPath = window.location.href;
        const absolutePath = new URL(path, window.location.href).href;
        
        debugInfo.innerHTML = `
            <strong>Informations de débogage détaillées:</strong><br>
            Chemin demandé: ${path}<br>
            Chemin absolu: ${absolutePath}<br>
            Chemin actuel: ${currentPath}<br>
            URL complète: ${fullPath}<br>
            <button onclick="testPath('${path}')">Tester le chemin</button>
            <button onclick="directRedirect('${path}')">Redirection directe</button>
        `;
    }

    function testPath(path) {
        // Tester si le fichier existe avec plus de détails
        fetch(path)
            .then(response => {
                const debugInfo = document.getElementById('debug-info');
                debugInfo.innerHTML += `<br>Status: ${response.status} ${response.statusText}`;
                if (response.ok) {
                    debugInfo.innerHTML += `<br>Le fichier existe! Redirection...`;
                    setTimeout(() => window.location.href = path, 1000);
                } else {
                    debugInfo.innerHTML += `<br>Erreur: Le fichier n'existe pas (${response.status})`;
                }
            })
            .catch(error => {
                const debugInfo = document.getElementById('debug-info');
                debugInfo.innerHTML += `<br>Erreur: ${error}`;
            });
    }

    function directRedirect(path) {
        const debugInfo = document.getElementById('debug-info');
        debugInfo.innerHTML += `<br>Tentative de redirection directe vers: ${path}`;
        window.location.href = path;
    }
    </script>
</body>
</html>
<?php
include 'includes/footer.php';
?>
