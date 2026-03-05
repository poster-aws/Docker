-- =============================================================================
-- Процедуры Q3: создание всех 6 процедур заполнения таблиц (комбинации из 3 цифр)
-- Выполнить целиком в MySQL (например: source procedures_Q3.sql;)
-- Требует MySQL 8.0+
-- =============================================================================

DELIMITER //

-- -----------------------------------------------------------------------------
-- 1. fill_Q3_stats_order — добавить одну строку (последний тираж из Q3)
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Q3_stats_order//
CREATE PROCEDURE fill_Q3_stats_order()
BEGIN
  INSERT INTO Q3_stats_order (Tirage, n1, n2, n3, days, days2, fois, max_days)
  SELECT
      latest.Tirage,
      latest.n1,
      latest.n2,
      latest.n3,
      IFNULL(DATEDIFF(
          latest.Tirage,
          (SELECT MAX(Tirage) FROM Q3
           WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND Tirage < latest.Tirage)
      ), 0),
      IFNULL((SELECT DATEDIFF(prev.Tirage, prev2.Tirage)
       FROM (SELECT MAX(Tirage) AS Tirage FROM Q3
             WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND Tirage < latest.Tirage) AS prev
       JOIN (SELECT MAX(Tirage) AS Tirage FROM Q3
             WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3
               AND Tirage < (SELECT MAX(Tirage) FROM Q3
                             WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND Tirage < latest.Tirage)) AS prev2 ON 1
       WHERE prev.Tirage IS NOT NULL AND prev2.Tirage IS NOT NULL
       LIMIT 1), 0),
      (SELECT COUNT(*) FROM Q3
       WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND Tirage <= latest.Tirage),
      (SELECT IFNULL(MAX(gap), 0)
       FROM (SELECT DATEDIFF(Tirage, LAG(Tirage) OVER (ORDER BY Tirage)) AS gap
             FROM Q3
             WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND Tirage <= latest.Tirage) AS t
       WHERE gap IS NOT NULL)
  FROM (SELECT Tirage, n1, n2, n3 FROM Q3 ORDER BY Tirage DESC LIMIT 1) AS latest;
END//

-- -----------------------------------------------------------------------------
-- 2. fill_Q3_stats_order_full — полное формирование Q3_stats_order
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Q3_stats_order_full//
CREATE PROCEDURE fill_Q3_stats_order_full()
BEGIN
  DROP TABLE IF EXISTS Q3_stats_order;
  CREATE TABLE Q3_stats_order (
      Tirage DATE,
      n1 TINYINT UNSIGNED,
      n2 TINYINT UNSIGNED,
      n3 TINYINT UNSIGNED,
      days INT NULL,
      days2 INT NULL,
      fois INT NULL,
      max_days INT NULL
  );
  INSERT INTO Q3_stats_order (Tirage, n1, n2, n3, days, days2, fois, max_days)
  WITH
  base AS (
      SELECT
          Tirage, n1, n2, n3,
          LAG(Tirage, 1) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage) AS prev_Tirage,
          LAG(Tirage, 2) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage) AS prev2_Tirage,
          ROW_NUMBER() OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage) AS fois,
          DATEDIFF(Tirage, LAG(Tirage, 1) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage)) AS gap
      FROM Q3
  ),
  computed AS (
      SELECT
          Tirage, n1, n2, n3,
          IFNULL(gap, 0) AS days,
          CASE
              WHEN prev_Tirage IS NOT NULL AND prev2_Tirage IS NOT NULL
              THEN DATEDIFF(prev_Tirage, prev2_Tirage)
              ELSE 0
          END AS days2,
          fois,
          IFNULL(MAX(gap) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage ROWS UNBOUNDED PRECEDING), 0) AS max_gap
      FROM base
  )
  SELECT Tirage, n1, n2, n3, days, days2, fois, max_gap AS max_days FROM computed;
END//

-- -----------------------------------------------------------------------------
-- 3. fill_Q3_stats_norder — добавить одну строку (последний тираж, порядок не важен)
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Q3_stats_norder//
CREATE PROCEDURE fill_Q3_stats_norder()
BEGIN
  INSERT INTO Q3_stats_norder (Tirage, n1, n2, n3, days, days2, fois, max_days)
  SELECT
      latest.Tirage,
      latest.n1,
      latest.n2,
      latest.n3,
      IFNULL(DATEDIFF(
          latest.Tirage,
          (SELECT MAX(Tirage) FROM Q3
           WHERE LEAST(n1, n2, n3) = latest.a
             AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
             AND GREATEST(n1, n2, n3) = latest.c
             AND Tirage < latest.Tirage)
      ), 0),
      IFNULL((SELECT DATEDIFF(prev.Tirage, prev2.Tirage)
       FROM (SELECT MAX(Tirage) AS Tirage FROM Q3
             WHERE LEAST(n1, n2, n3) = latest.a
               AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
               AND GREATEST(n1, n2, n3) = latest.c
               AND Tirage < latest.Tirage) AS prev
       JOIN (SELECT MAX(Tirage) AS Tirage FROM Q3
             WHERE LEAST(n1, n2, n3) = latest.a
               AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
               AND GREATEST(n1, n2, n3) = latest.c
               AND Tirage < (SELECT MAX(Tirage) FROM Q3
                             WHERE LEAST(n1, n2, n3) = latest.a
                               AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
                               AND GREATEST(n1, n2, n3) = latest.c AND Tirage < latest.Tirage)) AS prev2 ON 1
       WHERE prev.Tirage IS NOT NULL AND prev2.Tirage IS NOT NULL
       LIMIT 1), 0),
      (SELECT COUNT(*) FROM Q3
       WHERE LEAST(n1, n2, n3) = latest.a
         AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
         AND GREATEST(n1, n2, n3) = latest.c
         AND Tirage <= latest.Tirage),
      (SELECT IFNULL(MAX(gap), 0)
       FROM (SELECT DATEDIFF(Tirage, LAG(Tirage) OVER (ORDER BY Tirage)) AS gap
             FROM Q3
             WHERE LEAST(n1, n2, n3) = latest.a
               AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
               AND GREATEST(n1, n2, n3) = latest.c
               AND Tirage <= latest.Tirage) AS t
       WHERE gap IS NOT NULL)
  FROM (SELECT Tirage, n1, n2, n3,
               LEAST(n1, n2, n3) AS a,
               (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) AS b,
               GREATEST(n1, n2, n3) AS c
        FROM Q3 ORDER BY Tirage DESC LIMIT 1) AS latest;
END//

-- -----------------------------------------------------------------------------
-- 4. fill_Q3_stats_norder_full — полное формирование Q3_stats_norder
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Q3_stats_norder_full//
CREATE PROCEDURE fill_Q3_stats_norder_full()
BEGIN
  DROP TABLE IF EXISTS Q3_stats_norder;
  CREATE TABLE Q3_stats_norder (
      Tirage DATE,
      n1 TINYINT UNSIGNED,
      n2 TINYINT UNSIGNED,
      n3 TINYINT UNSIGNED,
      days INT NULL,
      days2 INT NULL,
      fois INT NULL,
      max_days INT NULL
  );
  INSERT INTO Q3_stats_norder (Tirage, n1, n2, n3, days, days2, fois, max_days)
  WITH
  base AS (
      SELECT
          Tirage, n1, n2, n3,
          LAG(Tirage, 1) OVER (PARTITION BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3) ORDER BY Tirage) AS prev_Tirage,
          LAG(Tirage, 2) OVER (PARTITION BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3) ORDER BY Tirage) AS prev2_Tirage,
          ROW_NUMBER() OVER (PARTITION BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3) ORDER BY Tirage) AS fois,
          DATEDIFF(Tirage, LAG(Tirage, 1) OVER (PARTITION BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3) ORDER BY Tirage)) AS gap
      FROM Q3
  ),
  computed AS (
      SELECT
          Tirage, n1, n2, n3,
          IFNULL(gap, 0) AS days,
          CASE
              WHEN prev_Tirage IS NOT NULL AND prev2_Tirage IS NOT NULL
              THEN DATEDIFF(prev_Tirage, prev2_Tirage)
              ELSE 0
          END AS days2,
          fois,
          IFNULL(MAX(gap) OVER (PARTITION BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3) ORDER BY Tirage ROWS UNBOUNDED PRECEDING), 0) AS max_gap
      FROM base
  )
  SELECT Tirage, n1, n2, n3, days, days2, fois, max_gap AS max_days FROM computed;
END//

-- -----------------------------------------------------------------------------
-- 5. fill_Q3_combo_stats_order — заполнение Q3_combo_stats_order
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Q3_combo_stats_order//
CREATE PROCEDURE fill_Q3_combo_stats_order()
BEGIN
  DROP TABLE IF EXISTS Q3_combo_stats_order;
  CREATE TABLE Q3_combo_stats_order (
      n1 TINYINT UNSIGNED,
      n2 TINYINT UNSIGNED,
      n3 TINYINT UNSIGNED,
      days INT NULL,
      Tirage DATE NULL,
      max_fois INT NULL,
      max_days INT NULL
  );
  INSERT INTO Q3_combo_stats_order (n1, n2, n3, days, Tirage, max_fois, max_days)
  SELECT
      n1, n2, n3,
      DATEDIFF(CURDATE(), MAX(Tirage)) AS days,
      MAX(Tirage) AS Tirage,
      COUNT(*) AS max_fois,
      NULL AS max_days
  FROM Q3_stats_order
  GROUP BY n1, n2, n3;
  UPDATE Q3_combo_stats_order c
  JOIN Q3_stats_order s
    ON c.n1 = s.n1 AND c.n2 = s.n2 AND c.n3 = s.n3 AND c.Tirage = s.Tirage
  SET c.max_days = s.max_days;
END//

-- -----------------------------------------------------------------------------
-- 6. fill_Q3_combo_stats_norder — заполнение Q3_combo_stats_norder
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS fill_Q3_combo_stats_norder//
CREATE PROCEDURE fill_Q3_combo_stats_norder()
BEGIN
  DROP TABLE IF EXISTS Q3_combo_stats_norder;
  CREATE TABLE Q3_combo_stats_norder (
      n1 TINYINT UNSIGNED,
      n2 TINYINT UNSIGNED,
      n3 TINYINT UNSIGNED,
      days INT NULL,
      Tirage DATE NULL,
      max_fois INT NULL,
      max_days INT NULL
  );
  INSERT INTO Q3_combo_stats_norder (n1, n2, n3, days, Tirage, max_fois, max_days)
  SELECT
      LEAST(n1, n2, n3) AS n1,
      (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) AS n2,
      GREATEST(n1, n2, n3) AS n3,
      DATEDIFF(CURDATE(), MAX(Tirage)) AS days,
      MAX(Tirage) AS Tirage,
      COUNT(*) AS max_fois,
      NULL AS max_days
  FROM Q3_stats_norder
  GROUP BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3);
  UPDATE Q3_combo_stats_norder c
  JOIN Q3_stats_norder s
    ON c.n1 = LEAST(s.n1, s.n2, s.n3)
   AND c.n2 = (s.n1 + s.n2 + s.n3 - LEAST(s.n1, s.n2, s.n3) - GREATEST(s.n1, s.n2, s.n3))
   AND c.n3 = GREATEST(s.n1, s.n2, s.n3)
   AND c.Tirage = s.Tirage
  SET c.max_days = s.max_days;
END//

DELIMITER ;

-- =============================================================================
-- Вызов после создания:
--   CALL fill_Q3_stats_order();           -- одна строка в stats_order
--   CALL fill_Q3_stats_order_full();      -- полная пересборка stats_order
--   CALL fill_Q3_stats_norder();          -- одна строка в stats_norder
--   CALL fill_Q3_stats_norder_full();     -- полная пересборка stats_norder
--   CALL fill_Q3_combo_stats_order();     -- пересборка combo order
--   CALL fill_Q3_combo_stats_norder();    -- пересборка combo norder
-- =============================================================================
