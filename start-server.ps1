# Démarre tout ce qu'il faut pour que l'app mobile Gestion Locative puisse
# contacter le serveur depuis le téléphone (Wi-Fi), sans action manuelle.
#
# IMPORTANT : sans --host=0.0.0.0, "php artisan serve" écoute uniquement sur
# 127.0.0.1 et le téléphone ne peut jamais le contacter, même avec la bonne IP
# côté app -> c'était la cause de "impossible de contacter le serveur".
#
# Ce script est lancé automatiquement à l'ouverture de session Windows
# (tâche planifiée "GestionLocative-Backend") pour que le backend soit
# toujours disponible, même après un redémarrage du PC.

$projectPath = "D:\Desktop\dossiermoibureau\stage travail\Immo_API-master\Immo_API-master"

# 1. S'assurer que MySQL (XAMPP) tourne
if (-not (Get-Process -Name "mysqld" -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" `
        -ArgumentList @('--defaults-file=C:\xampp\mysql\bin\my.ini', '--standalone') `
        -WindowStyle Hidden
    Start-Sleep -Seconds 5
}

# 2. Démarrer le serveur Laravel, accessible depuis tout le réseau local
Set-Location $projectPath
& "C:\xampp\php\php.exe" artisan serve --host=0.0.0.0 --port=8000
