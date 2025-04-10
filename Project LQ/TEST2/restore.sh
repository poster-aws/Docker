#!/bin/sh

# Настройки
DB_NAME="quotidienne2"
DB_USER="user"
DB_PASS="user"
DB_HOST="db"
BACKUP_DIR="/backups"

# Находим последний бэкап-файл для этой базы
LATEST_BACKUP=$(ls -t $BACKUP_DIR/$DB_NAME-*.sql | head -n 1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "Ошибка: Файл резервной копии не найден!"
    exit 1
fi
    echo "Обнаружен последний бэкап: $LATEST_BACKUP"

# Ожидание доступности MySQL
    echo "Ожидание доступности MySQL..."
#until mysqladmin ping -h"localhost" --silent; do
until mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --silent; do
    echo "Ожидаем MySQL..."
    sleep 2
done
    echo "MySQL доступен. Восстанавливаем данные..."

# Восстановление данных
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < "$LATEST_BACKUP"

    echo "Восстановление завершено!"