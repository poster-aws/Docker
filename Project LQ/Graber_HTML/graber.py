import requests
from bs4 import BeautifulSoup
from datetime import datetime
import os

# URL страницы
url = "https://loteries.lotoquebec.com/fr/loteries/quebec-max-resultats#res"

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

    print("\n- Исходный код Astro сохранён в 'page_source.html'.")

    # Парсим HTML (пока без использования — задел на будущее)
    soup = BeautifulSoup(page_source, 'html.parser')

    # === DEBUG (опционально): вывод даты, если есть ===
    date_elem = soup.find('div', id='dateAffichee')
    if date_elem:
        try:
            date_obj = datetime.strptime(date_elem.text.strip(), "%Y-%m-%d").date()
            print(f"- Дата извлечена : {date_obj}")
        except ValueError:
            print("* Дата найдена, но формат не YYYY-MM-DD.")
    else:
        print("- Дата не найдена")

else:
    print(f"* Ошибка при получении страницы: {response.status_code}")