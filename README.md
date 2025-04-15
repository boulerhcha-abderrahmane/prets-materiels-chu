# Application de Gestion de Prêts de Matériels

Cette application permet de gérer les prêts de matériels au sein d'une organisation.

## Configuration requise
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx)
- XAMPP (recommandé pour l'installation rapide)

## Installation

### 1. Configuration de la base de données

#### Option 1 : Via PHPMyAdmin
1. Démarrez XAMPP Control Panel et activez les services Apache et MySQL
2. Ouvrez PHPMyAdmin dans votre navigateur (http://localhost/phpmyadmin)
3. Créez une nouvelle base de données nommée "prets_materiels"
4. Sélectionnez cette base de données, puis allez dans l'onglet "Importer"
5. Cliquez sur "Parcourir" et sélectionnez le fichier `database.sql`
6. Cliquez sur "Exécuter"

#### Option 2 : Via la ligne de commande
1. Ouvrez une fenêtre de terminal
2. Naviguez vers le répertoire du projet
3. Exécutez la commande :
   ```
   mysql -u root -p < database.sql
   ```
4. Entrez votre mot de passe MySQL si demandé

### 2. Configuration de l'application
1. Créez ou modifiez le fichier `config/config.php` pour y mettre vos paramètres de connexion à la base de données :
   ```php
   <?php
   $host = 'localhost';
   $dbname = 'prets_materiels';
   $username = 'root';
   $password = ''; // Laissez vide si vous utilisez XAMPP avec la configuration par défaut
   
   try {
       $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
   } catch (PDOException $e) {
       die("Erreur de connexion à la base de données: " . $e->getMessage());
   }
   ?>
   ```

### 3. Accès à l'application
1. Accédez à l'application via l'URL : http://localhost/prets_materiels
2. Connectez-vous avec les identifiants par défaut :
   - Email : admin@prets-materiels.fr
   - Mot de passe : admin123

## Structure des dossiers
- `config/` : Fichiers de configuration
- `assets/` : Ressources statiques (CSS, JS, images)
- `includes/` : Composants réutilisables
- `views/` : Pages de l'application
- `uploads/` : Dossier pour les photos de matériels

## Sécurité
- Changez le mot de passe administrateur par défaut après la première connexion
- Assurez-vous que le dossier `uploads/` a les permissions appropriées (755)

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Extensions PHP requises :
  - PDO
  - PDO_MySQL
  - GD
  - mbstring
  - json
  - session

## Installation en Production

1. **Préparation du serveur**
   ```bash
   # Créer le répertoire du projet
   mkdir /chemin/vers/votre/site
   cd /chemin/vers/votre/site
   ```

2. **Cloner le projet**
   ```bash
   git clone https://github.com/votre-username/prets_materiels.git .
   ```

3. **Configuration**
   - Copier `config/config.prod.php` vers `config/config.php`
   - Modifier les paramètres dans `config/config.php` avec vos informations :
     - Informations de la base de données
     - URL du site
     - Configuration SMTP
     - Sel de hachage

4. **Base de données**
   - Créer une base de données MySQL
   - Importer le fichier SQL de structure (à fournir)

5. **Permissions**
   ```bash
   # Définir les permissions des répertoires d'upload
   chmod -R 755 uploads/
   chmod -R 755 uploads/user_photos/
   chmod -R 755 uploads/admin_photos/
   ```

6. **Configuration du serveur web**
   
   ### Apache (.htaccess déjà inclus)
   ```apache
   <IfModule mod_rewrite.c>
   RewriteEngine On
   RewriteBase /
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule . /index.php [L]
   </IfModule>
   ```

   ### Nginx
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$args;
   }
   ```

7. **Sécurité**
   - Vérifier que le mode DEBUG est désactivé dans la configuration
   - S'assurer que les fichiers de configuration sont protégés
   - Mettre en place HTTPS
   - Configurer les en-têtes de sécurité

## Maintenance

- Vérifier régulièrement les logs d'erreur
- Faire des sauvegardes régulières de la base de données
- Mettre à jour régulièrement les dépendances

## Support

Pour toute question ou problème, veuillez créer une issue sur le dépôt GitHub. 