FROM python:3.9-slim

WORKDIR /app

# Копируем все файлы из контекста сборки в /app.
COPY . .

# Если init.sh лежит в поддиректории "files", можно скопировать его так:
# COPY files/init.sh .

# Обновляем списки пакетов и устанавливаем MySQL-клиент
RUN apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*
# Устанавливаем зависимости
RUN pip install --no-cache-dir requests
RUN pip install beautifulsoup4 requests
RUN pip install mysql-connector-python

# Делаем init.sh исполняемым (предполагается, что init.sh находится в /app)
RUN chmod +x init.sh

# Выполнение main.py, а затем init.sh
CMD ["sh", "-c", "python main.py && ./init.sh"]