# Scripts de Vérification des Retards

Ce dossier contient les scripts nécessaires pour la vérification automatique des retards de matériels.

## Structure des fichiers

- `check_retards.php` : Script principal qui vérifie les retards et envoie les notifications
- `run_check_retards.bat` : Script batch qui exécute le script PHP et enregistre les logs
- `logs/` : Dossier contenant les fichiers de logs des exécutions

## Configuration de la tâche planifiée

Pour configurer l'exécution automatique à 8h00 chaque jour :

1. Ouvrir le Planificateur de tâches Windows (taper "planificateur de tâches" dans le menu Démarrer)
2. Cliquer sur "Créer une tâche" dans le panneau de droite
3. Dans l'onglet "Général" :
   - Nom : "VerificationRetardsMateriels"
   - Description : "Vérifie les retards de matériels et envoie les notifications"
   - Sélectionner "Exécuter que l'utilisateur est connecté ou non"
   - Cocher "Exécuter avec les privilèges les plus élevés"

4. Dans l'onglet "Déclencheurs" :
   - Cliquer sur "Nouveau"
   - Choisir "Quotidien"
   - Définir l'heure de début à 08:00:00
   - Cliquer sur "OK"

5. Dans l'onglet "Actions" :
   - Cliquer sur "Nouveau"
   - Action : "Démarrer un programme"
   - Programme/script : `C:\xampp\htdocs\prets_materiels\scripts\run_check_retards.bat`
   - Cliquer sur "OK"

6. Dans l'onglet "Conditions" :
   - Décocher "Démarrer la tâche uniquement si l'ordinateur est alimenté sur secteur"
   - Cliquer sur "OK"

7. Dans l'onglet "Paramètres" :
   - Cocher "Exécuter la tâche dès que possible après un démarrage manqué"
   - Cliquer sur "OK"

8. Cliquer sur "OK" pour créer la tâche

## Vérification du fonctionnement

Pour vérifier que la tâche est bien configurée :

1. Dans le Planificateur de tâches, vérifier que la tâche "VerificationRetardsMateriels" est présente
2. Vérifier que le statut est "Prêt"
3. Vérifier que le prochain déclenchement est prévu pour 8h00 le lendemain

## Logs

Les logs sont enregistrés dans le dossier `logs/` avec le format suivant :
- Nom du fichier : `check_retards_YYYY-MM-DD.log`
- Contenu : Date et heure d'exécution, nombre de retards trouvés, détails des notifications envoyées

## Maintenance

### Vérification des logs

Pour vérifier les logs :
1. Ouvrir le dossier `logs/`
2. Consulter le fichier du jour ou des jours précédents
3. Vérifier que les exécutions ont bien eu lieu à 8h00

### En cas de problème

Si la tâche ne s'exécute pas :
1. Vérifier que le service "Planificateur de tâches" est bien démarré
2. Vérifier les logs Windows dans l'Observateur d'événements
3. Exécuter manuellement le script `run_check_retards.bat` pour tester

### Après redémarrage du serveur

Si le serveur a été redémarré, il est important de vérifier que la tâche planifiée est bien active :

1. Vérifier le statut de la tâche :
   ```
   schtasks /query /tn "VerificationRetardsMateriels" /fo list
   ```

2. Si la tâche n'est pas listée ou si son statut n'est pas "Prêt", la réactiver :
   ```
   schtasks /create /tn "VerificationRetardsMateriels" /tr "C:\xampp\htdocs\prets_materiels\scripts\run_check_retards.bat" /sc daily /st 08:00 /ru SYSTEM
   ```

3. S'assurer que les services XAMPP sont démarrés :
   ```
   net start mysql
   net start apache
   ```

4. Exécuter manuellement le script pour rattraper les vérifications manquées :
   ```
   cd C:\xampp\htdocs\prets_materiels\scripts
   .\run_check_retards.bat
   ``` 