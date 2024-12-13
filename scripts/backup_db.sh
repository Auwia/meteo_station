#!/bin/bash

# Configuration
DB_USER="pi"
DB_PASS="pi_db_meteo"
DB_NAME="meteo"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"  # Absolute path to the script directory
BACKUP_DIR="../backup"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
BACKUP_FILE="$BACKUP_DIR/backup_meteo_db_$TIMESTAMP.sql"
COMPRESSED_FILE="$BACKUP_FILE.gz"  # Compressed backup file
GITHUB_REPO="/home/pi/meteo_station"  # Local path to the GitHub repository

# Step 1: Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Step 2: Remove all old backups in the directory
echo "Cleaning up old backups..."
rm -f "$BACKUP_DIR"/*.gz
if [ $? -eq 0 ]; then
    echo "Old backups removed successfully."
else
    echo "Warning: Failed to remove some old backups."
fi

# Step 3: Export the entire database
echo "Starting database backup..."
if ! mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"; then
    echo "Error: Database backup failed. Check your mysqldump command and credentials."
    exit 1
fi

# Sleep for 15 seconds to ensure disk operations complete
echo "Waiting for disk operations to stabilize..."
sleep 15

# Step 4: Compress the backup file
echo "Compressing the backup file..."
gzip "$BACKUP_FILE"
if [ $? -ne 0 ]; then
    echo "Error: Failed to compress the backup file."
    exit 1
fi
echo "Backup compressed successfully: $COMPRESSED_FILE"

# Step 5: Add compressed backup to GitHub repository
if [ -f "$COMPRESSED_FILE" ]; then
    echo "Adding backup to GitHub repository..."
    cd "$GITHUB_REPO" || exit
    git add "$GITHUB_REPO/backup/$(basename "$COMPRESSED_FILE")"  # Add only the new backup file
    git commit -a -m "Backup on $TIMESTAMP"
    git push origin main
    echo "Backup completed and pushed to GitHub."
else
    echo "Error: Compressed backup file not found. Operation aborted."
    exit 1
fi
