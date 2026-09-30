#!/bin/sh
set -e

echo "🚀 Starting Hidden-Scan Production Container..."

# Assurer les permissions pour storage et bootstrap/cache
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Créer le lien symbolique public vers storage
php artisan storage:link || true

# Publier et copier les assets Livewire pour une disponibilité statique directe
php artisan livewire:publish --assets || true
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

# Exécuter les migrations
echo "📦 Running database migrations..."
php artisan migrate --force || true

# Initialiser les données de démarrage sans jamais écraser les modifications du panel
echo "🌱 Checking and ensuring initial data (preserves all existing admin modifications)..."
php artisan db:seed --class=ProductionDataSeeder --force || true

# Mettre en cache la configuration, les routes et les vues
echo "⚡ Caching configuration, routes, and views..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Permissions indispensables pour SQLite et le serveur web (évite 'attempt to write a readonly database')
echo "🔒 Applying strict permissions for www-data..."
chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache
if [ -f /var/www/html/database/database.sqlite ]; then
    chmod 664 /var/www/html/database/database.sqlite
fi

echo "✨ Ready! Starting supervisord (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
