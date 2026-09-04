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

# Backfill cover_url for events that don't have one yet
php artisan tinker --execute="
\$events = [
    'Neon Frequencies' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=1200&h=700&fit=crop&auto=format',
    'Arctic Monkeys: The Comedown Machine Tour' => 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=1200&h=700&fit=crop&auto=format',
    'TechSummit 2026' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&h=700&fit=crop&auto=format',
    'Champions League Final 2026' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=1200&h=700&fit=crop&auto=format',
    'Dave Chappelle: Midnight Return' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=700&fit=crop&auto=format',
    'Sakura Cultural Festival' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1200&h=700&fit=crop&auto=format',
    'Jazz Under the Stars' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1200&h=700&fit=crop&auto=format',
    'Global Founders Summit' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1200&h=700&fit=crop&auto=format',
    'Design Systems Workshop' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=1200&h=700&fit=crop&auto=format',
    'Midnight Techno: Warehouse Sessions' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1200&h=700&fit=crop&auto=format',
    'Afrobeats & Afrofusion Night' => 'https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=1200&h=700&fit=crop&auto=format',
    'World Street Food Championship' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&h=700&fit=crop&auto=format',
];
\$count = 0;
foreach (\$events as \$title => \$url) {
    \$updated = DB::table('events')->where('title', \$title)->whereNull('cover_url')->update(['cover_url' => \$url]);
    \$count += \$updated;
}
echo \"Backfilled cover_url for {\$count} events\\n\";
" 2>&1 || true

# Cache config/routes/views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage/bootstrap dirs are owned by the php-fpm user
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start php-fpm + nginx via supervisor
exec supervisord -c /etc/supervisord.conf
