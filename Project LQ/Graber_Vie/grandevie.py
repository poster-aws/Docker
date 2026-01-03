import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
from datetime import datetime
import os

# URL страницы (Grande Vie)
url = "https://loteries.lotoquebec.com/fr/loteries/grande-vie-resultats#res"

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

    # === 3. Извлекаем 5 номеров ===
    numeros = soup.find_all('span', class_='num')
    print(f"- Найдено номеров: {len(numeros)}")

    try:
        values = [int(span.text.strip()) for span in numeros[:5]]
    except ValueError:
        print("* Ошибка при преобразовании номеров.")
        values = []

    if len(values) == 5:
        n1, n2, n3, n4, n5 = values
        print("Grande Vie:")
        print(f"{n1:2} {n2:2} {n3:2} {n4:2} {n5:2}")
    else:
        print("* Недостаточно номеров.")
        exit()

    # === 3.1. Извлекаем Grand Numéro ===
    gn_elem = soup.find('span', class_='num_gn')
    if not gn_elem:
        print("* Grand Numéro не найден.")
        exit()

    try:
        GN = int(gn_elem.text.strip())
        print(f"- Grand Numéro: {GN}")
    except ValueError:
        print("* Ошибка при разборе Grand Numéro.")
        exit()

    # === 4. Подключаемся к базе данных ===
    try:
        connection = mysql.connector.connect(
            host='db',
            database='vie',
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
                CREATE TABLE IF NOT EXISTS Vie (
                    Tirage DATE PRIMARY KEY,
                    n1 TINYINT, n2 TINYINT, n3 TINYINT, n4 TINYINT, n5 TINYINT,
                    GN TINYINT
                )
            """)

            # === 6. Проверка — есть ли уже запись с этой датой ===
            cursor.execute("SELECT COUNT(*) FROM Vie WHERE Tirage = %s", (date_obj,))
            exists = cursor.fetchone()[0] > 0

            if exists:
                print(f"- Данные для {date_obj} уже существуют! Пропускаем вставку.")
            else:
                # === 7. Вставляем новую запись ===
                cursor.execute("""
                    INSERT INTO Vie (Tirage, n1, n2, n3, n4, n5, GN)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (date_obj, n1, n2, n3, n4, n5, GN))
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
    if os.path.exists("page_source.html"):
        try:
            os.remove("page_source.html")
            print("- Временный файл 'page_source.html' удалён.")
        except Exception as e:
            print(f"* Ошибка при удалении файла page_source.html: {e}")

else:
    print(f"* Ошибка при получении страницы: {response.status_code}")