#!/usr/bin/env bash
#
# Truncate every table in the application database.
#
# Foreign key checks are disabled for the duration of the run so tables can be
# truncated in any order, then restored to their previous value.
#
# Usage:
#   ./_db/truncate-tables.sh              # asks for confirmation
#   ./_db/truncate-tables.sh --yes        # skip confirmation
#   ./_db/truncate-tables.sh --yes other_database
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-${ROOT_DIR}/.env}"

if [ ! -f "${ENV_FILE}" ]; then
    echo "Error: .env not found at ${ENV_FILE}" >&2
    exit 1
fi

# Read a key from .env, tolerating quotes, spaces and export prefixes.
read_env() {
    sed -n "s/^[[:space:]]*${1}=//p" "${ENV_FILE}" | tail -n 1 \
        | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' \
        -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
}

DB_CONNECTION="$(read_env DB_CONNECTION)"
DB_HOST="$(read_env DB_HOST)"
DB_PORT="$(read_env DB_PORT)"
DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"

if [ "${DB_CONNECTION:-}" != "mysql" ]; then
    echo "Error: this script only supports the mysql connection (found '${DB_CONNECTION:-unset}')" >&2
    exit 1
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-worksphere}"

ASSUME_YES=false
if [ "${1:-}" = "--yes" ] || [ "${1:-}" = "-y" ]; then
    ASSUME_YES=true
    shift
fi

if [ -n "${1:-}" ]; then
    DB_DATABASE="$1"
fi

if [ -z "$(command -v mysql)" ]; then
    echo "Error: the mysql client is not installed or not on PATH" >&2
    exit 1
fi

if ! mysql --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME:-root}" \
    --password="${DB_PASSWORD}" --database="${DB_DATABASE}" \
    --execute="SELECT 1;" >/dev/null 2>&1; then
    echo "Error: cannot connect to '${DB_HOST}:${DB_PORT}' as '${DB_USERNAME:-root}' on database '${DB_DATABASE}'" >&2
    exit 1
fi

TABLES="$(mysql --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME:-root}" \
    --password="${DB_PASSWORD}" --database="${DB_DATABASE}" --batch --skip-column-names \
    --execute="SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME;")"

if [ -z "${TABLES}" ]; then
    echo "No tables found in '${DB_DATABASE}'."
    exit 0
fi

TABLE_COUNT="$(printf '%s\n' "${TABLES}" | wc -l | tr -d ' ')"

if [ "${ASSUME_YES}" != true ]; then
    echo "About to truncate ${TABLE_COUNT} table(s) in database '${DB_DATABASE}' on ${DB_HOST}:${DB_PORT}."
    printf 'This deletes all data. Continue? [y/N] '
    read -r REPLY
    case "${REPLY}" in
        [yY]|[yY][eE][sS]) ;;
        *)
            echo "Aborted."
            exit 1
            ;;
    esac
fi

{
    echo "SET FOREIGN_KEY_CHECKS=0;"
    printf '%s\n' "${TABLES}" | while IFS= read -r table; do
        printf 'TRUNCATE TABLE `%s`;\n' "${table//\`/}"
    done
    echo "SET FOREIGN_KEY_CHECKS=1;"
} | mysql --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME:-root}" \
    --password="${DB_PASSWORD}" --database="${DB_DATABASE}"

echo "Truncated ${TABLE_COUNT} table(s) in '${DB_DATABASE}'."