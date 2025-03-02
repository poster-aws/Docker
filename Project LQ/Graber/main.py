import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
import os

# URL страницы
url = "https://loteries.lotoquebec.com/fr/loteries/la-quotidienne"

# Выполняем GET-запрос
response = requests.get(url)

# Проверяем статус ответа (код 200)
if response.status_code == 200:
    # Получаем исходный код страницы
    page_source = response.text
    
    # Сохраняем исходный код в файл HTML
    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)

    print("\nИсходный код был сохранен в 'page_source.html'.")
    
    # Используем BeautifulSoup для парсинга HTML
    soup = BeautifulSoup(page_source, 'html.parser')
    
    # Извлекаем дату из элемента <div id="dateAffichee">
    date_elem = soup.find('div', id='dateAffichee')
    if date_elem:
        date_str = date_elem.text.strip()  # Извлекаем текст (дату)
        print(f"Дата извлечена: {date_str}")
    else:
        print("Дата не найдена на странице.")
        date_str = None
    
    # Ищем элементы <span> с классом "num" для номеров
    numeros = soup.find_all('span', class_='num')
    
    if len(numeros) >= 2 and date_str:
        # Извлекаем два первых номера
        n1 = numeros[0].text.strip().replace(' ', '')
        n2 = numeros[1].text.strip().replace(' ', '')
        
        print(f"Два первых номера: {n1}, {n2}")
        
        # Подключаемся к базе данных MySQL
        try:
            connection = mysql.connector.connect(
                host=os.getenv('DB_HOST', 'db'),  # Используем переменные окружения для безопасности
                database=os.getenv('DB_NAME', 'loterie_db'),
                user=os.getenv('DB_USER', 'root'),
                password=os.getenv('DB_PASSWORD', 'password')
            )

            if connection.is_connected():
                cursor = connection.cursor()

                # Создаём таблицу с новой колонкой для даты (Tirage) первым столбцом
                create_table_query = """
                CREATE TABLE IF NOT EXISTS loterie (
                    Tirage DATE,
                    n1 INT,
                    n2 INT
                );
                """
                cursor.execute(create_table_query)
                print("Таблица 'loterie' была успешно создана/обновлена.")
                
                # Преобразуем номера в целые числа
                try:
                    n1_int = int(n1)
                    n2_int = int(n2)
                except ValueError:
                    print(f"Ошибка преобразования номеров: {n1}, {n2}. Они не являются допустимыми целыми числами.")
                    # return

                # Преобразуем строку даты в формат для базы данных
                try:
                    tirage_date = f"{date_str}"  # Пример: "2025-02-27"
                except ValueError:
                    print("Ошибка преобразования даты.")
                    # return

                # SQL-запрос для вставки данных
                insert_query = """INSERT INTO loterie (Tirage, n1, n2) VALUES (%s, %s, %s)"""
                cursor.execute(insert_query, (tirage_date, n1_int, n2_int))
                connection.commit()
                print(f"Номера {n1_int} и {n2_int} и дата {tirage_date} были успешно вставлены в базу данных.")
        
        except Error as e:
            print("Ошибка при подключении к MySQL:", e)
        
        finally:
            # Закрываем соединение
            if 'connection' in locals() and connection.is_connected():
                cursor.close()
                connection.close()
                print("Соединение закрыто.")
    else:
        print("Недостаточно номеров или не найдена дата для вставки в базу данных.")
else:
    print(f"Ошибка при получении страницы: {response.status_code}")
