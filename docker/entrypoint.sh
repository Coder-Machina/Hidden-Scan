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

# Support SQLite automatique si aucun hôte MySQL n'est fourni
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_HOST" ]; then
    echo "🗄️ Initializing SQLite database fallback..."
    mkdir -p /var/www/html/database
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Exécuter les migrations
echo "📦 Running database migrations..."
php artisan migrate --force || true

# Mettre en cache la configuration, les routes et les vues
echo "⚡ Caching configuration, routes, and views..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "✨ Ready! Starting supervisord (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
