# Déploiement de Hali — de LWS mutualisé au VPS

Ce guide couvre deux étapes de la vie du projet : la mise en ligne sur un **hébergement mutualisé
LWS** (démarrage, une clinique, budget réduit), puis la **migration vers un VPS** quand les limites
du mutualisé se font sentir.

---

## 1. Avant de commencer

| Élément | Valeur à préparer |
| --- | --- |
| Domaine | ex. `app.hali.gn` (sous-domaine dédié) |
| Version PHP | 8.3 (à choisir dans le panneau LWS) |
| Base | MySQL/MariaDB créée depuis le panneau, avec son utilisateur |
| SMS | clé API Nimba et nom d'expéditeur |
| Accès | SSH si votre offre LWS l'inclut, sinon FTP + phpMyAdmin |

Vérifiez dans le panneau LWS que ces extensions PHP sont actives : `pdo_mysql`, `mbstring`,
`openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `zip`, `gd`.

---

## 2. Le piège du mutualisé : la racine web

Sur LWS, le domaine pointe vers `public_html` (ou `www`). Laravel, lui, veut exposer **uniquement**
son dossier `public`. Deux façons de faire, par ordre de préférence :

**A. Sous-domaine pointé sur `public` (recommandé).**
Dans le panneau LWS, créez le sous-domaine et faites-le pointer sur
`/htdocs/hali/public`. Rien d'autre à faire.

**B. Application hors racine, `public_html` en vitrine.**
Placez le code dans `/htdocs/hali` et copiez le contenu de `public/` dans `public_html/`, puis
corrigez les deux chemins de `public_html/index.php` :

```php
require __DIR__.'/../hali/vendor/autoload.php';
$app = require_once __DIR__.'/../hali/bootstrap/app.php';
```

Dans les deux cas, **rien d'autre que `public/` ne doit être accessible par le web** : ni `.env`,
ni `storage`, ni `database`.

---

## 3. Première mise en ligne

### 3.1 Préparer le paquet en local

```bash
composer install --no-dev --optimize-autoloader
npm run build          # si vous compilez des assets
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

Envoyez par FTP (ou `git pull` si SSH) l'ensemble du projet **sans** `node_modules`, `.git`,
`tests`, ni `storage/logs/*`.

### 3.2 Base de données

1. Créez la base et l'utilisateur dans le panneau LWS.
2. Renseignez `.env` (voir §3.3).
3. Avec SSH : `php artisan migrate --force`.
   Sans SSH : exportez le schéma depuis votre poste
   (`php artisan schema:dump`, ou un `mysqldump` de votre base locale vide de données) et importez
   le `.sql` dans phpMyAdmin.

### 3.3 Le fichier `.env`

```dotenv
APP_NAME=Hali
MARQUE_NOM=Hali
MARQUE_SMS_EXPEDITEUR=HALI
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.hali.gn
APP_KEY=                      # php artisan key:generate, ou copiez une clé générée en local

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=xxxxx
DB_USERNAME=xxxxx
DB_PASSWORD=xxxxx

SESSION_DRIVER=database       # le mutualisé n'a ni Redis ni Memcached
CACHE_STORE=database
QUEUE_CONNECTION=database     # voir §4 pour l'exécution des jobs

NIMBA_SMS_API_KEY=xxxxx
NIMBA_SMS_SENDER=HALI

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

`APP_DEBUG=false` n'est pas négociable : en production, une erreur ne doit jamais afficher de
trace ni de requête SQL.

### 3.4 Droits et liens

```bash
chmod -R 775 storage bootstrap/cache
php artisan storage:link        # sans SSH : créez le lien depuis le gestionnaire de fichiers
```

### 3.5 Initialisation applicative

```bash
php artisan db:seed --class="Database\Seeders\PermissionSeeder"
php artisan db:seed --class="Database\Seeders\Assurance\AssurancePermissionsSeeder"
php artisan db:seed --class="Database\Seeders\Parcours\ParcoursPermissionsSeeder"
php artisan db:seed --class="Database\Seeders\Labo\LaboPermissionsSeeder"
php artisan db:seed --class="Database\Seeders\Labo\LaboReseauPermissionsSeeder"
php artisan db:seed --class="Database\Seeders\Rapports\RapportsPermissionsSeeder"
php artisan optimize
```

Créez ensuite l'établissement, son administrateur, les départements, motifs de rendez-vous et
catalogues depuis l'application.

---

## 4. Tâches planifiées et files d'attente sur mutualisé

LWS ne fait pas tourner de worker permanent. Deux adaptations :

**Le planificateur**, dans les tâches cron du panneau, une fois par minute :

```
/usr/local/bin/php /home/xxxx/htdocs/hali/artisan schedule:run >> /dev/null 2>&1
```

**Les jobs** (SMS, PDF de comptes rendus) : plutôt qu'un `queue:work` permanent, une tâche cron
toutes les cinq minutes qui vide la file et s'arrête :

```
/usr/local/bin/php /home/xxxx/htdocs/hali/artisan queue:work --stop-when-empty --max-time=280
```

Si votre offre interdit tout cron, passez `QUEUE_CONNECTION=sync` : les envois se font alors dans
la requête, ce qui ralentit l'écran mais reste fonctionnel.

Rappels de consultations prénatales, une fois par jour :

```
/usr/local/bin/php /home/xxxx/htdocs/hali/artisan hali:rappels-cpn --jours=3
```

---

## 5. Sauvegardes

Sur mutualisé, faites-le vous-même, sans attendre la sauvegarde de l'hébergeur :

```bash
mysqldump -u UTILISATEUR -p BASE | gzip > sauvegarde-$(date +%F).sql.gz
tar czf fichiers-$(date +%F).tar.gz storage/app
```

Gardez au moins 7 sauvegardes quotidiennes et 4 hebdomadaires, hors du serveur (poste local ou
stockage externe). `storage/app` contient les pièces justificatives d'assurance et les PDF.

---

## 6. Mises à jour

Avec SSH, utilisez `scripts/deploy.sh` (voir le fichier, qui met l'application en maintenance,
sauvegarde la base, déploie, migre et vide les caches).

Sans SSH, la même séquence à la main :

1. Sauvegarde base + `storage/app`.
2. Panneau LWS : renommez temporairement `index.php` ou activez une page de maintenance.
3. Envoyez les fichiers modifiés par FTP (les livraisons sont des zips différentiels).
4. Importez les migrations : sans SSH, exécutez le SQL équivalent depuis phpMyAdmin — d'où
   l'intérêt d'avoir SSH dès que possible.
5. Remettez `index.php`, testez un parcours complet.

---

## 7. Limites du mutualisé, et signaux de migration

| Signal | Pourquoi c'est bloquant |
| --- | --- |
| Pas de SSH ni de cron | Migrations et files à la main, rappels SMS impossibles à planifier |
| Un seul processus PHP lent | L'écran de consultation et les PDF deviennent pénibles aux heures de pointe |
| Pas de Redis | Sessions et cache en base, donc charge supplémentaire sur MySQL |
| Quota disque | Les PDF de comptes rendus et pièces justificatives grossissent vite |
| Plusieurs cliniques clientes | Un incident sur le mutualisé les touche toutes en même temps |

Dès que deux de ces signaux sont présents, passez au VPS.

---

## 8. Migration vers un VPS

### 8.1 Cible

Un VPS 2 vCPU / 4 Go suffit pour démarrer avec quelques cliniques : Ubuntu LTS, Nginx, PHP 8.3-FPM,
MySQL 8 ou MariaDB, Redis, Certbot, Supervisor.

### 8.2 Préparer le serveur

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server supervisor certbot python3-certbot-nginx \
    php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath
sudo mysql_secure_installation
```

Créez un utilisateur de déploiement (jamais root pour l'application) et copiez votre clé SSH.

### 8.3 Nginx

```nginx
server {
    listen 80;
    server_name app.hali.gn;
    root /var/www/hali/public;

    index index.php;
    charset utf-8;
    client_max_body_size 12M;          # pièces justificatives d'assurance

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Puis `sudo certbot --nginx -d app.hali.gn` pour le HTTPS.

### 8.4 Transférer les données

```bash
# sur le mutualisé
mysqldump -u UTILISATEUR -p BASE | gzip > hali.sql.gz
tar czf storage-app.tar.gz storage/app

# sur le VPS
gunzip < hali.sql.gz | mysql -u hali -p hali
tar xzf storage-app.tar.gz -C /var/www/hali
```

Faites la bascule pendant une fenêtre calme, application en maintenance des deux côtés, et
laissez l'ancien hébergement en lecture quelques jours.

### 8.5 Ce qui change dans `.env`

```dotenv
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 8.6 Worker permanent (Supervisor)

```ini
[program:hali-worker]
command=php /var/www/hali/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/hali
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/hali-worker.log
```

### 8.7 Cron

```
* * * * * cd /var/www/hali && php artisan schedule:run >> /dev/null 2>&1
```

### 8.8 Après la bascule

```bash
php artisan migrate --force
php artisan optimize
php artisan hali:comptes           # contrôle des soldes après transfert
```

Vérifiez un parcours complet : arrivée d'un patient, consultation, encaissement, impression d'une
ordonnance, envoi d'un SMS, ouverture d'un rapport.

---

## 9. Sécurité, des deux côtés

- `APP_DEBUG=false`, `APP_ENV=production`, aucune page d'erreur détaillée.
- Sauvegardes testées : une sauvegarde jamais restaurée n'est pas une sauvegarde.
- Mots de passe de base longs, différents de ceux du panneau d'hébergement.
- `storage/app` hors du web : il contient des données de santé.
- Sur VPS : pare-feu (`ufw allow 22,80,443`), `fail2ban`, mises à jour de sécurité automatiques.
- Journal d'accès aux dossiers patients : conservé en base, pensez à le sauvegarder aussi.
