<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Prêts Techniques - Solution Professionnelle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <meta name="description" content="Système professionnel de gestion des prêts d'équipements techniques pour ingénieurs et techniciens d'entreprise.">
    
    <style>
        /* Variables globales */
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
            --border-radius: 20px;
            --border-radius-sm: 12px;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        /* Styles de base */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            min-height: 100vh;
            color: var(--text-color);
            overflow-x: hidden;
            position: relative;
            padding-top: 72px;
            line-height: 1.7;
            letter-spacing: 0.2px;
        }

        /* Effets communs */
        .glass-effect {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        /* Navigation */
        #mainNav {
            padding: 1rem 0;
            transition: var(--transition);
            background: rgba(26, 79, 139, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #mainNav.navbar-shrink {
            background: rgba(26, 79, 139, 0.98);
            padding: 0.5rem 0;
        }

        .navbar-brand {
            color: white;
            font-weight: 700;
            letter-spacing: 0.5px;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            position: relative;
            padding-left: 0.75rem;
        }

        .navbar-brand::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: var(--accent-color-2);
            border-radius: 2px;
        }

        #mainNav .nav-link {
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.75rem 1rem;
            letter-spacing: 0.5px;
            position: relative;
            transition: var(--transition);
        }

        #mainNav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0.5rem;
            left: 1rem;
            width: 0;
            height: 2px;
            background-color: var(--accent-color-2);
            transition: width 0.3s ease;
        }

        #mainNav .nav-link:hover::after {
            width: calc(100% - 2rem);
        }

        /* Header */
        .header {
            background: var(--gradient-primary);
            padding: 9rem 0 11rem;
            position: relative;
            overflow: hidden;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }

        .header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgc2xpY2UiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMTAwIFYgMCBIMTAwIFYgMTAwIEgwIFogTSA1MCA3NSBBIDI1IDI1IDAgMCAwIDc1IDUwIEEgMjUgMjUgMCAwIDAgNTAgMjUgQSAyNSAyNSAwIDAgMCAyNSA1MCBBIDI1IDI1IDAgMCAwIDUwIDc1IFoiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=');
            opacity: 0.2;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 4rem;
            margin-bottom: 1.2rem;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.15);
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
            margin: 1rem auto 0;
            border-radius: 2px;
        }

        .header p {
            font-weight: 400;
            color: rgba(255,255,255,0.95);
            font-size: 1.35rem;
            letter-spacing: 0.5px;
            max-width: 700px;
            margin: 0 auto 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards 0.6s;
        }

        /* Cartes et conteneurs */
        .card-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s forwards 0.9s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-container:hover {
            box-shadow: 0 25px 65px rgba(0, 0, 0, 0.1);
            transform: translateY(-15px);
        }

        .card-header {
            background: rgba(248, 249, 250, 0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            padding: 2.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .card-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .card-header h2::after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background: var(--gradient-primary);
            bottom: -8px;
            left: 25%;
            border-radius: 2px;
        }

        /* Boutons */
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
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .access-button::before {
            content: '';
            position: absolute;
            inset: 0;
            background: inherit;
            z-index: -1;
            transition: var(--transition);
        }

        .access-button:hover::before {
            transform: scale(1.1);
            opacity: 0.9;
        }

        .access-button:active {
            transform: scale(0.97);
        }

        .btn-admin {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-user {
            background: var(--gradient-accent);
            color: white;
        }

        /* Sections */
        .section {
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
        }

        /* Section Fonctionnalités */
        .features-section {
            background-color: var(--background-white);
            position: relative;
        }

        .feature-card {
            background: var(--background-white);
            border-radius: var(--border-radius-sm);
            padding: 3rem 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.03);
            transform: translateY(0);
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
            transform: translateY(-15px);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08);
        }

        .feature-card:hover::before {
            height: 6px;
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            margin: 0 auto 2rem;
            color: var(--accent-color);
            background-color: rgba(52, 152, 219, 0.08);
            border-radius: 50%;
            position: relative;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            background-color: rgba(52, 152, 219, 0.12);
        }
        
        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 1px dashed rgba(52, 152, 219, 0.3);
            top: 0;
            left: 0;
            animation: spin 20s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .feature-title {
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .feature-description {
            color: var(--text-muted);
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.7rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-align: center;
            position: relative;
            padding-bottom: 20px;
            font-weight: 700;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 3px;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.15rem;
            color: var(--text-muted);
            margin-bottom: 4rem;
            max-width: 750px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
        }

        /* Animations */
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Media Queries */
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
            position: relative;
            z-index: 2;
        }

        .step-number::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            z-index: -1;
            opacity: 0.3;
            transform: scale(1.3);
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
            position: relative;
        }

        .step-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.1));
            z-index: 1;
            opacity: 0;
            transition: var(--transition);
        }

        .step-image:hover::before {
            opacity: 1;
        }

        .step-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .step-image:hover img {
            transform: scale(1.05);
        }

        /* Contact Section */
        .contact-section {
            background-color: var(--background-light);
        }

        .contact-card {
            background: var(--background-white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2.5rem;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        /* Styles pour la section À propos */
        .about-section {
            background-color: var(--background-white);
            position: relative;
            overflow: hidden;
        }
        
        .about-section::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(52, 152, 219, 0.05) 0%, transparent 70%);
            z-index: 0;
        }
        
        .about-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(46, 204, 113, 0.05) 0%, transparent 70%);
            z-index: 0;
        }
        
        .about-content {
            position: relative;
            z-index: 1;
        }
        
        .about-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }
        
        .about-content h3::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: var(--gradient-primary);
            bottom: -10px;
            left: 0;
            border-radius: 2px;
        }
        
        .about-content p {
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .about-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .about-feature {
            display: flex;
            align-items: center;
            font-size: 1rem;
            color: var(--text-color);
            transition: var(--transition);
        }
        
        .about-feature:hover {
            transform: translateX(5px);
            color: var(--primary-color);
        }
        
        .about-image {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transform: perspective(1000px) rotateY(-5deg);
            transition: var(--transition);
        }
        
        .about-image:hover {
            transform: perspective(1000px) rotateY(0);
        }
        
        .about-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.1));
            z-index: 1;
            opacity: 0;
            transition: var(--transition);
        }
        
        .about-image:hover::before {
            opacity: 1;
        }
        
        @media (max-width: 992px) {
            .about-image {
                margin-top: 2rem;
                transform: perspective(1000px) rotateY(0);
            }
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient-primary);
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.1);
        }

        .contact-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2.5rem;
            margin-top: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .contact-item:hover {
            transform: translateY(-5px);
        }

        .contact-item i {
            font-size: 1.5rem;
            color: var(--accent-color);
            width: 50px;
            height: 50px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .contact-item:hover i {
            background-color: var(--accent-color);
            color: white;
        }

        .contact-item p {
            margin: 0;
            font-weight: 500;
            color: var(--text-color);
            font-size: 1.1rem;
        }
        
        /* Nouveaux styles pour améliorer l'élégance */
        .dot-separator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .dot-separator::before,
        .dot-separator::after {
            content: '';
            height: 1px;
            flex: 1;
            background: linear-gradient(to right, transparent, rgba(0,0,0,0.1), transparent);
        }
        
        .dot-separator::before {
            margin-right: 1rem;
        }
        
        .dot-separator::after {
            margin-left: 1rem;
        }
        
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent-color);
            margin: 0 3px;
            opacity: 0.7;
            animation: pulse 2s infinite;
        }
        
        .dot-1 {
            animation-delay: 0s;
        }
        
        .dot-2 {
            animation-delay: 0.3s;
        }
        
        .dot-3 {
            animation-delay: 0.6s;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
        }
        
        .icon {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        /* Effet de particules en arrière-plan */
        .bg-shapes {
            position: relative;
        }
        
        .bg-shapes::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(52, 152, 219, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(46, 204, 113, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 50% 50%, rgba(243, 156, 18, 0.03) 0%, transparent 30%);
            z-index: -1;
            pointer-events: none;
        }
        
        /* Effet de décoration pour le header */
        .deco-1, .deco-2, .deco-3 {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            z-index: 1;
        }
        
        .deco-1 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(46, 204, 113, 0.2) 0%, transparent 70%);
            top: -100px;
            right: -100px;
        }
        
        .deco-2 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(52, 152, 219, 0.2) 0%, transparent 70%);
            bottom: -50px;
            left: -50px;
        }
        
        .deco-3 {
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(243, 156, 18, 0.2) 0%, transparent 70%);
            top: 50%;
            right: 10%;
        }
        
        /* Effet de survol pour les cartes de fonctionnalités */
        .feature-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05), rgba(46, 204, 113, 0.05));
            z-index: -1;
            opacity: 0;
            transition: var(--transition);
        }
        
        .feature-card:hover::after {
            opacity: 1;
        }
        
        /* Effet de survol pour les étapes */
        .step-container:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
        }
        
        /* Effet de survol pour les éléments de contact */
        .contact-item:hover p {
            color: var(--accent-color);
        }
    </style>
</head>
<body class="bg-shapes no-spacing">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="#page-top">PrêtsMatériels</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars ms-1"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto py-4 py-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#about">À propos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Fonctionnalités</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">Processus</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="header">
        <div class="container header-content text-center">
            <h1>Prêts d'Équipements Techniques</h1>
            <p>Solution professionnelle pour la gestion des prêts de matériel spécialisé pour ingénieurs et techniciens.</p>
        </div>
        <div class="deco-1"></div>
        <div class="deco-2"></div>
        <div class="deco-3"></div>
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

    <!-- Section À propos -->
    <section class="section about-section" id="about">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">À propos de PrêtsMatériels</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Une plateforme innovante pour optimiser la gestion de vos équipements techniques.</p>
            
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
                    <div class="about-content">
                        <h3 class="mb-4">Notre Mission</h3>
                        <p>PrêtsMatériels est né de la volonté d'optimiser la gestion des équipements techniques au sein des entreprises d'ingénierie et des services techniques. Notre plateforme permet de suivre avec précision le cycle de prêt du matériel, réduisant ainsi les coûts liés aux pertes et optimisant l'utilisation des ressources.</p>
                        
                        <div class="about-features mt-4">
                            <div class="about-feature">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                <span>Gestion centralisée des ressources matérielles</span>
                            </div>
                            <div class="about-feature">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                <span>Suivi précis des prêts et retours</span>
                            </div>
                            <div class="about-feature">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                <span>Optimisation de l'utilisation des équipements</span>
                            </div>
                            <div class="about-feature">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                <span>Réduction des pertes et des coûts associés</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
                    <div class="about-image">
                        <img src="assets/images/about.svg" alt="À propos de PrêtsMatériels" onerror="this.src='https://via.placeholder.com/600x400?text=Notre+Solution'" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    <!-- Section Comment ça marche -->
    <section class="section how-it-works-section" id="how-it-works">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Comment ça marche</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Découvrez notre processus simple pour emprunter le matériel technique dont vous avez besoin.</p>
            
            <div class="row">
                <div class="col-lg-7 col-md-12" data-aos="fade-right" data-aos-delay="200">
                    <div class="step-container">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3 class="step-title">Connectez-vous à votre compte</h3>
                            <p class="step-description">Accédez à votre espace personnel en tant qu'ingénieur ou technicien pour consulter l'inventaire disponible.</p>
                        </div>
                    </div>
                    
                    <div class="step-container">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3 class="step-title">Sélectionnez votre matériel</h3>
                            <p class="step-description">Parcourez notre catalogue d'équipements et sélectionnez celui dont vous avez besoin pour votre projet.</p>
                        </div>
                    </div>
                    
                    <div class="step-container">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3 class="step-title">Réservez et récupérez</h3>
                            <p class="step-description">Réservez l'équipement pour la période souhaitée et récupérez-le auprès du service technique.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-5 col-md-12" data-aos="fade-left" data-aos-delay="300">
                    <div class="step-image">
                        <img src="assets/images/process.svg" alt="Processus d'emprunt de matériel" onerror="this.src='https://via.placeholder.com/500x600?text=Processus'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Contactez-nous -->
    <section class="section contact-section" id="contact">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Besoin d'aide ?</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Notre équipe technique est disponible pour répondre à vos questions sur le système de prêt.</p>
            
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-card">
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <p>gestion_prets@gmail.com</p>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <p>0766662217</p>
                            </div>
                        </div>
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
            
            // Handle navbar color change on scroll
            const mainNav = document.getElementById('mainNav');
            function handleScroll() {
                if (window.scrollY > 70) {
                    mainNav.classList.add('navbar-shrink');
                } else {
                    mainNav.classList.remove('navbar-shrink');
                }
            }
            
            window.addEventListener('scroll', handleScroll);
            handleScroll(); // Initialize on page load
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a.nav-link').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    if (targetSection) {
                        window.scrollTo({
                            top: targetSection.offsetTop - 70,
                            behavior: 'smooth'
                        });
                    }
                    
                    // Close mobile menu
                    const navbarToggler = document.querySelector('.navbar-toggler');
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    
                    if (navbarCollapse.classList.contains('show')) {
                        navbarToggler.click();
                    }
                });
            });
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
