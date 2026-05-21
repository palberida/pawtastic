#!/bin/bash

set -o allexport
source <(grep -v '^#' ".env" | sed -E 's/^\s*//;s/\s*$//') || exit 1
set +o allexport

# Database credentials from the .env file
USER="${DB_USERNAME}"
PASSWORD="${DB_PASSWORD}"
DATABASE="${DB_DATABASE}"
HOST="${DB_HOST:-localhost}"  # Defaults to localhost if DB_HOST is not set

# Backup directory
BACKUP_DIR="/home/pablo.merida"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="${BACKUP_DIR}/backup_${DATABASE}_${DATE}.sql"

# Create backup
mysqldump -u $USER -p$PASSWORD -h $HOST $DATABASE > $BACKUP_FILE

# Optional: Remove backups older than 7 days
find $BACKUP_DIR -type f -name "backup_*.sql" -mtime +7 -exec rm {} \;
