-- =============================================================================
-- Grande Vie — процедура Vie_info (по образцу Astro_info в procedures_Astro.sql)
-- Выполнить в MySQL, база: vie
--   mysql -u ... -p vie < procedures_Vie.sql
--   source procedures_Vie.sql;
-- Требует MySQL 8.0+ (не обязательно; здесь только агрегаты)
-- =============================================================================
-- Vie_info:
--   Tirages    — число прошедших тиражей = число различных дат Tirage в таблице Vie
--   Comb_out_6 — число уникальных комбинаций (n1,n2,n3,n4,n5,GN), где GN > 0,
--                которые встретились ровно один раз
--   Comb_out_5 — число уникальных комбинаций (n1,n2,n3,n4,n5), без учёта GN и даты,
--                которые встретились ровно один раз
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Vie_info//
CREATE PROCEDURE fill_Vie_info()
BEGIN
  DROP TABLE IF EXISTS Vie_info;
  CREATE TABLE Vie_info (
      Tirages SMALLINT UNSIGNED NOT NULL,
      Comb_out_6 SMALLINT UNSIGNED NOT NULL,
      Comb_out_5 SMALLINT UNSIGNED NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  INSERT INTO Vie_info (Tirages, Comb_out_6, Comb_out_5)
  SELECT
      (SELECT COUNT(DISTINCT Tirage) FROM Vie) AS Tirages,
      (
          SELECT COUNT(*)
          FROM (
              SELECT n1, n2, n3, n4, n5, GN
              FROM Vie
              WHERE GN > 0
              GROUP BY n1, n2, n3, n4, n5, GN
              HAVING COUNT(*) = 1
          ) AS uniq_combos_6
      ) AS Comb_out_6,
      (
          SELECT COUNT(*)
          FROM (
              SELECT n1, n2, n3, n4, n5
              FROM Vie
              GROUP BY n1, n2, n3, n4, n5
              HAVING COUNT(*) = 1
          ) AS uniq_combos_5
      ) AS Comb_out_5;
END//

DELIMITER ;

-- Пересчёт агрегатов:  CALL fill_Vie_info();
