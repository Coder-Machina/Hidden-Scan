#!/bin/sh
set -e

echo "🚀 Starting Hidden-Scan Production Container..."

# Définition et configuration dynamique du port d'écoute Nginx ($PORT fourni par Render, défaut: 80)
PORT="${PORT:-80}"
echo "🌐 Configuring Nginx to bind on port ${PORT}..."
if [ -f /etc/nginx/http.d/default.conf ]; then
    sed -i -E "s/listen [0-9]+;/listen ${PORT};/g" /etc/nginx/http.d/default.conf
    sed -i -E "s/listen \[::\]:[0-9]+;/listen [::]:${PORT};/g" /etc/nginx/http.d/default.conf
fi

# Assurer les répertoires et permissions pour storage et bootstrap/cache
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Créer le lien symbolique public vers storage (en supprimant tout lien résiduel ou cassé)
rm -rf /var/www/html/public/storage
php artisan storage:link --force || true

# Publier et copier les assets Livewire pour une disponibilité statique directe
php artisan livewire:publish --assets --no-interaction || true
mkdir -p /var/www/html/public/livewire
cp -f /var/www/html/public/vendor/livewire/* /var/www/html/public/livewire/ 2>/dev/null || true

# Support SQLite automatique si aucun hôte externe ou URL de connexion n'est fourni
if [ "$DB_CONNECTION" = "sqlite" ] || ([ -z "$DB_HOST" ] && [ -z "$DB_URL" ] && [ -z "$DATABASE_URL" ]); then
    echo "🗄️ Initializing SQLite database fallback..."
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
    fi
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Vérification rapide de connectivité à la base de données sans bloquer le démarrage du service web
echo "🔍 Checking database connectivity..."
DB_READY=0
if php -r '
    require "/var/www/html/vendor/autoload.php";
    $app = require_once "/var/www/html/bootstrap/app.php";
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        exit(0);
    } catch (\Throwable $e) {
        fwrite(STDERR, "Database connection not ready: " . $e->getMessage() . "\n");
        exit(1);
    }
' 2>/dev/null; then
    DB_READY=1
fi

if [ "$DB_READY" = "1" ]; then
    echo "📦 Database connection successful. Running migrations..."
    php artisan migrate --force --no-interaction || true

    MANGA_COUNT=$(php artisan tinker --execute='echo \App\Models\Manga::count();' 2>/dev/null | tail -n 1)
    if [ "$MANGA_COUNT" = "0" ]; then
        echo "🌱 Fresh database detected, seeding initial data..."
        php artisan db:seed --class=ProductionDataSeeder --force --no-interaction || true
    else
        echo "✅ Database already contains data ($MANGA_COUNT mangas)."
    fi
else
    echo "⚠️ Database not immediately reachable (or provisioning in progress), continuing startup to bind port."
fi

# Mettre en cache la configuration, les routes et les vues
echo "⚡ Caching configuration, routes, and views..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Permissions indispensables pour SQLite, le stockage et les fichiers du bot
echo "🔒 Applying strict permissions for www-data..."
mkdir -p /var/www/html/discord-bot/data
chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/discord-bot/data
chmod -R 775 /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/discord-bot/data
if [ -f /var/www/html/database/database.sqlite ]; then
    chmod 664 /var/www/html/database/database.sqlite
fi

echo "✨ Ready! Starting supervisord on port ${PORT} (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
