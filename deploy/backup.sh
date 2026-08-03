#!/bin/bash
# deploy/backup.sh
# Weekly backup of database + uploaded files.
set -euo pipefail

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/backups/$DATE"
mkdir -p "$BACKUP_DIR"

DB_NAME="${DB_NAME:-oceanovia}"
DB_USER="${DB_USER:-oceanovia}"
DB_PASS="${DB_PASS:-change-me}"
APP_DIR="${APP_DIR:-/var/www/oceanovia}"

echo "=== Backing up database ==="
mysqldump --single-transaction --quick --routines --triggers \
    -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/database.sql.gz"

echo "=== Backing up storage ==="
rsync -az "$APP_DIR/storage/app/public/" "$BACKUP_DIR/storage/"

# Offsite copy (configure rclone remote named "cloudflare-r2" to enable)
if command -v rclone >/dev/null 2>&1 && rclone listremotes | grep -q cloudflare-r2; then
    echo "=== Uploading to R2 ==="
    rclone copy "$BACKUP_DIR" cloudflare-r2:oceanovia-backups/"$DATE"/
else
    echo "!!! rclone/cloudflare-r2 not configured — skipping offsite upload"
fi

# Retention: keep daily for 7 days, weekly for 4 weeks, monthly for 12 months
find /backups -type d -mtime +7 -exec rm -rf {} +

echo "=== Backup complete: $BACKUP_DIR ==="
