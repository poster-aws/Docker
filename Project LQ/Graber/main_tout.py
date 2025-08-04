
import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
import os
import locale
from datetime import datetime

# Локаль для французской даты
try:
    locale.setlocale(locale.LC_TIME, 'fr_CA.UTF-8')
except locale.Error:
    locale.setlocale(locale.LC_TIME, 'fr_FR.UTF-8')

url = "https://loteries.lotoquebec.com/fr/loteries/tout-ou-rien"

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
    raw_date_str = date_elem.text.strip() if date_elem else None
    try:
        date_obj = datetime.strptime(raw_date_str, "%A %d %B %Y").date()
    except ValueError:
        try:
            date_obj = datetime.strptime(raw_date_str, "%d %B %Y").date()
        except ValueError:
            print("* Невозможно преобразовать дату:", raw_date_str)
            exit()

    print(f"- Дата преобразована: {date_obj}")

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
                database='toutourien',
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
                    print(f"- Данные для {date_obj} уже существуют.")
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
            print("; Ошибка при подключении к MySQL:", e)

        finally:
            try:
                if 'cursor' in locals(): cursor.close()
                if 'connection' in locals() and connection.is_connected(): connection.close()
                print("- Соединение с базой данных закрыто.")
            except Exception as cleanup_error:
                print("* Ошибка при закрытии:", cleanup_error)

    else:
        print("- Недостаточно номеров или дата не извлечена.")
else:
    print(f"- Ошибка при получении страницы: {response.status_code}")
