import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
from datetime import datetime
import os

# URL страницы (La Quotidienne)
url = "https://loteries.lotoquebec.com/fr/loteries/la-quotidienne-resultats#res"

# === 1. Получаем HTML ===
try:
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"}
    response = requests.get(url, timeout=30, headers=headers)
    response.raise_for_status()
except requests.RequestException as e:
    print("* Ошибка при выполнении HTTP-запроса:", e)
    exit()

if response.status_code == 200:
    page_source = response.text

    # Сохраняем HTML для отладки
    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)
    print("\n- Исходный код сохранён в 'page_source.html'.")

    soup = BeautifulSoup(page_source, 'html.parser')

    # === 2. Извлекаем дату ===
    date_elem = soup.find('div', id='dateAffichee')
    if not date_elem:
        print("* Дата не найдена.")
        exit()

    try:
        date_obj = datetime.strptime(date_elem.text.strip(), "%Y-%m-%d").date()
        print(f"- Дата извлечена: {date_obj}")
    except ValueError:
        print("* Ошибка: неверный формат даты.")
        exit()

    # === 3. Извлекаем 9 чисел ===
    numeros = soup.find_all('span', class_='num')
    print(f"- Найдено номеров: {len(numeros)}")

    try:
        values = [int(span.text.strip().replace(' ', '')) for span in numeros[:9]]
    except ValueError:
        print("* Ошибка при преобразовании номеров.")
        values = []

    if len(values) != 9:
        print("* Недостаточно номеров.")
        exit()

    print(f"Q2: {values[0]}, {values[1]}")
    print(f"Q3: {values[2]}, {values[3]}, {values[4]}")
    print(f"Q4: {values[5]}, {values[6]}, {values[7]}, {values[8]}")

    # === 4. Подключение к базе и вставка ===
    try:
        connection = mysql.connector.connect(
            host='db',
            database='quotidienne',
            user='user',
            password='user'
        )

        if connection.is_connected():
            cursor = connection.cursor()
            cursor.execute("SELECT DATABASE()")
            current_db = cursor.fetchone()[0]
            print(f"- Подключен к БД: {current_db}")

            inserted = False

            # === 5. Обработка всех таблиц циклом ===
            configs = [
                ('Q2', ['n1', 'n2'], values[0:2]),
                ('Q3', ['n1', 'n2', 'n3'], values[2:5]),
                ('Q4', ['n1', 'n2', 'n3', 'n4'], values[5:9])
            ]

            for table, fields, nums in configs:
                # Создание таблицы
                field_defs = ",\n    ".join([f"{f} INT" for f in fields])
                cursor.execute(f"""
                    CREATE TABLE IF NOT EXISTS {table} (
                        Tirage DATE PRIMARY KEY,
                        {field_defs}
                    )
                """)

                # Проверка существования
                cursor.execute(f"SELECT COUNT(*) FROM {table} WHERE Tirage = %s", (date_obj,))
                if cursor.fetchone()[0] == 0:
                    placeholders = ", ".join(["%s"] * len(nums))
                    field_list = ", ".join(fields)
                    cursor.execute(
                        f"INSERT INTO {table} (Tirage, {field_list}) VALUES (%s, {placeholders})",
                        (date_obj, *nums)
                    )
                    inserted = True
                    print(f"- {table}: новая запись добавлена.")

            connection.commit()

            # === 6. Запускаем процедуры, если были вставки ===
            if inserted:
                print(f"\n- Новые данные добавлены за {date_obj}. Запускаем процедуры...")

                procedures = [
                    'fill_Q2_stats_order', 'fill_Q2_stats_norder', 'fill_Q2_combo_stats_order',
                    'fill_Q3_stats_order', 'fill_Q3_stats_norder', 'fill_Q3_combo_stats_order',
                    'fill_Q4_fois', 'fill_Q4_stats_order', 'fill_Q4_stats_norder', 'fill_Q4_combo_stats_order'
                ]

                for proc in procedures:
                    try:
                        cursor.callproc(proc)
                        connection.commit()
                        print(f"- Процедура {proc} выполнена.")
                    except Error as e:
                        print(f"* Ошибка в {proc}: {e}")
            else:
                print(f"- Данные за {date_obj} уже были в базе. Вставка и процедуры пропущены.")

    except Error as e:
        print("* Ошибка при подключении к MySQL:", e)

    finally:
        try:
            if 'cursor' in locals() and cursor:
                cursor.close()
            if 'connection' in locals() and connection.is_connected():
                connection.close()
            print("- Соединение с базой данных закрыто.")
        except Exception as cleanup_error:
            print("* Ошибка при закрытии соединения:", cleanup_error)

    # === 8. Удаляем временный файл page_source.html ===
    if os.path.exists("page_source.html"):
        try:
            os.remove("page_source.html")
            print("- Временный файл 'page_source.html' удалён.")
        except Exception as e:
            print(f"* Ошибка при удалении файла page_source.html: {e}")

else:
    print(f"* Ошибка при получении страницы: {response.status_code}")