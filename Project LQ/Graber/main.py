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
    print("Ошибка при выполнении HTTP-запроса:", e)
    exit()

if response.status_code == 200:
    page_source = response.text

    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)

    print("\nИсходный код был сохранен в 'page_source.html'.")

    soup = BeautifulSoup(page_source, 'html.parser')

    # Извлекаем дату
    date_elem = soup.find('div', id='dateAffichee')
    if date_elem:
        date_str = date_elem.text.strip()
        print(f"Дата извлечена: {date_str}")
    else:
        print("Дата не найдена.")
        date_str = None

    # Извлекаем все номера
    numeros = soup.find_all('span', class_='num')
    print(f"Найдено номеров: {len(numeros)}")

    if len(numeros) >= 9 and date_str:
        try:
            n1 = int(numeros[0].text.strip().replace(' ', ''))
            n2 = int(numeros[1].text.strip().replace(' ', ''))
            n3 = int(numeros[2].text.strip().replace(' ', ''))
            n4 = int(numeros[3].text.strip().replace(' ', ''))
            n5 = int(numeros[4].text.strip().replace(' ', ''))
            n6 = int(numeros[5].text.strip().replace(' ', ''))
            n7 = int(numeros[6].text.strip().replace(' ', ''))
            n8 = int(numeros[7].text.strip().replace(' ', ''))
            n9 = int(numeros[8].text.strip().replace(' ', ''))

            print(f"Q2: {n1}, {n2}")
            print(f"Q3: {n3}, {n4}, {n5}")
            print(f"Q4: {n6}, {n7}, {n8}, {n9}")
        except ValueError:
            print("Ошибка при преобразовании номеров.")
            n1 = n2 = n3 = n4 = n5 = n6 = n7 = n8 = n9 = None
    else:
        print("Недостаточно номеров или дата не извлечена.")
        n1 = n2 = n3 = n4 = n5 = n6 = n7 = n8 = n9 = None

    if date_str and all(n is not None for n in [n1, n2, n3, n4, n5, n6, n7, n8, n9]):
        try:
            connection = mysql.connector.connect(
                host=os.getenv('DB_HOST', 'db'),
                database=os.getenv('DB_NAME', 'quotidienne2'),
                user=os.getenv('DB_USER', 'user'),
                password=os.getenv('DB_PASSWORD', 'user')
            )

            if connection.is_connected():
                cursor = connection.cursor()

                # Создание таблицы Q2
                create_q2_query = """
                CREATE TABLE IF NOT EXISTS Q2 (
                    Tirage VARCHAR(50) PRIMARY KEY,
                    n1 INT,
                    n2 INT
                );
                """
                cursor.execute(create_q2_query)

                insert_q2_query = """
                INSERT INTO Q2 (Tirage, n1, n2) 
                VALUES (%s, %s, %s) 
                ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2);
                """
                cursor.execute(insert_q2_query, (date_str, n1, n2))
                connection.commit()

                # Создание таблицы Q3
                create_q3_query = """
                CREATE TABLE IF NOT EXISTS Q3 (
                    Tirage VARCHAR(50) PRIMARY KEY,
                    n1 INT,
                    n2 INT,
                    n3 INT
                );
                """
                cursor.execute(create_q3_query)

                insert_q3_query = """
                INSERT INTO Q3 (Tirage, n1, n2, n3)
                VALUES (%s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2), n3 = VALUES(n3);
                """
                cursor.execute(insert_q3_query, (date_str, n3, n4, n5))
                connection.commit()

                # Создание таблицы Q4
                create_q4_query = """
                CREATE TABLE IF NOT EXISTS Q4 (
                    Tirage VARCHAR(50) PRIMARY KEY,
                    n1 INT,
                    n2 INT,
                    n3 INT,
                    n4 INT
                );
                """
                cursor.execute(create_q4_query)

                insert_q4_query = """
                INSERT INTO Q4 (Tirage, n1, n2, n3, n4)
                VALUES (%s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE 
                    n1 = VALUES(n1),
                    n2 = VALUES(n2),
                    n3 = VALUES(n3),
                    n4 = VALUES(n4);
                """
                cursor.execute(insert_q4_query, (date_str, n6, n7, n8, n9))
                rows_affected = cursor.rowcount
                connection.commit()

            if rows_affected == 1:

                print(f"\nДанные добавлены/обновлены:")
                print(f"  Q2: {date_str} — {n1}, {n2}")
                print(f"  Q3: {date_str} — {n3}, {n4}, {n5}")
                print(f"  Q4: {date_str} — {n6}, {n7}, {n8}, {n9}")

                # Вызов процедур для Q2
                try:
                    cursor.callproc('fill_Q2_stats_order')
                    connection.commit()
                    print("Процедура fill_Q2_stats_order выполнена.")
                    try:
                        cursor.callproc('fill_Q2_stats_norder')
                        connection.commit()
                        print("Процедура fill_Q2_stats_norder выполнена.")
                    except Error as e:
                        print("Ошибка в fill_Q2_stats_norder:", e)
                except Error as e:
                    print("Ошибка в fill_Q2_stats_order:", e)

            else:
                    print("Данные в Q2 уже существуют. Статистика не обновлялась.")

        except Error as e:
            print("Ошибка при подключении к MySQL:", e)

        finally:
            if 'connection' in locals() and connection.is_connected():
                cursor.close()
                connection.close()
                print("Соединение с базой данных закрыто.")
else:
    print(f"Ошибка при получении страницы: {response.status_code}")