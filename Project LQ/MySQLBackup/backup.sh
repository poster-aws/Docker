#!/bin/sh

# Настройки
DB_NAME="quotidienne2"
DB_USER="user"
DB_PASS="user"
DB_HOST="db"
BACKUP_DIR="/backups"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="$BACKUP_DIR/$DB_NAME-$DATE.sql"

# Ожидание доступности MySQL (важно!)
echo "Ожидание доступности MySQL..."
until mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --silent; do
    sleep 2
done

echo "MySQL доступен. Начинаем резервное копирование..."

# Создание резервной копии
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE

echo "Резервное копирование завершено: $BACKUP_FILE"