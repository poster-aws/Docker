import mysql.connector
from datetime import datetime
import itertools

# === Настройки подключения ===
DB_CONFIG = {
    "host": "db",
    "user": "user",
    "password": "user",
    "database": "banco",
    "charset": "utf8mb4",
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

# === 3. Статистика по парам на лету ===
# (n1, n2) -> [last_date, prev_last_date, max_gap, fois]
stats = {}
print("🧮 Считаем статистику для комбинаций 2 из 20...")

for row in tirages:
    date = row[0]  # Tirage (DATE/DATETIME из MySQL)
    nums = [int(x) for x in row[1:] if x is not None]

    # itertools.combinations(sorted(nums), 2) уже даёт n1 < n2
    for n1, n2 in itertools.combinations(sorted(nums), 2):
        key = (n1, n2)
        if key not in stats:
            # первое появление пары
            stats[key] = [date, None, 0, 1]   # last, prev, max_gap, fois
        else:
            last, prev, max_gap, fois = stats[key]

            gap = (date - last).days          # оба — date/datetime, можно вычитать
            if gap > max_gap:
                max_gap = gap

            prev = last
            last = date
            fois += 1

            stats[key] = [last, prev, max_gap, fois]

print(f"✅ Обработано {len(stats)} комбинаций.")

# === 4. UPDATE по PRIMARY KEY (n1, n2) ===
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

for (n1, n2), (last, prev, max_gap, fois) in stats.items():
    # days2 — между последними двумя появлениями
    days2 = (last - prev).days if prev is not None else 0

    # days — от последнего тиража базы до последнего выпадения пары
    days = (last_global_date - last).days

    cur.execute(update_sql, (
        last,      # Tirage
        days,      # days
        days2,     # days2
        fois,      # fois
        max_gap,   # max
        n1, n2     # PK
    ))

    updated += 1
    if updated % 100 == 0:
        conn.commit()
        print(f"  ⏩ Обновлено {updated} комбинаций...")

# финальный commit
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