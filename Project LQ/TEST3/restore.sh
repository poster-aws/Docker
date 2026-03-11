#!/bin/sh
DB_USER="user"
DB_PASS="user"
DB_HOST="db"
BACKUP_DIR="/backups"
export MYSQL_PWD="$DB_PASS"

DATABASES=("quotidienne")

restore_database() {
  DB_NAME="$1"
  echo "Recherche du dernier backup pour $DB_NAME..."
  LATEST_BACKUP=$(ls -t "$BACKUP_DIR/${DB_NAME}-"*.sql 2>/dev/null | head -n 1)
  if [ -z "$LATEST_BACKUP" ]; then
    echo "Aucun backup trouvé pour $DB_NAME"
    return 1
  fi
  echo "Backup trouvé: $LATEST_BACKUP"
  until mysqladmin ping -h"$DB_HOST" -u"$DB_USER" --silent; do sleep 2; done
  mysql -h "$DB_HOST" -u "$DB_USER" -e "
    CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$LATEST_BACKUP"
  echo "Restauration de $DB_NAME terminée."
}

for DB in "${DATABASES[@]}"; do
  restore_database "$DB"
done
