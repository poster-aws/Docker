-- Ajout dans Astro_stats des lignes du dernier Tirage (date max dans Astro).
-- Conversion mois/signe chaîne → numéro comme dans fill_Astro_stats_full.sql.
-- Nécessite MySQL 8.0+
--
-- Установка:
--   выполнить этот файл целиком (индекс создастся только если его ещё нет)
-- Запуск после нового тиража:
--   CALL fill_Astro_stats();

-- Индекс для точечного поиска по комбинации в Astro_stats
SET @idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'Astro_stats'
      AND index_name = 'idx_combo_tirage'
);

SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE Astro_stats ADD KEY idx_combo_tirage (jour, mois, annee, signe, Tirage)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELIMITER $$

DROP PROCEDURE IF EXISTS fill_Astro_stats$$

CREATE PROCEDURE fill_Astro_stats()
BEGIN

DECLARE v_astro_max DATE;
DECLARE v_stats_max DATE;

SELECT MAX(Tirage) INTO v_astro_max FROM Astro;
SELECT MAX(Tirage) INTO v_stats_max FROM Astro_stats;

IF v_astro_max IS NOT NULL
   AND (v_stats_max IS NULL OR v_astro_max <> v_stats_max) THEN

-- 1. Вставка в Astro_stats
INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)

SELECT
    l.Tirage,
    l.jour,
    l.mois,
    l.annee,
    l.signe,

    -- сколько раз было раньше + 1 (точечный поиск по idx_combo_tirage)
    IFNULL((
        SELECT COUNT(*)
        FROM Astro_stats a
        WHERE a.jour = l.jour
          AND a.mois = l.mois
          AND a.annee = l.annee
          AND a.signe = l.signe
          AND a.Tirage < l.Tirage
    ), 0) + 1 AS fois,

    -- разница с последним появлением (точечный поиск по idx_combo_tirage)
    IFNULL(l.Tirage - (
        SELECT MAX(a.Tirage)
        FROM Astro_stats a
        WHERE a.jour = l.jour
          AND a.mois = l.mois
          AND a.annee = l.annee
          AND a.signe = l.signe
          AND a.Tirage < l.Tirage
    ), 0) AS days

FROM (
    -- нормализация + только последний тираж
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
    WHERE Tirage = v_astro_max

) l
WHERE l.mois IS NOT NULL
  AND l.signe IS NOT NULL;

-- 2. Обновление Astro_info (без вложенных SELECT-адов)

TRUNCATE TABLE Astro_info;

INSERT INTO Astro_info (Tirages, Comb_out)
SELECT
    (SELECT COUNT(*) FROM Astro) AS Tirages,
    COUNT(*) AS Comb_out
FROM Astro_stats
WHERE fois = 1;

END IF;

END$$

DELIMITER ;
