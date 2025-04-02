import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
import os

# URL страницы
url = "https://loteries.lotoquebec.com/fr/loteries/la-quotidienne"

# Выполняем GET-запрос
response = requests.get(url)

if response.status_code == 200:
    page_source = response.text

    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)

    print("\n Исходный код был сохранен в 'page_source.html'.")

    soup = BeautifulSoup(page_source, 'html.parser')

    # Извлекаем дату как строку
    date_elem = soup.find('div', id='dateAffichee')
    if date_elem:
        date_str = date_elem.text.strip()
        print(f"Дата извлечена: {date_str}")
    else:
        print("Дата не найдена.")
        date_str = None

    # Извлекаем номера
    numeros = soup.find_all('span', class_='num')
    if len(numeros) >= 2 and date_str:
        try:
            n1 = int(numeros[0].text.strip().replace(' ', ''))
            n2 = int(numeros[1].text.strip().replace(' ', ''))
            print(f"Два первых номера: {n1}, {n2}")
        except ValueError:
            print("Ошибка при преобразовании номеров.")
            n1, n2 = None, None
    else:
        print("Недостаточно номеров или дата не извлечена.")
        n1, n2 = None, None

    if date_str and n1 is not None and n2 is not None:
        try:
            connection = mysql.connector.connect(
                host=os.getenv('DB_HOST', 'db'),
                database=os.getenv('DB_NAME', 'quotidienne2'),
                user=os.getenv('DB_USER', 'user'),
                password=os.getenv('DB_PASSWORD', 'user')
            )

            if connection.is_connected():
                cursor = connection.cursor()

                # Создание таблицы Q2, если не существует
                create_table_query = """
                CREATE TABLE IF NOT EXISTS Q2 (
                    Tirage VARCHAR(50) PRIMARY KEY,
                    n1 INT,
                    n2 INT
                );
                """
                cursor.execute(create_table_query)

                # Вставка в Q2
                insert_query = """
                INSERT INTO Q2 (Tirage, n1, n2) 
                VALUES (%s, %s, %s) 
                ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2);
                """
                cursor.execute(insert_query, (date_str, n1, n2))
                rows_affected = cursor.rowcount
                connection.commit()

                if rows_affected == 1:
                    print(f"Новые данные добавлены: {date_str} — {n1}, {n2}")

                    # Вызов первой процедуры
                    try:
                        cursor.callproc('fill_Q2_stats_order')
                        connection.commit()
                        print("Процедура fill_Q2_stats_order была успешно выполнена.")

                        # Вызов второй процедуры
                        try:
                            cursor.callproc('fill_Q2_stats_norder')
                            connection.commit()
                            print("Процедура fill_Q2_stats_norder была успешно выполнена.")
                        except Error as e:
                            print("Ошибка при выполнении процедуры fill_Q2_stats_norder:", e)

                    except Error as e:
                        print("Ошибка при выполнении процедуры fill_Q2_stats_order:", e)
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