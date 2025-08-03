#!/bin/sh

# === Настройки ===
DB_USER="user"
DB_PASS="user"
DB_HOST="db"
BACKUP_DIR="/backups"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")

export MYSQL_PWD="$DB_PASS"

# === Функция резервного копирования базы данных ===
backup_database() {
  DB_NAME="$1"
  TABLE_TO_JSON="$2"  # "" если JSON-экспорт не нужен

  echo "📌 Ожидание доступности MySQL для базы $DB_NAME..."
  until mysqladmin ping -h"$DB_HOST" -u"$DB_USER" --silent; do
      sleep 2
  done

  echo "✅ MySQL доступен. Начинаем резервное копирование $DB_NAME..."

  # SQL-дамп
  BACKUP_FILE="$BACKUP_DIR/${DB_NAME}-${DATE}.sql"
  mysqldump --routines --triggers --events --no-tablespaces \
    -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" > "$BACKUP_FILE"

  if [ $? -eq 0 ]; then
    echo "✅ SQL резервная копия $DB_NAME завершена: $BACKUP_FILE"
  else
    echo "❌ Ошибка при создании SQL-резервной копии $DB_NAME"
    return 1
  fi

  # JSON экспорт таблицы (если задано)
  if [ -n "$TABLE_TO_JSON" ]; then
    JSON_FILE="$BACKUP_DIR/${TABLE_TO_JSON}-${DATE}.json"
    TMP_FILE="/tmp/${DB_NAME}_${TABLE_TO_JSON}_export.tsv"
    echo "📦 Экспорт таблицы $TABLE_TO_JSON в JSON: $JSON_FILE"

    mysql -h "$DB_HOST" -u "$DB_USER" -D "$DB_NAME" --batch --skip-column-names \
      -e "SELECT Tirage, n1, n2 FROM $TABLE_TO_JSON;" > "$TMP_FILE"

    if [ $? -eq 0 ]; then
      awk 'BEGIN { print "[" }
      {
          records[NR] = sprintf("  {\n    \"Tirage\": \"%s\",\n    \"n1\": %s,\n    \"n2\": %s\n  }", $1, $2, $3)
      }
      END {
          for (i = 1; i <= NR; i++) {
              printf "%s%s\n", records[i], (i < NR ? "," : "")
          }
          print "]"
      }' "$TMP_FILE" > "$JSON_FILE"

      echo "✅ JSON экспорт завершён: $JSON_FILE"

      # Обновление index.json
      INDEX_FILE="$BACKUP_DIR/index.json"
      echo "{
  \"latest\": \"$(basename "$JSON_FILE")\"
}" > "$INDEX_FILE"
      echo "📄 Создан index.json: $INDEX_FILE"
    else
      echo "⚠️ Ошибка экспорта таблицы $TABLE_TO_JSON из $DB_NAME"
    fi
  fi
}

# === Бэкап базы quotidienne с JSON ===
backup_database "quotidienne" "Q2"

# === Бэкап базы toutourien без JSON ===
backup_database "toutourien" ""