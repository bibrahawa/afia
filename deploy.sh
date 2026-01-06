#!/bin/bash
# ---------------------------
# Script de déploiement Laravel LWS
# ---------------------------

# Chemins
APP_PATH="/htdocs/rdvpro"
PUBLIC_PATH="/htdocs/public_html"

# 1. Se placer dans le projet Laravel
cd $APP_PATH || { echo "Impossible de trouver $APP_PATH"; exit 1; }

echo "🔄 Pull depuis Git..."
git pull origin main

echo "📦 Installation des dépendances composer..."
composer install --no-dev --optimize-autoloader

echo "🛠️ Clear et cache Laravel..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Si cette commande existe
php artisan optimize:clear

php artisan migrate --force

# Recréer les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🌐 Synchronisation du dossier public..."
rsync -av --exclude='.git' --exclude='storage' --exclude='index.php' --exclude='.htaccess' public/ $PUBLIC_PATH/

php artisan sms:clean-logs --days=90

echo "✅ Déploiement terminé !"