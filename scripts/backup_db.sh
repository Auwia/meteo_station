#!/bin/bash

# Configuration
DB_USER="pi"
DB_PASS="pi_db_meteo"
DB_NAME="meteo"
TABLES="temperatures humidities pressures"  # Add your selected tables
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"  # Absolute path to the script directory
BACKUP_DIR="$SCRIPT_DIR/backup"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
BACKUP_FILE="$BACKUP_DIR/backup_meteo_db_$TIMESTAMP.sql"
GITHUB_REPO="/home/pi/meteo_station"  # Local path to the GitHub repository

# Step 1: Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Step 2: Export the database tables
echo "Starting database backup..."
if ! mysqldump -u "$DB_USER" -p"$DB_PASS" --databases "$DB_NAME" --tables $TABLES > "$BACKUP_FILE"; then
    echo "Error: Database backup failed. Check your mysqldump command and credentials."
    exit 1
fi

# Step 3: Add backup to GitHub repository
if [ -f "$BACKUP_FILE" ]; then
    echo "Backup created successfully: $BACKUP_FILE"
    cd "$GITHUB_REPO" || exit
    cp "$BACKUP_FILE" "$GITHUB_REPO/"
    git add .
    git commit -m "Backup on $TIMESTAMP"
    git push origin main
    echo "Backup completed and pushed to GitHub."
else
    echo "Error: Backup file not found. Operation aborted."
    exit 1
fi
