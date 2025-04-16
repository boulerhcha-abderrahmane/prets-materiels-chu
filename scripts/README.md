# 📋 Scripts de Vérification des Retards

> Système automatisé de vérification et notification des retards de matériels

![Status](https://img.shields.io/badge/Status-Actif-success)
![Version](https://img.shields.io/badge/Version-1.0-blue)
![Schedule](https://img.shields.io/badge/Schedule-10:45%20Daily-orange)

## 📁 Structure des fichiers

| Fichier | Description |
|---------|-------------|
| `check_retards.php` | Script principal de vérification des retards |
| `run_check_retards.bat` | Script batch d'exécution et logging |
| `logs/` | Dossier des fichiers de logs |

## ⚙️ Configuration

### Interface Graphique

1. **Ouvrir le Planificateur de tâches**
   - Menu Démarrer → "planificateur de tâches"

2. **Créer une nouvelle tâche**
   - Panneau de droite → "Créer une tâche"

3. **Configuration Générale** ⚡
   ```
   Nom: VerificationRetardsMateriels
   Description: Vérifie les retards de matériels et envoie les notifications
   Options: 
   ✓ Exécuter que l'utilisateur est connecté ou non
   ✓ Exécuter avec les privilèges les plus élevés
   ```

4. **Configuration des Déclencheurs** 🕒
   ```
   Type: Quotidien
   Heure: 10:45:00
   ```

5. **Configuration des Actions** ▶️
   ```
   Action: Démarrer un programme
   Programme: C:\xampp\htdocs\prets_materiels\scripts\run_check_retards.bat
   ```

6. **Configuration des Conditions** ⚡
   ```
   ❌ Démarrer la tâche uniquement si l'ordinateur est alimenté sur secteur
   ```

7. **Configuration des Paramètres** ⚙️
   ```
   ✓ Exécuter la tâche dès que possible après un démarrage manqué
   ```

### Ligne de Commande

#### 🔧 Création de Tâche

```powershell
# Création basique
schtasks /create /tn "\NomDeLaTache" /tr "chemin\vers\script.bat" /sc daily /st HH:MM /ru SYSTEM

# Création avancée
schtasks /create /tn "\NomDeLaTache" /tr "chemin\vers\script.bat" /sc daily /st HH:MM /ru SYSTEM /rl highest /f
```

#### 📝 Paramètres Principaux

| Paramètre | Description |
|-----------|-------------|
| `/tn` | Nom de la tâche |
| `/tr` | Programme à exécuter |
| `/sc` | Planification (daily/weekly/monthly) |
| `/st` | Heure de début (HH:MM) |
| `/ru` | Compte utilisateur (SYSTEM) |
| `/rl` | Niveau d'exécution (highest) |
| `/f` | Forcer la création |

#### 🔄 Gestion des Tâches

```powershell
# Modification
schtasks /change /tn "\NomDeLaTache" /st HH:MM

# Liste des tâches
schtasks /query /fo list /v

# Exécution manuelle
schtasks /run /tn "\NomDeLaTache"

# Suppression
schtasks /delete /tn "\NomDeLaTache" /f
```

## 📊 Exemple: VerificationRetardsMateriels

### Création
```powershell
schtasks /create /tn "\VerificationRetardsMateriels" /tr "C:\xampp\htdocs\prets_materiels\scripts\run_check_retards.bat" /sc daily /st 10:45 /ru SYSTEM /rl highest /f
```

### Modification
```powershell
schtasks /change /tn "\VerificationRetardsMateriels" /st 10:45
```

### Vérification
```powershell
schtasks /query /tn "\VerificationRetardsMateriels" /fo list /v
```

## 📝 Logs

### Structure
```
logs/
└── check_retards_YYYY-MM-DD.log
```

### Contenu
- Date et heure d'exécution
- Nombre de retards trouvés
- Détails des notifications

## 🔍 Maintenance

### Vérification des Logs
1. Accéder au dossier `logs/`
2. Consulter le fichier du jour
3. Vérifier l'heure d'exécution (10:45)

### Dépannage
1. **Service Planificateur**
   ```powershell
   net start | findstr "Task Scheduler"
   ```

2. **Logs Windows**
   ```powershell
   eventvwr.msc
   ```

3. **Permissions**
   ```powershell
   icacls "chemin\vers\script.bat"
   ```

4. **Test Manuel**
   ```powershell
   cd C:\xampp\htdocs\prets_materiels\scripts
   .\run_check_retards.bat
   ```

## ⭐ Bonnes Pratiques

### 1. Nommage
- ✅ Noms descriptifs
- ✅ Pas d'espaces
- ✅ Préfixe de projet

### 2. Sécurité
- ✅ Compte SYSTEM
- ✅ Niveau d'exécution approprié
- ✅ Vérification des permissions

### 3. Maintenance
- ✅ Documentation
- ✅ Vérification des logs
- ✅ Tests préalables

### 4. Dépannage
- ✅ Logs Windows
- ✅ Tests manuels
- ✅ Vérification des chemins

---

> 💡 **Note**: Pour toute question ou problème, consulter la section maintenance ou contacter l'administrateur système.