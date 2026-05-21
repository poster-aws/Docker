-- Ajout dans Astro_stats des lignes du dernier Tirage (date max dans Astro).
-- Conversion mois/signe chaîne → numéro comme dans fill_Astro_stats_full.sql.
-- Nécessite MySQL 8.0+

BEGIN

-- 1. Вставка в Astro_stats
INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)

SELECT
    l.Tirage,
    l.jour,
    l.mois,
    l.annee,
    l.signe,

    -- сколько раз было раньше + 1
    IFNULL(prev.cnt, 0) + 1 AS fois,

    -- разница с последним появлением
    IFNULL(l.Tirage - prev.last_tirage, 0) AS days

FROM (
    -- нормализация + только последний тираж
    SELECT
        Tirage,
        jour,

        CASE mois
            WHEN 'Janvier' THEN 1 WHEN 'Février' THEN 2 WHEN 'Mars' THEN 3 WHEN 'Avril' THEN 4
            WHEN 'Mai' THEN 5 WHEN 'Juin' THEN 6 WHEN 'Juillet' THEN 7 WHEN 'Août' THEN 8
            WHEN 'Septembre' THEN 9 WHEN 'Octobre' THEN 10 WHEN 'Novembre' THEN 11 WHEN 'Décembre' THEN 12
        END AS mois,

        annee,

        CASE UPPER(TRIM(signe))
            WHEN 'BÉLIER' THEN 1 WHEN 'TAUREAU' THEN 2 WHEN 'GÉMEAUX' THEN 3 WHEN 'CANCER' THEN 4
            WHEN 'LION' THEN 5 WHEN 'VIERGE' THEN 6 WHEN 'BALANCE' THEN 7 WHEN 'SCORPION' THEN 8
            WHEN 'SAGITTAIRE' THEN 9 WHEN 'CAPRICORNE' THEN 10 WHEN 'VERSEAU' THEN 11 WHEN 'POISSONS' THEN 12
        END AS signe

    FROM Astro
    WHERE Tirage = (SELECT MAX(Tirage) FROM Astro)

) l

LEFT JOIN (
    -- агрегированная история (ОДИН раз считаем)
    SELECT
        jour, mois, annee, signe,
        COUNT(*) AS cnt,
        MAX(Tirage) AS last_tirage
    FROM Astro_stats
    GROUP BY jour, mois, annee, signe
) prev
ON  prev.jour = l.jour
AND prev.mois = l.mois
AND prev.annee = l.annee
AND prev.signe = l.signe;

-- 2. Обновление Astro_info (без вложенных SELECT-адов)

TRUNCATE TABLE Astro_info;

INSERT INTO Astro_info (Tirages, Comb_out)
SELECT
    (SELECT COUNT(*) FROM Astro) AS Tirages,
    COUNT(*) AS Comb_out
FROM Astro_stats
WHERE fois = 1;

END