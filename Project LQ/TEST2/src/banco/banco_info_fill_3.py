import mysql.connector
from datetime import datetime
import itertools

# === Настройки подключения ===
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

# === 2. Определяем дату последнего тиража ===
cur.execute("SELECT MAX(Tirage) FROM banco;")
last_global_date = cur.fetchone()[0]
print(f"📅 Последний тираж в базе: {last_global_date}")

# === 3. Формируем все комбинации из 3 чисел (n1 < n2 < n3) ===
triplets = {}  # (n1,n2,n3) -> [список дат]
print("🧮 Формируем комбинации из 3 чисел...")

for row in tirages:
    date = row[0]
    nums = [int(x) for x in row[1:] if x is not None]
    for combo in itertools.combinations(sorted(nums), 3):
        key = tuple(sorted(combo))
        if key not in triplets:
            triplets[key] = []
        triplets[key].append(date)

print(f"✅ Обнаружено {len(triplets)} уникальных комбинаций из 3 чисел.")

# === 4. Подготовка SQL для обновления comb3 ===
update_sql = """
    UPDATE comb3
    SET Tirage = %s,
        days   = %s,
        days2  = %s,
        fois   = %s,
        max    = %s
    WHERE (n1=%s AND n2=%s AND n3=%s)
"""

print("🧩 Вычисляем статистику и обновляем comb3...")

updated = 0
for (a, b, c), dates in triplets.items():
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

    # --- Правильная статистика ---
    max_gap = max(gaps) if gaps else 0

    # days2 = разница между последними двумя выпадениями (если >= 2)
    if len(dates) >= 2:
        d_last = dates[-1]
        d_prev = dates[-2]
        if isinstance(d_last, datetime):
            days2 = (d_last - d_prev).days
        else:
            days2 = (datetime.strptime(str(d_last), "%Y-%m-%d") -
                     datetime.strptime(str(d_prev), "%Y-%m-%d")).days
    else:
        days2 = 0

    # days = разница между последним тиражом базы и последним выпадением
    last_date = dates[-1]
    if isinstance(last_date, datetime):
        days = (last_global_date - last_date).days
    else:
        days = (datetime.strptime(str(last_global_date), "%Y-%m-%d") -
                datetime.strptime(str(last_date), "%Y-%m-%d")).days

    # Обновление comb3
    cur.execute(update_sql, (
        last_date, days, days2, fois, max_gap, a, b, c
    ))

    updated += 1
    if updated % 100 == 0:
        conn.commit()
        print(f"  ⏩ Обновлено {updated} комбинаций...")

conn.commit()
print(f"✅ Готово. Обновлено {updated} комбинаций в comb3.")

# === 5. Проверка результата ===
cur.execute("SELECT COUNT(*) FROM comb3 WHERE fois > 0")
seen = cur.fetchone()[0]
cur.execute("SELECT COUNT(*) FROM comb3")
total = cur.fetchone()[0]

print(f"📊 Комбинаций, которые хоть раз выпадали: {seen} из {total}")

cur.close()
conn.close()
print("🔚 Соединение закрыто.")