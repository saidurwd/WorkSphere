#!/usr/bin/env bash
#
# Seed the database with a complete, demonstrable dataset.
#
# Migrates if needed, then runs every seeder in `database/seeders`. The result is
# a full organisation: twelve departments, ten locations, a named roster of
# employees with accounts and roles, and work volume in tasks, meetings,
# obligations and to-dos. Volume is configurable — see config/seed.php.
#
# Usage:
#   ./_db/seed-demo-data.sh                 # migrate if needed, then seed
#   ./_db/seed-demo-data.sh --fresh         # drop everything and rebuild
#   ./_db/seed-demo-data.sh --fresh --yes   # no confirmation prompt
#   ./_db/seed-demo-data.sh --class=Database\Seeders\TaskSeeder
#
# Every seeded account shares one password, printed by UserSeeder. It defaults
# to `password`; set SEED_DEMO_PASSWORD to change it.
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

FRESH=false
ASSUME_YES=false
CLASS=""

while [ "$#" -gt 0 ]; do
    case "$1" in
        --fresh) FRESH=true; shift ;;
        --yes|-y) ASSUME_YES=true; shift ;;
        --class=*) CLASS="${1#*=}"; shift ;;
        -h|--help) sed -n '/^# Usage:/,/^#$/p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *)
            echo "Unknown option: $1 (try --help)" >&2
            exit 1
            ;;
    esac
done

if [ ! -f .env ]; then
    echo "Error: .env not found in ${ROOT_DIR}" >&2
    exit 1
fi

if [ ! -d vendor ]; then
    echo "Error: dependencies are not installed. Run 'composer install' first." >&2
    exit 1
fi

read_env() {
    sed -n "s/^[[:space:]]*${1}=//p" .env | tail -n 1 \
        | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' \
        -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
}

DB_CONNECTION="$(read_env DB_CONNECTION)"
DB_DATABASE="$(read_env DB_DATABASE)"

if [ "${DB_CONNECTION}" != "mysql" ]; then
    echo "Error: the seeders expect a mysql connection (found '${DB_CONNECTION:-unset}')" >&2
    exit 1
fi

if [ "${FRESH}" = true ] && [ "${ASSUME_YES}" != true ]; then
    echo "This will DROP every table in '${DB_DATABASE}' and rebuild it from the migrations."
    printf 'Every row in the database will be lost. Continue? [y/N] '
    read -r REPLY
    case "${REPLY}" in
        [yY]|[yY][eE][sS]) ;;
        *)
            echo "Aborted."
            exit 1
            ;;
    esac
fi

if [ "${FRESH}" = true ]; then
    echo "==> Rebuilding the schema"
    php artisan migrate:fresh --force --no-interaction
else
    echo "==> Applying any pending migrations"
    php artisan migrate --force --no-interaction
fi

echo "==> Seeding"
if [ -n "${CLASS}" ]; then
    php artisan db:seed --class="${CLASS}" --force --no-interaction
else
    php artisan db:seed --force --no-interaction
fi

echo
echo "==> Row counts"
php artisan tinker --execute='
$tables = [
    "users", "employees", "departments", "locations", "companies", "vendors",
    "roles", "permissions", "role_permissions", "user_roles",
    "task_projects", "tasks", "time_entries", "task_watchers", "task_transfers",
    "task_remarks", "comments",
    "meetings", "meeting_agendas", "meeting_action_items", "meeting_participants",
    "meeting_decisions", "meeting_templates", "meeting_recurrences",
    "obligations", "obligation_responsibilities", "obligation_documents",
    "obligation_renewals", "obligation_activity_logs", "notification_logs",
    "todos", "todo_checklist_items", "todo_watchers", "todo_links", "tags",
    "activity_logs", "login_logs", "tyro_audit_logs", "notifications",
    "notification_preferences", "reminders", "settings", "feature_flags",
];
foreach ($tables as $table) {
    if (! Schema::hasTable($table)) {
        continue;
    }
    printf("  %-30s %8d\n", $table, DB::table($table)->count());
}
'