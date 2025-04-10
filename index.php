<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Prêts Techniques - Solution Professionnelle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <meta name="description" content="Système professionnel de gestion des prêts d'équipements techniques pour ingénieurs et techniciens d'entreprise.">
    
    <style>
        :root {
            --primary-color: #1a4f8b;
            --secondary-color: #2d7dd2;
            --accent-color: #3498db;
            --accent-color-2: #2ecc71;
            --accent-color-3: #f39c12;
            --text-color: #2c3e50;
            --text-light: #ecf0f1;
            --text-muted: #95a5a6;
            --background-light: #f9fafc;
            --background-white: #ffffff;
            --gradient-primary: linear-gradient(135deg, #1a4f8b, #3498db);
            --gradient-accent: linear-gradient(135deg, #2ecc71, #45b17f);
            --gradient-accent-2: linear-gradient(135deg, #f39c12, #f1c40f);
            --box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--background-light);
            min-height: 100vh;
            color: var(--text-color);
            overflow-x: hidden;
            position: relative;
        }

        .bg-shapes::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.1));
            top: -100px;
            right: -100px;
            z-index: -1;
        }

        .bg-shapes::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.08));
            bottom: -80px;
            left: -100px;
            z-index: -1;
        }

        .header {
            background: var(--gradient-primary);
            padding: 8rem 0 10rem;
            position: relative;
            overflow: hidden;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgc2xpY2UiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMTAwIFYgMCBIMTAwIFYgMTAwIEgwIFogTSA1MCA3NSBBIDI1IDI1IDAgMCAwIDc1IDUwIEEgMjUgMjUgMCAwIDAgNTAgMjUgQSAyNSAyNSAwIDAgMCAyNSA1MCBBIDI1IDI1IDAgMCAwIDUwIDc1IFoiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=');
            opacity: 0.2;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 3.8rem;
            margin-bottom: 1.2rem;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
            letter-spacing: 1px;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards 0.3s;
        }

        .header h1::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--accent-color-2);
            margin: 0.8rem auto 0;
            border-radius: 2px;
        }

        .header p {
            font-weight: 400;
            color: rgba(255,255,255,0.9);
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            max-width: 700px;
            margin: 0 auto 1.5rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards 0.6s;
        }

        .main-container {
            position: relative;
            margin-top: -5rem;
            z-index: 5;
            margin-bottom: 5rem;
        }

        .card-container {
            background: var(--background-white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s forwards 0.9s;
        }

        .card-container:hover {
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.1);
            transform: translateY(-10px);
        }

        .card-header {
            background: #f8f9fa;
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .card-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .card-body {
            padding: 3rem;
        }

        .access-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.3rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-radius: 50px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            width: 100%;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .access-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            z-index: -1;
            transition: var(--transition);
        }

        .access-button:hover::before {
            transform: scale(1.1);
            opacity: 0.9;
        }

        .access-button:active {
            transform: scale(0.98);
        }

        .btn-admin {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-user {
            background: var(--gradient-accent);
            color: white;
        }

        .icon {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .dot-separator {
            display: flex;
            justify-content: center;
            margin: 1rem 0;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin: 0 3px;
        }

        .dot-1 {
            background-color: var(--primary-color);
        }

        .dot-2 {
            background-color: var(--secondary-color);
        }

        .dot-3 {
            background-color: var(--accent-color);
        }

        /* Sections supplémentaires */
        .section {
            padding: 5rem 0;
            position: relative;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Section Fonctionnalités */
        .features-section {
            background-color: var(--background-white);
        }

        .feature-card {
            background: var(--background-white);
            border-radius: var(--border-radius-sm);
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            text-align: center;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 1.5rem;
            color: var(--accent-color);
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
        }

        .feature-title {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .feature-description {
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* Section Comment ça marche */
        .how-it-works-section {
            background-color: var(--background-light);
        }

        .step-container {
            display: flex;
            position: relative;
            margin-bottom: 4rem;
        }

        .step-number {
            flex: 0 0 auto;
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            margin-right: 1.5rem;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .step-description {
            color: var(--text-muted);
            line-height: 1.7;
        }

        .step-container::before {
            content: '';
            position: absolute;
            top: 50px;
            left: 25px;
            width: 2px;
            height: calc(100% + 30px);
            background: rgba(52, 152, 219, 0.2);
            z-index: -1;
        }

        .step-container:last-child::before {
            display: none;
        }

        .step-image {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            height: 100%;
        }

        .step-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 992px) {
            .header {
                padding: 6rem 0 8rem;
            }
            
            .header h1 {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 5rem 0 7rem;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .header p {
                font-size: 1.1rem;
            }

            .card-body {
                padding: 2rem;
            }

            .section {
                padding: 4rem 0;
            }

            .section-title {
                font-size: 2rem;
            }

            .feature-card {
                margin-bottom: 2rem;
            }

            .step-container {
                margin-bottom: 3rem;
            }

            .step-image {
                margin-top: 2rem;
            }
        }

        @media (max-width: 576px) {
            .header {
                padding: 4rem 0 6rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .card-header h2 {
                font-size: 1.5rem;
            }
            
            .access-button {
                padding: 1.2rem 1.5rem;
                font-size: 0.9rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .section-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="bg-shapes no-spacing">
    <header class="header">
        <div class="container header-content text-center">
            <h1>Prêts d'Équipements Techniques</h1>
            <p>Solution professionnelle pour la gestion des prêts de matériel spécialisé pour ingénieurs et techniciens.</p>
        </div>
    </header>

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card-container">
                    <div class="card-header">
                        <h2>Espace Technique</h2>
                        <p>Accédez à votre interface selon votre rôle dans l'entreprise</p>
                    </div>
                    <div class="card-body">
                        <a href="views/admin/login.php" class="btn access-button btn-admin" aria-label="Accéder à l'espace Administrateur">
                            <i class="fas fa-user-shield icon"></i>
                            Service Technique
                        </a>
                        
                        <div class="dot-separator">
                            <div class="dot dot-1"></div>
                            <div class="dot dot-2"></div>
                            <div class="dot dot-3"></div>
                        </div>
                        
                        <a href="views/auth/login.php" class="btn access-button btn-user" aria-label="Accéder à l'espace Utilisateur">
                            <i class="fas fa-user icon"></i>
                            Ingénieurs & Techniciens
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Fonctionnalités -->
    <section class="section features-section" id="features">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Nos Fonctionnalités</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Découvrez les avantages de notre système professionnel de gestion des prêts de matériel.</p>
            
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Inventaire en Temps Réel</h3>
                        <p class="feature-description">Suivez la disponibilité de votre matériel à tout moment et identifiez rapidement les équipements disponibles.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="feature-title">Réservation Simplifiée</h3>
                        <p class="feature-description">Processus de réservation intuitif permettant aux utilisateurs de demander du matériel en quelques clics.</p>
                    </div>
                </div>
                
              
                
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Notifications Automatiques</h3>
                        <p class="feature-description">Recevez des alertes pour les retards, les approbations et les retours imminents de matériel.</p>
                    </div>
                </div>
                
                
                
            </div>
        </div>
    </section>

    <!-- Fin du contenu principal -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
