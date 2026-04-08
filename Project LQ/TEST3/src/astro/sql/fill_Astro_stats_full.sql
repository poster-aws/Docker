-- Формирование полной таблицы Astro_stats из Astro
-- Astro: mois et signe en chaînes (lisible). Astro_stats: mois et signe en numéros (analyse, index).
-- mois: 1=Janvier … 12=Décembre. signe: 1=Bélier … 12=Poissons (ordre zodiacal).
-- fois = nombre d’occurrences de (jour, mois, annee, signe) jusqu’à ce tirage; days = jours depuis la précédente.
-- Nécessite MySQL 8.0+

DROP TABLE IF EXISTS Astro_stats;

CREATE TABLE Astro_stats (
    Tirage DATE NOT NULL,
    jour TINYINT UNSIGNED NOT NULL,
    mois TINYINT UNSIGNED NOT NULL COMMENT '1-12',
    annee TINYINT UNSIGNED NOT NULL,
    signe TINYINT UNSIGNED NOT NULL COMMENT '1-12 ordre zodiacal',
    fois INT UNSIGNED NOT NULL DEFAULT 0,
    days INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (Tirage, jour, mois, annee, signe),
    KEY (mois),
    KEY (signe),
    KEY (jour, mois, signe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)
WITH conv AS (
    SELECT
        Tirage,
        jour,
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
),
base AS (
    SELECT
        Tirage, jour, mois, annee, signe,
        LAG(Tirage) OVER (PARTITION BY jour, mois, annee, signe ORDER BY Tirage) AS prev_Tirage,
        ROW_NUMBER() OVER (PARTITION BY jour, mois, annee, signe ORDER BY Tirage) AS fois
    FROM conv
    WHERE mois IS NOT NULL AND signe IS NOT NULL
)
SELECT
    Tirage, jour, mois, annee, signe,
    fois,
    IFNULL(DATEDIFF(Tirage, prev_Tirage), 0) AS days
FROM base
ORDER BY Tirage;
