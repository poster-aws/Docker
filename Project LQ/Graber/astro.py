import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error
from datetime import datetime
import os

# URL страницы (Astro)
url = "https://loteries.lotoquebec.com/fr/loteries/astro-resultats#res"

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

    # === 2. Извлекаем и парсим дату тиража ===
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

    # === 3. Извлекаем данные Astro ===
    numeros = soup.find('div', class_='numeros typeAstro')
    if not numeros:
        print("* Блок Astro не найден.")
        exit()

    try:
        jour = int(numeros.find('div', class_='jour').text.strip())
        mois = numeros.find('div', class_='mois').text.strip()
        annee = int(numeros.find('div', class_='annee').text.strip())

        signe_img = numeros.find('div', class_='signe').find('img', class_='signe noir')
        signe = signe_img['alt'].strip()
    except Exception as e:
        print("* Ошибка при извлечении данных Astro:", e)
        exit()

    print("Astro:")
    print(f"- Jour : {jour}")
    print(f"- Mois : {mois}")
    print(f"- Année : {annee}")
    print(f"- Signe : {signe}")

    # === 4. Подключаемся к базе данных ===
    try:
        connection = mysql.connector.connect(
            host='db',
            database='astro',
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
                CREATE TABLE IF NOT EXISTS Astro (
                    Tirage DATE PRIMARY KEY,
                    jour TINYINT,
                    mois VARCHAR(10),
                    annee TINYINT,
                    signe VARCHAR(11)
                )
                ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            """)

            # === 6. Проверка — есть ли уже запись с этой датой ===
            cursor.execute("SELECT COUNT(*) FROM Astro WHERE Tirage = %s", (date_obj,))
            exists = cursor.fetchone()[0] > 0

            inserted = False
            if exists:
                print(f"- Данные для {date_obj} уже существуют! Пропускаем вставку.")
            else:
                # === 7. Вставляем новую запись ===
                cursor.execute("""
                    INSERT INTO Astro (Tirage, jour, mois, annee, signe)
                    VALUES (%s, %s, %s, %s, %s)
                """, (date_obj, jour, mois, annee, signe))
                connection.commit()
                inserted = True
                print(f"- Тираж {date_obj} успешно добавлен в базу данных.")

            # === 8. Запускаем процедуру fill_Astro_stats, если были вставки ===
            if inserted:
                print("- Запускаем процедуру fill_Astro_stats...")
                try:
                    cursor.callproc("fill_Astro_stats")
                    connection.commit()
                    print("- Процедура fill_Astro_stats выполнена.")
                except Error as e:
                    print(f"* Ошибка при вызове fill_Astro_stats: {e}")

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

    # === 9. Удаляем временный файл page_source.html ===
    if os.path.exists("page_source.html"):
        try:
            os.remove("page_source.html")
            print("- Временный файл 'page_source.html' удалён.")
        except Exception as e:
            print(f"* Ошибка при удалении файла page_source.html: {e}")

else:
    print(f"* Ошибка при получении страницы: {response.status_code}")