import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
from datetime import datetime

# URL страницы (Banco)
url = "https://loteries.lotoquebec.com/fr/loteries/banco"

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

    # Сохраняем копию HTML для отладки
    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)
    print("\n- Исходный код сохранён в 'page_source.html'.")

    soup = BeautifulSoup(page_source, 'html.parser')

    # === 2. Извлекаем и парсим дату напрямую ===
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

    # === 3. Извлекаем 20 номеров ===
    numeros = soup.find_all('span', class_='num')
    print(f"- Найдено номеров: {len(numeros)}")

    try:
        values = [int(span.text.strip().replace(' ', '')) for span in numeros[:20]]
    except ValueError:
        print("* Ошибка при преобразовании номеров.")
        values = []

    if len(values) == 20:
        n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12, n13, n14, n15, n16, n17, n18, n19, n20 = values
        print("Banco:")
        print(f"{n1:2} {n2:2} {n3:2} {n4:2} {n5:2} {n6:2} {n7:2} {n8:2} {n9:2} {n10:2}")
        print(f"{n11:2} {n12:2} {n13:2} {n14:2} {n15:2} {n16:2} {n17:2} {n18:2} {n19:2} {n20:2}")
    else:
        print("* Недостаточно номеров.")
        exit()

    # === 3.1. Извлекаем Turbo ===
    turbo_elem = soup.find('img', class_='multiplicateur')
    if turbo_elem and 'alt' in turbo_elem.attrs:
        try:
            turbo = int(turbo_elem['alt'].split('x')[-1].strip())
            print(f"- Turbo: x{turbo}")
        except ValueError:
            print("* Ошибка при разборе Turbo.")
            turbo = 0
    else:
        print("* Turbo не найден.")
        turbo = 0

    # === 4. Подключаемся к базе данных ===
    try:
        connection = mysql.connector.connect(
            host='db',
            database='banco',
            user='user',
            password='user'
        )

        if connection.is_connected():
            cursor = connection.cursor()

            cursor.execute("SELECT DATABASE()")
            current_db = cursor.fetchone()[0]
            print(f"- Подключен к БД: {current_db}")

            # === 5. Создаём таблицу, если её нет ===
            cursor.execute("""
                CREATE TABLE IF NOT EXISTS banco (
                    Tirage DATE PRIMARY KEY,
                    n1 TINYINT, n2 TINYINT, n3 TINYINT, n4 TINYINT, n5 TINYINT, n6 TINYINT,
                    n7 TINYINT, n8 TINYINT, n9 TINYINT, n10 TINYINT, n11 TINYINT, n12 TINYINT,
                    n13 TINYINT, n14 TINYINT, n15 TINYINT, n16 TINYINT, n17 TINYINT, n18 TINYINT,
                    n19 TINYINT, n20 TINYINT,
                    turbo TINYINT
                )
            """)

            # === 6. Проверка — есть ли уже запись с этой датой ===
            cursor.execute("SELECT COUNT(*) FROM banco WHERE Tirage = %s", (date_obj,))
            exists = cursor.fetchone()[0] > 0

            if exists:
                print(f"- Данные для {date_obj} уже существуют! Пропускаем вставку.")
            else:
                # === 7. Вставляем новую запись ===
                cursor.execute("""
                    INSERT INTO banco (Tirage, n1, n2, n3, n4, n5, n6,
                                       n7, n8, n9, n10, n11, n12,
                                       n13, n14, n15, n16, n17, n18, n19, n20, turbo)
                    VALUES (%s, %s, %s, %s, %s, %s, %s,
                            %s, %s, %s, %s, %s, %s,
                            %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """, (date_obj, n1, n2, n3, n4, n5, n6,
                      n7, n8, n9, n10, n11, n12,
                      n13, n14, n15, n16, n17, n18, n19, n20, turbo))
                connection.commit()
                print(f"- Тираж {date_obj} успешно добавлен в базу данных.")

    except Error as e:
        print("* Ошибка при работе с MySQL:", e)

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
    import os
    if os.path.exists("page_source.html"):
        try:
            os.remove("page_source.html")
            print("- Временный файл 'page_source.html' удалён.")
        except Exception as e:
            print(f"* Ошибка при удалении файла page_source.html: {e}")

else:
    print(f"* Ошибка при получении страницы: {response.status_code}")