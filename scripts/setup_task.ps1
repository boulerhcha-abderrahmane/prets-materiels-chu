# Définir les variables
$taskName = "VerificationRetardsMateriels"
$taskDescription = "Vérifie les retards de matériels et envoie les notifications à 8h du matin"
$scriptPath = "C:\xampp\htdocs\prets_materiels\scripts\run_check_retards.bat"

# Créer la tâche planifiée
$action = New-ScheduledTaskAction -Execute $scriptPath
$trigger = New-ScheduledTaskTrigger -Daily -At 8AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Enregistrer la tâche
Register-ScheduledTask -TaskName $taskName -Description $taskDescription -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force

Write-Host "Tâche planifiée '$taskName' créée avec succès!"
Write-Host "Le script s'exécutera chaque jour à 8h du matin." 