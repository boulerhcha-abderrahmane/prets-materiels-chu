<?php
// Inclure la configuration des chemins
include_once './config/paths.php';
?>
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
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.2s ease;
        }

        /* Styles de base */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            min-height: 100vh;
            color: var(--text-color);
            overflow-x: hidden;
            position: relative;
            padding-top: 0;
            line-height: 1.5;
            letter-spacing: 0.1px;
        }

        /* Navigation */
        #mainNav {
            padding: 0.6rem 0;
            transition: var(--transition);
            background: rgba(26, 79, 139, 0.95);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            height: 60px;
        }

        #mainNav.navbar-shrink {
            background: rgba(26, 79, 139, 0.98);
            padding: 0.3rem 0;
        }

        .navbar-brand {
            color: white;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            position: relative;
            padding-left: 0.6rem;
        }

        .navbar-brand::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--accent-color-2);
            border-radius: 2px;
        }

        #mainNav .nav-link {
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.8rem;
            letter-spacing: 0.3px;
            position: relative;
            transition: var(--transition);
        }

        #mainNav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0.4rem;
            left: 0.8rem;
            width: 0;
            height: 2px;
            background-color: var(--accent-color-2);
            transition: width 0.2s ease;
        }

        #mainNav .nav-link:hover::after {
            width: calc(100% - 1.6rem);
        }

        /* Menu mobile styles */
        @media (max-width: 991.98px) {
            #navbarResponsive {
                background-color: rgba(26, 79, 139, 0.98);
                padding: 0.5rem 1rem;
                border-radius: 0 0 10px 10px;
                margin-top: 0.5rem;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            }
            
            #navbarResponsive .navbar-nav {
                padding: 0.5rem 0;
            }
            
            #mainNav .nav-link {
                padding: 0.75rem 0.5rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            #mainNav .nav-link:last-child {
                border-bottom: none;
            }
        }

        /* Header */
        .header {
            background: var(--gradient-primary);
            padding: 0;
            position: relative;
            overflow: hidden;
            clip-path: polygon(0 0, 100% 0, 100% 92%, 0 100%);
            margin-top: 60px;
        }

        .header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgc2xpY2UiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMTAwIFYgMCBIMTAwIFYgMTAwIEgwIFogTSA1MCA3NSBBIDI1IDI1IDAgMCAwIDc1IDUwIEEgMjUgMjUgMCAwIDAgNTAgMjUgQSAyNSAyNSAwIDAgMCAyNSA1MCBBIDI1IDI1IDAgMCAwIDUwIDc1IFoiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=');
            opacity: 0.15;
        }

        .header-content {
            position: relative;
            z-index: 2;
            padding: 3.8rem 0 5.8rem;
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            font-size: 2.5rem;
            margin-bottom: 0.8rem;
            color: #fff;
            text-shadow: 0 2px 8px rgba(0,0,0,0.1);
            letter-spacing: 0.8px;
            position: relative;
            opacity: 0;
            transform: translateY(15px);
            animation: fadeInUp 0.6s forwards 0.2s;
        }

        .header h1::after {
            content: '';
            display: block;
            width: 50px;
            height: 2px;
            background: var(--accent-color-2);
            margin: 0.6rem auto 0;
            border-radius: 2px;
        }

        .header p {
            font-weight: 400;
            color: rgba(255,255,255,0.95);
            font-size: 1.1rem;
            letter-spacing: 0.3px;
            max-width: 650px;
            margin: 0 auto 1.2rem;
            opacity: 0;
            transform: translateY(15px);
            animation: fadeInUp 0.6s forwards 0.4s;
        }

        /* Cartes et conteneurs */
        .card-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s forwards 0.6s;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: -2.5rem;
        }

        .card-container:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: rgba(248, 249, 250, 0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .card-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: var(--primary-color);
            margin-bottom: 0.8rem;
            position: relative;
            display: inline-block;
        }

        .card-header h2::after {
            content: '';
            position: absolute;
            width: 40%;
            height: 2px;
            background: var(--gradient-primary);
            bottom: -6px;
            left: 30%;
            border-radius: 2px;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Boutons */
        .access-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.9rem 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            border-radius: 40px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            width: 100%;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
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
            transform: scale(1.05);
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

        /* Sections */
        .section {
            padding: 4rem 0;
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
            padding: 1.5rem 1.2rem;
            box-shadow: var(--box-shadow);
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
            height: 3px;
            background: var(--gradient-primary);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.06);
        }

        .feature-card:hover::before {
            height: 4px;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
            color: var(--accent-color);
            background-color: rgba(52, 152, 219, 0.08);
            border-radius: 50%;
            position: relative;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.05);
            background-color: rgba(52, 152, 219, 0.12);
        }
        
        .feature-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 1px dashed rgba(52, 152, 219, 0.2);
            top: 0;
            left: 0;
            animation: spin 15s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .feature-title {
            font-size: 1.2rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .feature-description {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 0.9rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-align: center;
            position: relative;
            padding-bottom: 15px;
            font-weight: 600;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
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
                padding: 3.5rem 0 5rem;
            }
            
            .header h1 {
                font-size: 2.3rem;
            }

            .section-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 3rem 0 5rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header p {
                font-size: 1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .section {
                padding: 3rem 0;
            }

            .section-title {
                font-size: 1.6rem;
            }
            
            .feature-icon {
                width: 55px;
                height: 55px;
                font-size: 1.4rem;
            }
            
            .contact-info {
                gap: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding-top: 55px;
            }
            
            .header {
                padding: 2.5rem 0 4rem;
                clip-path: polygon(0 0, 100% 0, 100% 96%, 0 100%);
            }
            
            .header-content {
                padding: 1.8rem 0 3rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
                margin-bottom: 0.3rem;
            }
            
            .header p {
                font-size: 0.9rem;
                margin-bottom: 0.8rem;
                max-width: 100%;
            }
            
            .card-header h2 {
                font-size: 1.3rem;
            }
            
            .card-header {
                padding: 1.2rem;
            }
            
            .card-body {
                padding: 1.2rem;
            }
            
            .access-button {
                padding: 0.8rem 1rem;
                font-size: 0.85rem;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .section-subtitle {
                font-size: 0.9rem;
                margin-bottom: 2rem;
            }
            
            .feature-card {
                padding: 1.5rem 1rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }
            
            .step-number {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }
            
            .step-title {
                font-size: 1.1rem;
            }
            
            .contact-card {
                padding: 1.2rem;
            }
            
            .contact-item i {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }
            
            .contact-item p {
                font-size: 0.9rem;
            }
            
            .step-container {
                margin-bottom: 2rem;
            }
            
            .contact-info {
                gap: 1rem;
            }
        }

        @media (max-width: 1366px) {
            /* Optimisations globales */
            body {
                font-size: 15px;
                line-height: 1.45;
                padding-top: 0;
            }

            /* Optimisations de la navigation */
            #mainNav {
                padding: 0.45rem 0;
                backdrop-filter: blur(4px);
                height: 55px;
            }

            /* Optimisations du header */
            .header {
                margin-top: 55px;
            }

            .header-content {
                padding: 3.3rem 0 4.8rem;
            }
            
            .header h1 {
                font-size: 2rem;
                margin-bottom: 0.5rem;
                letter-spacing: 0.6px;
            }
            
            .header p {
                font-size: 0.92rem;
                max-width: 520px;
                margin-bottom: 0.8rem;
                line-height: 1.5;
            }

            /* Optimisations des cartes principales */
            .card-container {
                margin-top: -1.6rem;
                border-radius: 9px;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.04);
            }

            .card-header {
                padding: 1.2rem;
                background: rgba(248, 249, 250, 0.8);
            }

            .card-header h2 {
                font-size: 1.4rem;
                margin-bottom: 0.6rem;
            }

            .card-body {
                padding: 1.2rem;
            }

            .access-button {
                padding: 0.75rem 1.2rem;
                font-size: 0.82rem;
                letter-spacing: 0.2px;
                border-radius: 35px;
            }

            /* Optimisations des sections */
            .section {
                padding: 2.8rem 0;
            }

            .section-title {
                font-size: 1.85rem;
                margin-bottom: 0.7rem;
                letter-spacing: 0.3px;
            }

            .section-subtitle {
                font-size: 0.92rem;
                margin-bottom: 2rem;
                line-height: 1.5;
            }

            /* Optimisations des cartes de fonctionnalités */
            .feature-card {
                padding: 1.1rem 0.9rem;
                border-radius: 7px;
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
            }
            
            .feature-icon {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
                margin-bottom: 0.7rem;
                background-color: rgba(52, 152, 219, 0.06);
            }
            
            .feature-title {
                font-size: 1.02rem;
                margin-bottom: 0.5rem;
                letter-spacing: 0.2px;
            }
            
            .feature-description {
                font-size: 0.8rem;
                line-height: 1.5;
                letter-spacing: 0.1px;
            }

            /* Optimisations de la section "Comment ça marche" */
            .step-container {
                margin-bottom: 1.5rem;
            }
            
            .step-number {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
                margin-right: 0.7rem;
                box-shadow: 0 3px 8px rgba(52, 152, 219, 0.15);
            }
            
            .step-title {
                font-size: 1.02rem;
                margin-bottom: 0.35rem;
                letter-spacing: 0.2px;
            }
            
            .step-description {
                font-size: 0.8rem;
                line-height: 1.5;
                letter-spacing: 0.1px;
            }

            .step-image {
                border-radius: 7px;
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.04);
            }

            /* Optimisations de la section contact */
            .contact-card {
                padding: 1.1rem;
                border-radius: 9px;
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.04);
            }
            
            .contact-item i {
                width: 30px;
                height: 30px;
                font-size: 0.95rem;
                background-color: rgba(52, 152, 219, 0.06);
            }
            
            .contact-item p {
                font-size: 0.82rem;
                letter-spacing: 0.1px;
            }

            .contact-info {
                gap: 1.1rem;
            }

            /* Optimisations des marges et espacements */
            .row {
                margin-left: -0.4rem;
                margin-right: -0.4rem;
            }

            .col-lg-4, .col-lg-8, .col-md-6, .col-md-12 {
                padding-left: 0.4rem;
                padding-right: 0.4rem;
            }

            /* Optimisations des animations */
            .card-container, .feature-card, .contact-card {
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .card-container:hover {
                transform: translateY(-2.5px);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06);
            }

            .feature-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            }

            .contact-card:hover {
                transform: translateY(-2.5px);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06);
            }

            /* Optimisations des effets de survol */
            .nav-link:hover::after {
                width: calc(100% - 1.4rem);
            }

            .feature-icon:hover {
                transform: scale(1.04);
                background-color: rgba(52, 152, 219, 0.1);
            }

            .contact-item:hover i {
                transform: scale(1.05);
            }

            /* Optimisations des performances */
            .header::before {
                opacity: 0.12;
            }

            .feature-card::before {
                height: 2.5px;
            }

            .contact-card::before {
                height: 2.5px;
            }

            /* Optimisations des images */
            .step-image img {
                transform: scale(1);
                transition: transform 0.3s ease;
            }

            .step-image:hover img {
                transform: scale(1.02);
            }
        }

        /* Styles supplémentaires pour très petits écrans */
        @media (max-width: 375px) {
            .header {
                clip-path: polygon(0 0, 100% 0, 100% 98%, 0 100%);
            }
            
            .header-content {
                padding: 1.2rem 0 2rem;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .header h1::after {
                width: 40px;
                margin: 0.4rem auto 0;
            }
            
            .header p {
                font-size: 0.85rem;
                line-height: 1.4;
            }
            
            .main-container {
                margin-top: -1.5rem;
            }
        }
        
        /* Styles spécifiques pour iPhone 13 */
        @media only screen and (min-width: 376px) and (max-width: 400px) {
            .header {
                clip-path: polygon(0 0, 100% 0, 100% 99%, 0 100%);
                margin-top: 50px;
            }
            
            .header-content {
                padding: 0.8rem 0 1.2rem;
            }
            
            .header h1 {
                font-size: 1.25rem;
                margin-bottom: 0.2rem;
            }
            
            .header p {
                font-size: 0.8rem;
                line-height: 1.3;
                margin-bottom: 0.4rem;
            }
            
            .header h1::after {
                width: 35px;
                height: 1.5px;
                margin: 0.3rem auto 0;
            }
            
            .main-container {
                margin-top: -1rem;
            }
            
            #mainNav {
                height: 50px;
            }
        }

        /* Section Comment ça marche */
        .how-it-works-section {
            background-color: var(--background-light);
        }

        .step-container {
            display: flex;
            position: relative;
            margin-bottom: 2rem;
        }

        .step-number {
            flex: 0 0 auto;
            width: 32px;
            height: 32px;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
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
            opacity: 0.2;
            transform: scale(1.2);
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .step-description {
            color: var(--text-muted);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .step-container::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 22px;
            width: 2px;
            height: calc(100% + 20px);
            background: rgba(52, 152, 219, 0.15);
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
            transform: scale(1.03);
        }

        /* Contact Section */
        .contact-section {
            background-color: var(--background-light);
            padding-bottom: 3rem;
        }

        .contact-card {
            background: var(--background-white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-primary);
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        }

        .contact-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 0.8rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: var(--transition);
        }

        .contact-item:hover {
            transform: translateY(-3px);
        }

        .contact-item i {
            font-size: 1.2rem;
            color: var(--accent-color);
            width: 40px;
            height: 40px;
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
            font-size: 0.95rem;
        }

        /* Ajuster l'espacement entre les éléments de contact sur mobile */
        @media (max-width: 768px) {
            .contact-info {
                gap: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .contact-info {
                gap: 1rem;
            }
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
                            <p class="step-description">Parcourez notre catalogue d'équipements et sélectionnez celui dont vous avez besoin.</p>
                        </div>
                    </div>
                    
                    <div class="step-container">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3 class="step-title">Retournez le matériel</h3>
                            <p class="step-description">Rapportez l'équipement au service technique après la date de retour prévue pour qu'il soit disponible pour les autres utilisateurs.</p>
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
                duration: 600,
                easing: 'ease-in-out',
                once: true
            });
            
            // Handle navbar color change on scroll
            const mainNav = document.getElementById('mainNav');
            function handleScroll() {
                if (window.scrollY > 50) {
                    mainNav.classList.add('navbar-shrink');
                } else {
                    mainNav.classList.remove('navbar-shrink');
                }
            }
            
            window.addEventListener('scroll', handleScroll);
            handleScroll(); // Initialize on page load
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a.nav-link, a.navbar-brand[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    if (targetSection) {
                        window.scrollTo({
                            top: targetSection.offsetTop - 60,
                            behavior: 'smooth'
                        });
                    } else if (targetId === '#page-top') {
                        window.scrollTo({
                            top: 0,
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