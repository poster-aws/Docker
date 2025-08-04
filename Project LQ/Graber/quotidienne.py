import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
import os

# URL страницы
url = "https://loteries.lotoquebec.com/fr/loteries/la-quotidienne"

try:
    response = requests.get(url, timeout=10)
    response.raise_for_status()
except requests.RequestException as e:
    print("* Ошибка при выполнении HTTP-запроса:", e)
    exit()

if response.status_code == 200:
    page_source = response.text

    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)

    print("\n- Исходный код сохранен в 'page_source.html'.")

    soup = BeautifulSoup(page_source, 'html.parser')

    # Извлекаем дату
    date_elem = soup.find('div', id='dateAffichee')
    date_str = date_elem.text.strip() if date_elem else None
    if date_str:
        print(f"- Дата извлечена: {date_str}")
    else:
        print("* Дата не найдена.")

    # Извлекаем номера
    numeros = soup.find_all('span', class_='num')
    print(f"- Найдено номеров: {len(numeros)}")

    try:
        values = [int(span.text.strip().replace(' ', '')) for span in numeros[:9]]
    except ValueError:
        print("* Ошибка при преобразовании номеров.")
        values = []

    if len(values) == 9 and date_str:
        n1, n2, n3, n4, n5, n6, n7, n8, n9 = values
        print(f"Q2: {n1}, {n2}")
        print(f"Q3: {n3}, {n4}, {n5}")
        print(f"Q4: {n6}, {n7}, {n8}, {n9}")

        try:
            connection = mysql.connector.connect(
            host='db',
            database='quotidienne',  # <-- без getenv
            user='user',
            password='user'
        )

            if connection.is_connected():
                cursor = connection.cursor()
                inserted = False
                cursor.execute("SELECT DATABASE()")
                current_db = cursor.fetchone()[0]
                print(f"- Подключен к базе: {current_db}")

                # Q2
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS Q2 (
                        Tirage VARCHAR(50) PRIMARY KEY,
                        n1 INT,
                        n2 INT
                    )
                """)
                cursor.execute("""
                    INSERT INTO Q2 (Tirage, n1, n2)
                    VALUES (%s, %s, %s)
                    ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2)
                """, (date_str, n1, n2))
                if cursor.rowcount:
                    inserted = True

                # Q3
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS Q3 (
                        Tirage VARCHAR(50) PRIMARY KEY,
                        n1 INT,
                        n2 INT,
                        n3 INT
                    )
                """)
                cursor.execute("""
                    INSERT INTO Q3 (Tirage, n1, n2, n3)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2), n3 = VALUES(n3)
                """, (date_str, n3, n4, n5))
                if cursor.rowcount:
                    inserted = True

                # Q4
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS Q4 (
                        Tirage VARCHAR(50) PRIMARY KEY,
                        n1 INT,
                        n2 INT,
                        n3 INT,
                        n4 INT
                    )
                """)
                cursor.execute("""
                    INSERT INTO Q4 (Tirage, n1, n2, n3, n4)
                    VALUES (%s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        n1 = VALUES(n1),
                        n2 = VALUES(n2),
                        n3 = VALUES(n3),
                        n4 = VALUES(n4)
                """, (date_str, n6, n7, n8, n9))
                if cursor.rowcount:
                    inserted = True

                connection.commit()

                if inserted:
                    print(f"\n- Данные добавлены:")
                    print(f"  Q2: {date_str} — {n1}, {n2}")
                    print(f"  Q3: {date_str} — {n3}, {n4}, {n5}")
                    print(f"  Q4: {date_str} — {n6}, {n7}, {n8}, {n9}")

                    procedures = [
                        'fill_Q2_stats_order',
                        'fill_Q2_stats_norder',
                        'fill_Q2_combo_stats_order',
                        'fill_Q3_stats_order',
                        'fill_Q3_stats_norder',
                        'fill_Q3_combo_stats_order',
                        'fill_Q4_fois',
                        'fill_Q4_stats_order',
                        'fill_Q4_stats_norder',
                        'fill_Q4_combo_stats_order'
                    ]

                    for proc in procedures:
                        try:
                            cursor.callproc(proc)
                            connection.commit()
                            print(f"- Процедура {proc} выполнена.")
                        except Error as e:
                            print(f"* Ошибка в {proc}:", e)
                else:
                    print("- Данные уже существуют! Процедуры не запущены.")

        except Error as e:
            print("* Ошибка при подключении к MySQL:", e)

        finally:
            try:
                if 'cursor' in locals() and cursor:
                    cursor.close()
                if 'connection' in locals() and connection.is_connected():
                    connection.close()
                print("- Соединение с базой данных полностью закрыто.")
            except Exception as cleanup_error:
                print("* Ошибка при закрытии соединения:", cleanup_error)

    else:
        print("* Недостаточно номеров или дата не извлечена.")
else:
    print(f"* Ошибка при получении страницы: {response.status_code}")