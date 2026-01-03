FROM python:3.9-slim

WORKDIR /app

# Копируем все файлы из контекста сборки в /app.
# COPY . .

# Обновляем списки пакетов и устанавливаем MySQL-клиент
RUN apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*
# Устанавливаем зависимости
RUN pip install --no-cache-dir requests
RUN pip install beautifulsoup4 requests
RUN pip install mysql-connector-python

# Выполнение main.py, а затем init.sh
# CMD ["python", "main.py"]
CMD ["sh", "-c", "python3 astro.py"]