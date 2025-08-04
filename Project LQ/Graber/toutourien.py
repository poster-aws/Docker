import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
import os

# URL новой страницы (без SSL-проблем)
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
    date_str = date_elem.text.strip() if date_elem else None
    if date_str:
        print(f"- Дата извлечена: {date_str}")
    else:
        print("* Дата не найдена.")

    # Извлекаем номера
    numeros = soup.find_all('span', class_='num')
    print(f"- Найдено номеров: {len(numeros)}")

    try:
        values = [int(span.text.strip()) for span in numeros[:12]]
    except ValueError:
        print("* Ошибка при преобразовании номеров.")
        values = []

    if len(values) == 12 and date_str:
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

# Проверка на уже существующий тираж
            if connection.is_connected():
                cursor = connection.cursor()
                inserted = False
                cursor.execute("SELECT DATABASE()")
                current_db = cursor.fetchone()[0]
                print(f"- Подключен к базе: {current_db}")

                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS Tout (
                        Tirage VARCHAR(50) PRIMARY KEY,
                        n1 INT, n2 INT, n3 INT, n4 INT, n5 INT, n6 INT,
                        n7 INT, n8 INT, n9 INT, n10 INT, n11 INT, n12 INT
                    )
                """)
                
                cursor.execute("""
                    INSERT INTO Tout (Tirage, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        n1=VALUES(n1), n2=VALUES(n2), n3=VALUES(n3), n4=VALUES(n4),
                        n5=VALUES(n5), n6=VALUES(n6), n7=VALUES(n7), n8=VALUES(n8),
                        n9=VALUES(n9), n10=VALUES(n10), n11=VALUES(n11), n12=VALUES(n12)
                """, (date_str, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12))

                if cursor.rowcount:
                    inserted = True
                
                connection.commit()

                if inserted:               
                    print(f"- Тираж {date_str} успешно добавлен.")
                else:
                    print(f"- Данные для тиража {date_str} уже существуют!")

        except Error as e:
            print("; Ошибка при подключении к MySQL:", e)

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
        print("- Недостаточно номеров или дата не извлечена.")
else:
    print(f"- Ошибка при получении страницы: {response.status_code}")