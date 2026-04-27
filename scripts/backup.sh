#!/usr/bin/env bash
# ProspecCRM — PostgreSQL Backup Script (INFRA-21)
# Cron: 0 2 * * * /root/.openclaw/workspace/main/prospec-crm/scripts/backup.sh

set -euo pipefail

BACKUP_DIR="/root/backups/prospec-crm"
CONTAINER="prospec-crm-postgres"
DB_NAME="prospec_crm"
DB_USER="prospec"
KEEP=7

mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +"%Y-%m-%d_%H%M")
FILENAME="${DB_NAME}_${TIMESTAMP}.sql.gz"
FILEPATH="${BACKUP_DIR}/${FILENAME}"

echo "[backup] Starting backup of ${DB_NAME} at ${TIMESTAMP}..."

docker exec "$CONTAINER" pg_dump -U "$DB_USER" "$DB_NAME" | gzip > "$FILEPATH"

if [ $? -eq 0 ]; then
    SIZE=$(du -h "$FILEPATH" | cut -f1)
    echo "[backup] Success: ${FILENAME} (${SIZE})"

    # Remove backups older than KEEP
    cd "$BACKUP_DIR"
    ls -t ${DB_NAME}_*.sql.gz | tail -n +"$((KEEP + 1))" | xargs -r rm -f
    REMAINING=$(ls -1 ${DB_NAME}_*.sql.gz 2>/dev/null | wc -l)
    echo "[backup] Kept ${REMAINING} backups (max ${KEEP})"
else
    echo "[backup] FAILED — removing incomplete file" >&2
    rm -f "$FILEPATH"
    exit 1
fi