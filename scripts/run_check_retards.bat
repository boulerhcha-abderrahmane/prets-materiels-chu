@echo off
cd /d C:\xampp\htdocs\prets_materiels\scripts
echo ===== Début de l'execution - %date% %time% ===== >> check_retards.log
"C:\xampp\php\php.exe" check_retards.php >> check_retards.log 2>&1
echo ===== Fin de l'execution - %date% %time% ===== >> check_retards.log
echo. >> check_retards.log 