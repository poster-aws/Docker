#!/bin/sh

# Параметры подключения к базе данных
DB_HOST="db"          # измените при необходимости
DB_NAME="quotidienne2"      # имя базы данных
DB_USER="user"          # имя пользователя
DB_PASS="user"      # пароль

echo "Начинаем выполнение init.sh."

# Выполнение SQL-команд через mysql-клиент
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'EOF'
-- Создаем результирующую таблицу Q2_days
CREATE TABLE IF NOT EXISTS Q2_days (
    Tirage DATE NOT NULL,
    n1 INT NOT NULL,
    n2 INT NOT NULL,
    days INT );

-- Очищаем таблицу перед вставкой данных
TRUNCATE TABLE Q2_days;

-- Вставляем данные с вычислением разницы в днях
INSERT INTO Q2_days (Tirage, n1, n2, days)
SELECT 
    t1.Tirage,
    t1.n1,
    t1.n2,
    COALESCE(
        DATEDIFF(t1.Tirage, (
            SELECT MAX(t2.Tirage)
            FROM Q2 t2
            WHERE t1.n1 = t2.n1 AND t1.n2 = t2.n2 AND t2.Tirage < t1.Tirage
        )), 0
    ) AS days
FROM Q2 t1;

EOF

echo "SQL-команды выполнены."