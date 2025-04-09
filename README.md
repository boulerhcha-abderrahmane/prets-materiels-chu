# Système de Prêt de Matériel

Ce projet est une application web de gestion des prêts de matériel.

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