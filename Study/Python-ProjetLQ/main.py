import requests
from bs4 import BeautifulSoup
import mysql.connector
from mysql.connector import Error

# URL de la page à récupérer
url = "https://loteries.lotoquebec.com/fr/loteries/la-quotidienne"

# Effectuer une requête GET pour obtenir le contenu de la page
response = requests.get(url)

# Vérifier si la requête a réussi (code 200)
if response.status_code == 200:
    # Récupérer le code source de la page
    page_source = response.text
    
    # Sauvegarder le code source dans un fichier HTML
    with open("page_source.html", "w", encoding="utf-8") as file:
        file.write(page_source)

    print("\nLe code source a été sauvegardé dans 'page_source.html'.")
    
    # Utiliser BeautifulSoup pour parser le HTML
    soup = BeautifulSoup(page_source, 'html.parser')
    
    # Chercher tous les éléments span avec la classe "num"
    numeros = soup.find_all('span', class_='num')
    
    if len(numeros) >= 2:
        # Récupérer les deux premiers numéros
        n1 = numeros[0].text.strip()
        n2 = numeros[1].text.strip()
        
        print(f"Les deux premiers numéros trouvés sont : {n1}, {n2}")
        
        # Connexion à la base de données MySQL
        try:
            connection = mysql.connector.connect(
                host='db',       # Adresse de ton serveur MySQL
                database='loterie_db',  # Nom de la base de données
                user='root',            # Nom d'utilisateur MySQL
                password='password'     # Mot de passe de l'utilisateur MySQL
            )

            if connection.is_connected():
                cursor = connection.cursor()

                # Créer la table si elle n'existe pas
                create_table_query = """
                CREATE TABLE IF NOT EXISTS loterie (
                    n1 INT,
                    n2 INT
                );
                """
                cursor.execute(create_table_query)
                print("Table 'loterie' créée avec succès.")

                # Convertir les numéros en entiers avant l'insertion
                try:
                    n1_int = int(n1)
                    n2_int = int(n2)
                except ValueError:
                    print(f"Erreur de conversion des numéros : {n1}, {n2}. Ils ne sont pas des entiers valides.")
                    #return

                # SQL pour insérer les deux premiers numéros dans la table
                insert_query = """INSERT INTO loterie (n1, n2) VALUES (%s, %s)"""
                cursor.execute(insert_query, (n1_int, n2_int))
                connection.commit()
                print(f"Les numéros {n1_int} et {n2_int} ont été insérés dans la base de données.")
        
        except Error as e:
            print("Erreur lors de la connexion à MySQL", e)
        
        finally:
            # Fermer la connexion
            if 'connection' in locals() and connection.is_connected():
                cursor.close()
                connection.close()
                print("Connexion fermée.")
    else:
        print("Pas assez de numéros trouvés pour insérer dans la base de données.")
else:
    print(f"Erreur lors de la récupération de la page: {response.status_code}")