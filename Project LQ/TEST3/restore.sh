#!/bin/sh

# === Настройки ===
DB_USER="user"
DB_PASS="user"
DB_HOST="db"
BACKUP_DIR="/backups"

export MYSQL_PWD="$DB_PASS"

# === Список баз для восстановления ===
DATABASES=("quotidienne" "toutourien" "banco" "astro" "vie")

restore_database() {
  DB_NAME="$1"
  echo "🔍 Поиск последнего бэкапа для базы $DB_NAME..."

  LATEST_BACKUP=$(ls -t "$BACKUP_DIR/${DB_NAME}-"*.sql 2>/dev/null | head -n 1)

  if [ -z "$LATEST_BACKUP" ]; then
    echo "❌ Бэкап не найден для базы $DB_NAME!"
    return 1
  fi

  echo "✅ Найден бэкап: $LATEST_BACKUP"

  echo "⏳ Ожидание доступности MySQL..."
  until mysqladmin ping -h"$DB_HOST" -u"$DB_USER" --silent; do
      sleep 2
  done

  echo "🗄️ Создание базы $DB_NAME (если не существует)..."
  mysql -h "$DB_HOST" -u "$DB_USER" -e "
    CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
  "

  echo "📦 Восстановление базы $DB_NAME из $LATEST_BACKUP..."
  mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$LATEST_BACKUP"

  if [ $? -eq 0 ]; then
    echo "✅ Восстановление базы $DB_NAME завершено: $(date)"
  else
    echo "❌ Ошибка при восстановлении базы $DB_NAME!"
  fi
}

# === Цикл восстановления всех баз ===
for DB in "${DATABASES[@]}"; do
  restore_database "$DB"
done