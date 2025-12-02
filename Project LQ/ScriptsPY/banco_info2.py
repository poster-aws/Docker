import mysql.connector
from datetime import datetime
import itertools

# === Настройки подключения ===
DB_CONFIG = {
    "host": "db",
    "user": "user",
    "password": "user",
    "database": "banco",
    "charset": "utf8mb4"
}

print("🔄 Подключаемся к базе данных banco...")
conn = mysql.connector.connect(**DB_CONFIG)
cur = conn.cursor()

# === 1. Загружаем все тиражи banco ===
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

# === 2. Последняя дата в таблице ===
cur.execute("SELECT MAX(Tirage) FROM banco;")
last_global_date = cur.fetchone()[0]
print(f"📅 Последний тираж в базе: {last_global_date}")

# === 3. Формируем ВСЕ пары (n1 < n2) ===
pairs = {}   # (n1,n2) -> [даты]
print("🧮 Формируем комбинации 2 из 20...")

for row in tirages:
    date = row[0]
    nums = [int(x) for x in row[1:] if x is not None]

    # itertools.combinations уже гарантирует n1 < n2
    for n1, n2 in itertools.combinations(sorted(nums), 2):
        pairs.setdefault((n1, n2), []).append(date)

print(f"✅ Обнаружено {len(pairs)} уникальных комбинаций 2 чисел.")

# === 4. SQL без OR (идеальный вариант!) ===
update_sql = """
    UPDATE comb2
    SET Tirage = %s,
        days   = %s,
        days2  = %s,
        fois   = %s,
        max    = %s
    WHERE n1=%s AND n2=%s
"""

print("🧩 Обновляем comb2...")

updated = 0
for (n1, n2), dates in pairs.items():
    dates.sort()
    fois = len(dates)

    # --- интервалы между появлениями ---
    gaps = []
    for i in range(1, len(dates)):
        d1, d2 = dates[i - 1], dates[i]
        if not isinstance(d1, datetime):
            d1 = datetime.strptime(str(d1), "%Y-%m-%d")
            d2 = datetime.strptime(str(d2), "%Y-%m-%d")
        gaps.append((d2 - d1).days)

    max_gap = max(gaps) if gaps else 0

    # --- разрыв между последними двумя ---
    if len(dates) >= 2:
        d_last = dates[-1]
        d_prev = dates[-2]
        if not isinstance(d_last, datetime):
            d_last = datetime.strptime(str(d_last), "%Y-%m-%d")
            d_prev = datetime.strptime(str(d_prev), "%Y-%m-%d")
        days2 = (d_last - d_prev).days
    else:
        days2 = 0

    # --- разница между последним тиражом и последним выпадением ---
    last_date = dates[-1]
    if not isinstance(last_date, datetime):
        last_date = datetime.strptime(str(last_date), "%Y-%m-%d")
    if not isinstance(last_global_date, datetime):
        last_global_date = datetime.strptime(str(last_global_date), "%Y-%m-%d")

    days = (last_global_date - last_date).days

    # === идеальный UPDATE (индекс PRIMARY KEY(n1,n2)) ===
    cur.execute(update_sql, (
        last_date, days, days2, fois, max_gap, n1, n2
    ))

    updated += 1
    if updated % 100 == 0:
        conn.commit()
        print(f"  ⏩ Обновлено {updated} комбинаций...")

conn.commit()
print(f"🎉 Готово. Обновлено {updated} комбинаций в comb2.")

# === 5. Проверка результата ===
cur.execute("SELECT COUNT(*) FROM comb2 WHERE fois > 0")
seen = cur.fetchone()[0]
cur.execute("SELECT COUNT(*) FROM comb2")
total = cur.fetchone()[0]

print(f"📊 Комбинаций, которые хоть раз выпадали: {seen} из {total}")

cur.close()
conn.close()
print("🔚 Соединение закрыто.")