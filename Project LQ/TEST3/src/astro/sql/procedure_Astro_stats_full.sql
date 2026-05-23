-- Формирование полной таблицы Astro_stats из Astro
-- Astro: mois et signe en chaînes (lisible). Astro_stats: mois et signe en numéros (analyse, index).
-- mois: 1=Janvier … 12=Décembre. signe: 1=Bélier … 12=Poissons (ordre zodiacal).
-- fois = nombre d’occurrences de (jour, mois, annee, signe) jusqu’à ce tirage; days = jours depuis la précédente.
-- Nécessite MySQL 8.0+
--
-- Установка:
--   выполнить этот файл целиком
-- Полная пересборка:
--   CALL fill_Astro_stats_full();

DELIMITER $$

DROP PROCEDURE IF EXISTS fill_Astro_stats_full$$

CREATE PROCEDURE fill_Astro_stats_full()
BEGIN

DROP TABLE IF EXISTS Astro_stats;
DROP TABLE IF EXISTS Astro_info;

CREATE TABLE Astro_stats (
    Tirage DATE NOT NULL,
    jour TINYINT UNSIGNED NOT NULL,
    mois TINYINT UNSIGNED NOT NULL,
    annee TINYINT UNSIGNED NOT NULL,
    signe TINYINT UNSIGNED NOT NULL,
    fois INT UNSIGNED NOT NULL,
    days INT UNSIGNED NOT NULL,
    PRIMARY KEY (Tirage, jour, mois, annee, signe),
    KEY idx_main (jour, mois, annee, signe, Tirage)
) ENGINE=InnoDB;

CREATE TABLE Astro_info (
    Tirages SMALLINT UNSIGNED NOT NULL,
    Comb_out SMALLINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Основная вставка (без лишних CTE)
INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)

SELECT
    Tirage,
    jour,
    mois,
    annee,
    signe,

    -- номер появления
    ROW_NUMBER() OVER (
        PARTITION BY jour, mois, annee, signe
        ORDER BY Tirage
    ) AS fois,

    -- разница с предыдущим
    IFNULL(
        Tirage - LAG(Tirage) OVER (
            PARTITION BY jour, mois, annee, signe
            ORDER BY Tirage
        ),
        0
    ) AS days

FROM (
    -- нормализация (один проход)
    SELECT
        Tirage,
        jour,

        CASE mois
            WHEN 'Janvier' THEN 1 WHEN 'Février' THEN 2 WHEN 'Mars' THEN 3 WHEN 'Avril' THEN 4
            WHEN 'Mai' THEN 5 WHEN 'Juin' THEN 6 WHEN 'Juillet' THEN 7 WHEN 'Août' THEN 8
            WHEN 'Septembre' THEN 9 WHEN 'Octobre' THEN 10 WHEN 'Novembre' THEN 11 WHEN 'Décembre' THEN 12
        END AS mois,

        annee,

        CASE UPPER(TRIM(BINARY signe))
            WHEN 'BÉLIER' THEN 1 WHEN 'TAUREAU' THEN 2 WHEN 'GÉMEAUX' THEN 3 WHEN 'CANCER' THEN 4
            WHEN 'LION' THEN 5 WHEN 'VIERGE' THEN 6 WHEN 'BALANCE' THEN 7 WHEN 'SCORPION' THEN 8
            WHEN 'SAGITTAIRE' THEN 9 WHEN 'CAPRICORNE' THEN 10 WHEN 'VERSEAU' THEN 11 WHEN 'POISSONS' THEN 12
            ELSE NULL
        END AS signe

    FROM Astro
) t

WHERE mois IS NOT NULL AND signe IS NOT NULL;

-- 2. Astro_info

INSERT INTO Astro_info (Tirages, Comb_out)
SELECT
    (SELECT COUNT(*) FROM Astro) AS Tirages,
    COUNT(*) AS Comb_out
FROM Astro_stats
WHERE fois = 1;

END$$

DELIMITER ;
