import mysql.connector
from datetime import datetime
import itertools

# === Настройки подключения к БД ===
DB_CONFIG = {
    "host": "db",          # если запускаешь на хосте → "127.0.0.1"
    "user": "user",
    "password": "user",
    "database": "banco",
    "charset": "utf8mb4"
}

print("🔄 Подключаемся к базе данных banco...")
conn = mysql.connector.connect(**DB_CONFIG)
cur = conn.cursor()

# === 1. Загружаем все тиражи с числами ===
print("📥 Загружаем таблицу banco...")
cur.execute("""
    SELECT Tirage,
           n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,
           n11,n12,n13,n14,n15,n16,n17,n18,n19,n20
    FROM banco
    ORDER BY Tirage
""")
tirages = cur.fetchall()
print(f"✅ Загружено {len(tirages)} тиражей.")

# === 2. Определяем дату последнего тиража (для расчёта 'jours passés') ===
cur.execute("SELECT MAX(Tirage) FROM banco;")
last_global_date = cur.fetchone()[0]
print(f"📅 Последний тираж в базе: {last_global_date}")

# === 3. Формируем все комбинации (n1 < n2) ===
pairs = {}  # (n1,n2) -> [список дат]
print("🧮 Формируем комбинации...")
for row in tirages:
    date = row[0]
    nums = [int(x) for x in row[1:] if x is not None]
    for a, b in itertools.combinations(sorted(nums), 2):
        n1, n2 = sorted((a, b))
        key = (n1, n2)
        if key not in pairs:
            pairs[key] = []
        pairs[key].append(date)

print(f"✅ Обнаружено {len(pairs)} уникальных комбинаций.")

# === 4. Подготовка SQL для обновления comb2 ===
update_sql = """
    UPDATE comb2
    SET Tirage = %s,
        days   = %s,
        days2  = %s,
        fois   = %s,
        max    = %s
    WHERE (n1=%s AND n2=%s) OR (n1=%s AND n2=%s)
"""

print("🧩 Вычисляем статистику и обновляем comb2...")

updated = 0
for (a, b), dates in pairs.items():
    dates = sorted(dates)
    fois = len(dates)

    # Разрывы между последовательными появлениями
    gaps = []
    for i in range(1, len(dates)):
        d1, d2 = dates[i - 1], dates[i]
        if isinstance(d1, datetime):
            diff = (d2 - d1).days
        else:
            diff = (datetime.strptime(str(d2), "%Y-%m-%d") -
                    datetime.strptime(str(d1), "%Y-%m-%d")).days
        gaps.append(diff)

    # Статистика промежутков
    max_gap = max(gaps) if gaps else 0
    days2 = gaps[-2] if len(gaps) >= 2 else 0

    # Разница между последним выпадением и последним тиражом
    last_date = dates[-1]
    if isinstance(last_date, datetime):
        days = (last_global_date - last_date).days
    else:
        days = (datetime.strptime(str(last_global_date), "%Y-%m-%d") -
                datetime.strptime(str(last_date), "%Y-%m-%d")).days

    # Обновление записи в comb2
    cur.execute(update_sql, (
        last_date, days, days2, fois, max_gap, a, b, b, a
    ))

    updated += 1
    if updated % 100 == 0:
        conn.commit()
        print(f"  ⏩ Обновлено {updated} комбинаций...")

conn.commit()
print(f"✅ Готово. Обновлено {updated} комбинаций в comb2.")

# === 5. Проверка результата ===
cur.execute("SELECT COUNT(*) FROM comb2 WHERE fois > 0")
seen = cur.fetchone()[0]
cur.execute("SELECT COUNT(*) FROM comb2")
total = cur.fetchone()[0]

print(f"📊 Комбинаций, которые хоть раз выпадали: {seen} из {total}")

cur.close()
conn.close()
print("🔚 Соединение закрыто.")