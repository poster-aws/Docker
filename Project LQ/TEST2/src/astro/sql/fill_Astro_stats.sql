-- Ajout dans Astro_stats des lignes du dernier Tirage (date max dans Astro).
-- Conversion mois/signe chaîne → numéro comme dans fill_Astro_stats_full.sql.
-- Nécessite MySQL 8.0+

INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)
WITH conv AS (
    SELECT
        Tirage, jour,
        CASE mois
            WHEN 'Janvier' THEN 1 WHEN 'Février' THEN 2 WHEN 'Mars' THEN 3 WHEN 'Avril' THEN 4
            WHEN 'Mai' THEN 5 WHEN 'Juin' THEN 6 WHEN 'Juillet' THEN 7 WHEN 'Août' THEN 8
            WHEN 'Septembre' THEN 9 WHEN 'Octobre' THEN 10 WHEN 'Novembre' THEN 11 WHEN 'Décembre' THEN 12
            ELSE NULL
        END AS mois,
        annee,
        CASE UPPER(TRIM(BINARY signe))
            WHEN 'BÉLIER' THEN 1 WHEN 'TAUREAU' THEN 2 WHEN 'GÉMEAUX' THEN 3 WHEN 'CANCER' THEN 4
            WHEN 'LION' THEN 5 WHEN 'VIERGE' THEN 6 WHEN 'BALANCE' THEN 7 WHEN 'SCORPION' THEN 8
            WHEN 'SAGITTAIRE' THEN 9 WHEN 'CAPRICORNE' THEN 10 WHEN 'VERSEAU' THEN 11 WHEN 'POISSONS' THEN 12
            ELSE NULL
        END AS signe
    FROM Astro
    WHERE Tirage = (SELECT MAX(Tirage) FROM Astro)
),
latest_data AS (
    SELECT
        Tirage, jour, mois, annee, signe,
        ROW_NUMBER() OVER (PARTITION BY jour, mois, annee, signe ORDER BY jour, mois, annee, signe) AS rn
    FROM conv
    WHERE mois IS NOT NULL AND signe IS NOT NULL
)
SELECT
    l.Tirage,
    l.jour,
    l.mois,
    l.annee,
    l.signe,
    (SELECT COUNT(*) FROM Astro_stats a
     WHERE a.jour = l.jour AND a.mois = l.mois AND a.annee = l.annee AND a.signe = l.signe
       AND a.Tirage < l.Tirage) + l.rn AS fois,
    IFNULL(DATEDIFF(
        l.Tirage,
        (SELECT MAX(a.Tirage) FROM Astro_stats a
         WHERE a.jour = l.jour AND a.mois = l.mois AND a.annee = l.annee AND a.signe = l.signe
           AND a.Tirage < l.Tirage)
    ), 0) AS days
FROM latest_data l;
