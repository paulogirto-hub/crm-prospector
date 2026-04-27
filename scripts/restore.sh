#!/usr/bin/env bash
# ProspecCRM — PostgreSQL Restore Script (INFRA-21)
# Usage: ./restore.sh <backup_file.sql.gz>

set -euo pipefail

CONTAINER="prospec-crm-postgres"
DB_NAME="prospec_crm"
DB_USER="prospec"

if [ $# -lt 1 ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    echo "Example: $0 /root/backups/prospec-crm/prospec_crm_2025-01-01_0200.sql.gz"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: File not found: $BACKUP_FILE"
    exit 1
fi

echo "================================================"
echo "  WARNING: This will DROP and RECREATE the database!"
echo "  Database: ${DB_NAME}"
echo "  Backup:   ${BACKUP_FILE}"
echo "================================================"
echo ""
read -p "Are you sure? Type 'YES' to continue: " CONFIRM

if [ "$CONFIRM" != "YES" ]; then
    echo "Aborted."
    exit 0
fi

echo "[restore] Terminating existing connections..."
docker exec "$CONTAINER" psql -U "$DB_USER" -d postgres -c \
    "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${DB_NAME}' AND pid <> pg_backend_pid();"

echo "[restore] Dropping database..."
docker exec "$CONTAINER" psql -U "$DB_USER" -d postgres -c "DROP DATABASE IF EXISTS ${DB_NAME};"

echo "[restore] Creating database..."
docker exec "$CONTAINER" psql -U "$DB_USER" -d postgres -c "CREATE DATABASE ${DB_NAME};"

echo "[restore] Restoring from backup..."
gunzip -c "$BACKUP_FILE" | docker exec -i "$CONTAINER" psql -U "$DB_USER" -d "$DB_NAME"

if [ $? -eq 0 ]; then
    echo "[restore] Success! Database restored from ${BACKUP_FILE}"
else
    echo "[restore] WARNING: Restore completed with errors. Check output above." >&2
fi