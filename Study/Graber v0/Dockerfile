FROM python:3.9-slim

WORKDIR /app

COPY . .

# Installer les dépendances nécessaires
RUN pip install --no-cache-dir requests
RUN pip install beautifulsoup4 requests
RUN pip install mysql-connector-python

# Exécuter le script Python
CMD ["python", "main.py"]