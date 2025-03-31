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
mysqldump --no-tablespaces -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE

echo "Резервное копирование завершено: $BACKUP_FILE"

# Экспорт таблицы Q2 в JSON
JSON_FILE="$BACKUP_DIR/Q2-$DATE.json"
echo "Экспорт таблицы Q2 в JSON: $JSON_FILE"
TMP_FILE="/tmp/q2_export.tsv"
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -D $DB_NAME --batch --skip-column-names -e "SELECT Tirage, n1, n2 FROM Q2;" > $TMP_FILE

awk 'BEGIN { print "[" }
{
    records[NR] = sprintf("  {\n    \"Tirage\": \"%s\",\n    \"n1\": %s,\n    \"n2\": %s\n  }", $1, $2, $3)
}
END {
    for (i = 1; i <= NR; i++) {
        printf "%s%s\n", records[i], (i < NR ? "," : "")
    }
    print "]"
}' $TMP_FILE > $JSON_FILE
echo "Экспорт завершён: $JSON_FILE"

# Обновление файла index.json
INDEX_FILE="$BACKUP_DIR/index.json"
if [ -f "$INDEX_FILE" ]; then
    rm "$INDEX_FILE"
fi

echo "{
  \"latest\": \"$(basename "$JSON_FILE")\"
}" > "$INDEX_FILE"

echo "Создан index.json: $INDEX_FILE"