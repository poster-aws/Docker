import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
from datetime import datetime
import os

# URL новой страницы (без SSL-проблем)
url = "https://loteries.lotoquebec.com/fr/loteries/tout-ou-rien"

try:
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    }
    response = requests.get(url, timeout=30, headers=headers)
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
        try:
            date_obj = datetime.strptime(date_str, "%Y-%m-%d").date()
        except ValueError:
            print("* Ошибка: неверный формат даты.")
            exit()
    else:
        print("* Дата не найдена.")
        exit()

    # Извлекаем номера
    numeros = soup.find_all('span', class_='num')
    print(f"- Найдено номеров: {len(numeros)}")

    try:
        values = [int(span.text.strip()) for span in numeros[:12]]
    except ValueError:
        print("* Ошибка при преобразовании номеров.")
        values = []

    if len(values) == 12:
        n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12 = values
        print(f"{n1} {n2} {n3} {n4} {n5} {n6}")
        print(f"  {n7} {n8} {n9} {n10} {n11} {n12}")

        try:
            connection = mysql.connector.connect(
                host='db',
                database='toutourien',  # <-- без getenv
                user='user',
                password='user'
            )
            if connection.is_connected():
                cursor = connection.cursor()
                cursor.execute("SELECT DATABASE()")
                print(f"- Подключен к базе данных: {cursor.fetchone()[0]}")

                # Создаем таблицу если нет
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS Tout (
                        Tirage DATE PRIMARY KEY,
                        n1 TINYINT, n2 TINYINT, n3 TINYINT, n4 TINYINT, n5 TINYINT, n6 TINYINT,
                        n7 TINYINT, n8 TINYINT, n9 TINYINT, n10 TINYINT, n11 TINYINT, n12 TINYINT
                    )
                """)

                # Проверка — существует ли уже такая дата
                cursor.execute("SELECT COUNT(*) FROM Tout WHERE Tirage = %s", (date_obj,))
                exists = cursor.fetchone()[0] > 0

                if exists:
                    print(f"- Данные для {date_obj} уже существуют!")
                else:
                    cursor.execute("""
                        INSERT INTO Tout (Tirage, n1, n2, n3, n4, n5, n6,
                                          n7, n8, n9, n10, n11, n12)
                        VALUES (%s, %s, %s, %s, %s, %s, %s,
                                %s, %s, %s, %s, %s, %s)
                    """, (date_obj, n1, n2, n3, n4, n5, n6,
                          n7, n8, n9, n10, n11, n12))
                    connection.commit()
                    print(f"- Тираж {date_obj} успешно добавлен.")

        except Error as e:
            print("* Ошибка при подключении к MySQL:", e)

        finally:
            try:
                if 'cursor' in locals(): cursor.close()
                if 'connection' in locals() and connection.is_connected(): connection.close()
                print("- Соединение с базой данных закрыто.")
            except Exception as cleanup_error:
                print("* Ошибка при закрытии:", cleanup_error)

    else:
        print("- Недостаточно номеров.")
else:
    print(f"- Ошибка при получении страницы: {response.status_code}")