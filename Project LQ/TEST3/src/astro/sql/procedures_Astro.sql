-- =============================================================================
-- Процедуры Astro: fill_Astro_stats_full, fill_Astro_stats
-- Выполнить в MySQL: source procedures_Astro.sql;
-- Требует MySQL 8.0+
-- =============================================================================

DELIMITER //

-- -----------------------------------------------------------------------------
-- 1. fill_Astro_stats_full — полное формирование Astro_stats из Astro
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Astro_stats_full//
CREATE PROCEDURE fill_Astro_stats_full()
BEGIN
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

  CREATE TABLE IF NOT EXISTS Astro_info (
      Tirages SMALLINT UNSIGNED NOT NULL,
      Comb_out SMALLINT UNSIGNED NOT NULL
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

  TRUNCATE TABLE Astro_info;

  INSERT INTO Astro_info (Tirages, Comb_out)
  SELECT
      (SELECT COUNT(*) FROM Astro) AS Tirages,
      (
          SELECT COUNT(*)
          FROM (
              SELECT jour, mois, annee, signe
              FROM Astro_stats
              GROUP BY jour, mois, annee, signe
              HAVING COUNT(*) = 1
          ) AS uniq_combos
      ) AS Comb_out;
END//

-- -----------------------------------------------------------------------------
-- 2. fill_Astro_stats — добавить строки только за последний Tirage (date max в Astro)
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Astro_stats//
CREATE PROCEDURE fill_Astro_stats()
BEGIN
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

  TRUNCATE TABLE Astro_info;

  INSERT INTO Astro_info (Tirages, Comb_out)
  SELECT
      (SELECT COUNT(*) FROM Astro) AS Tirages,
      (
          SELECT COUNT(*)
          FROM (
              SELECT jour, mois, annee, signe
              FROM Astro_stats
              GROUP BY jour, mois, annee, signe
              HAVING COUNT(*) = 1
          ) AS uniq_combos
      ) AS Comb_out;
END//

DELIMITER ;

-- Вызов полной пересборки:   CALL fill_Astro_stats_full();
-- Добавить последний тираж:  CALL fill_Astro_stats();
