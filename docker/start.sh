#!/bin/sh
set -e

# Render sets $PORT dynamically - default to 10000 if not set
PORT="${PORT:-10000}"
sed -i "s/PORT_PLACEHOLDER/${PORT}/" /etc/nginx/nginx.conf

# Generate app key if missing (safe no-op if already set)
php artisan key:generate --force || true

# Clear any cached config that might have stale DB settings
php artisan config:clear 2>&1 || true

# Drop orphaned permission tables AND their indexes from failed previous migrations
php artisan tinker --execute="
DB::statement('DROP TABLE IF EXISTS eventhub_role_has_permissions CASCADE');
DB::statement('DROP TABLE IF EXISTS eventhub_model_has_roles CASCADE');
DB::statement('DROP TABLE IF EXISTS eventhub_model_has_permissions CASCADE');
DB::statement('DROP TABLE IF EXISTS eventhub_roles CASCADE');
DB::statement('DROP TABLE IF EXISTS eventhub_permissions CASCADE');
DB::table('migrations')->where('migration','LIKE','%create_permission_tables%')->delete();
// Also drop any orphaned indexes with unprefixed names from previous failed runs
foreach([
    'model_has_permissions_model_id_model_type_index',
    'model_has_permissions_team_foreign_key_index',
    'model_has_permissions_permission_model_type_primary',
    'model_has_roles_model_id_model_type_index',
    'model_has_roles_team_foreign_key_index',
    'model_has_roles_role_model_type_primary',
    'model_has_roles_organization_role_model_type_unique',
    'role_has_permissions_permission_id_role_id_primary',
    'roles_team_foreign_key_index',
] as \$idx) {
    DB::statement('DROP INDEX IF EXISTS '.\$idx);
}
" 2>&1 || true

# Run database migrations FIRST (before caching config)
php artisan migrate --force 2>&1

# Cache config/routes/views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage/bootstrap dirs are owned by the php-fpm user
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start php-fpm + nginx via supervisor
exec supervisord -c /etc/supervisord.conf
