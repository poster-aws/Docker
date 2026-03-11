# TEST3 — Quotidienne (nouvelle interface)

Version du projet avec **uniquement les pages Quotidienne 2, 3 et 4**, et une **nouvelle interface** adaptée ordinateur et mobile.

**TEST3 utilise la même base MySQL que TEST2** (réseau Docker `mysql_net`). Aucun conteneur `db` ni `restore` : un seul SGBD pour les deux applis.

## Lancer le projet

1. Démarrer d’abord TEST2 (pour créer le réseau et la base) :
   ```bash
   cd TEST2
   docker compose up -d
   ```

2. Puis lancer TEST3 :
   ```bash
   cd TEST3
   docker compose up -d
   ```

- **TEST2** : http://localhost:80 — **TEST3** : http://localhost:8081  
- **Adminer** (TEST2) : http://localhost:8080 — (TEST3) : http://localhost:8082 — même base.

Pour restaurer les backups (dossier `../BackupDB`), utiliser TEST2 : `cd TEST2 && docker compose up db restore`.

## Interface

- **Header** : titre, menu (Q2 / Q3 / Q4), langue FR/EN, switch Order / N'import, bouton Info.
- **Mobile** : menu hamburger qui ouvre la navigation ; tables en défilement horizontal si besoin.
- **Desktop** : navigation en ligne, tables en grille.
- **Tables** : cartes avec bordures arrondies, en-têtes fixes, chiffres en pastilles, lignes triables au clic.

## Structure

- `src/index.html` — page d’accueil (nouvelle maquette).
- `src/style.css` — styles globaux (header, titre, chargement).
- `src/script.js` — chargement des pages Quotidienne, langue, switch, info.
- `src/quotidienne/` — PHP et templates Q2/Q3/Q4, QInfo, `quotidienne.css` pour les tables.

Les ports (8081, 8082, 3307) sont différents de TEST2 pour pouvoir tourner en parallèle.
