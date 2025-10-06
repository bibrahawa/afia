#!/bin/bash

# ---------------------------
# Script de déploiement Laravel LWS
# ---------------------------

# Chemins
APP_PATH="/htdocs/rdvpro"
PUBLIC_PATH="/htdocs/rdv.aprosafe.com"

# 1. Se placer dans le projet Laravel
cd $APP_PATH || { echo "Impossible de trouver $APP_PATH"; exit 1; }

echo "🔄 Pull depuis Git..."
git pull origin main

echo "📦 Installation des dépendances composer..."
composer install --no-dev --optimize-autoloader
php artisan migrate --force

echo "🛠️ Clear et cache Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

echo "🌐 Synchronisation du dossier public..."
rsync -av --exclude='.git' --exclude='storage' public/ $PUBLIC_PATH/

echo "✅ Déploiement terminé !"
