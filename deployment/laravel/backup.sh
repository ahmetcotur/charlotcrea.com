#!/bin/sh
# Run with ENV_FILE=/absolute/path/.env and BACKUP_ROOT=/private/backup/path.
set -eu
umask 077
: "${ENV_FILE:?Set ENV_FILE to the deployment environment file}"
: "${BACKUP_ROOT:?Set BACKUP_ROOT to the backup directory}"
script_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
mkdir -p "$BACKUP_ROOT"
backup_dir=$(mktemp -d "$BACKUP_ROOT/charlot-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXX")
compose() { docker compose --env-file "$ENV_FILE" -f "$script_dir/compose.yaml" "$@"; }
compose exec -T database sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' > "$backup_dir/database.dump"
compose exec -T app tar -czf - -C /var/www/html storage/app > "$backup_dir/storage-app.tar.gz"
# The key is needed to decrypt sessions and validate existing signed links.
cp "$ENV_FILE" "$backup_dir/deployment.env"
printf '%s\n' 'Backup finished:' "$backup_dir"
