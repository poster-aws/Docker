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

# === 1. Загружаем все тиражи ===
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

# === 2. Последняя дата ===
cur.execute("SELECT MAX(Tirage) FROM banco;")
last_global_date = cur.fetchone()[0]
print(f"📅 Последний тираж: {last_global_date}")

# === 3. Статистика по четверкам на лету ===
# структура: (n1,n2,n3,n4) -> [last, prev, max_gap, fois]
stats = {}
print("🧮 Считаем статистику 4-комбинаций...")

for row in tirages:
    date = row[0]
    nums = [int(x) for x in row[1:] if x is not None]

    # Все уникальные комбинации по 4 числа из тиража
    for n1, n2, n3, n4 in itertools.combinations(sorted(nums), 4):
        key = (n1, n2, n3, n4)
        if key not in stats:
            # первое появление
            stats[key] = [date, None, 0, 1]
        else:
            last, prev, max_gap, fois = stats[key]

            gap = (date - last).days
            if gap > max_gap:
                max_gap = gap

            prev = last
            last = date
            fois += 1

            stats[key] = [last, prev, max_gap, fois]

print(f"✅ Обработано {len(stats)} комбинаций 4 чисел.")

# === 4. UPDATE comb4 по PRIMARY KEY ===
update_sql = """
    UPDATE comb4
    SET Tirage = %s,
        days   = %s,
        days2  = %s,
        fois   = %s,
        max    = %s
    WHERE n1=%s AND n2=%s AND n3=%s AND n4=%s
"""

print("🧩 Обновляем comb4...")

updated = 0

for (n1, n2, n3, n4), (last, prev, max_gap, fois) in stats.items():

    days2 = (last - prev).days if prev else 0
    days  = (last_global_date - last).days

    cur.execute(update_sql, (
        last, days, days2, fois, max_gap,
        n1, n2, n3, n4
    ))

    updated += 1
    if updated % 100 == 0:
        conn.commit()
        print(f"  ⏩ Обновлено {updated} комбинаций...")

conn.commit()
print(f"🎉 Готово! Обновлено {updated} комбинаций в comb4.")

# === 5. Итоги ===
cur.execute("SELECT COUNT(*) FROM comb4 WHERE fois > 0")
seen = cur.fetchone()[0]
cur.execute("SELECT COUNT(*) FROM comb4")
total = cur.fetchone()[0]

print(f"📊 Комбинаций, которые хоть раз выпадали: {seen} из {total}")

cur.close()
conn.close()
print("🔚 Соединение закрыто.")