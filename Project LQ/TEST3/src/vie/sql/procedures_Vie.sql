-- =============================================================================
-- Grande Vie — процедура Vie_info (по образцу Astro_info в procedures_Astro.sql)
-- Выполнить в MySQL, база: vie
--   mysql -u ... -p vie < procedures_Vie.sql
--   source procedures_Vie.sql;
-- Требует MySQL 8.0+ (не обязательно; здесь только агрегаты)
-- =============================================================================
-- Vie_info:
--   Tirages  — число прошедших тиражей = число различных дат Tirage в таблице Vie
--   Comb_out — число уникальных комбинаций (n1,n2,n3,n4,n5,GN), которые
--              встретились ровно один раз (аналог Astro: GROUP BY … HAVING COUNT(*) = 1)
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Vie_info//
CREATE PROCEDURE fill_Vie_info()
BEGIN
  CREATE TABLE IF NOT EXISTS Vie_info (
      Tirages SMALLINT UNSIGNED NOT NULL,
      Comb_out SMALLINT UNSIGNED NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  TRUNCATE TABLE Vie_info;

  INSERT INTO Vie_info (Tirages, Comb_out)
  SELECT
      (SELECT COUNT(DISTINCT Tirage) FROM Vie) AS Tirages,
      (
          SELECT COUNT(*)
          FROM (
              SELECT n1, n2, n3, n4, n5, GN
              FROM Vie
              GROUP BY n1, n2, n3, n4, n5, GN
              HAVING COUNT(*) = 1
          ) AS uniq_combos
      ) AS Comb_out;
END//

DELIMITER ;

-- Пересчёт агрегатов:  CALL fill_Vie_info();
