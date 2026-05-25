-- =============================================================================
-- Quotidienne — процедура Q_info (по образцу fill_Vie_info)
-- Выполнить в MySQL, база quotidienne (та же, что Q2 / Q3 / Q4)
--   mysql -u ... -p quotidienne < procedure_Q_info.sql
--   source procedure_Q_info.sql;
-- Требует MySQL 8.0+ (только агрегаты)
-- =============================================================================
-- Q_info — одна строка:
--   Q2 — число тиражей = COUNT(*) в таблице Q2
--   Q3 — число тиражей = COUNT(*) в таблице Q3
--   Q4 — число тиражей = COUNT(*) в таблице Q4
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Q_info//
CREATE PROCEDURE fill_Q_info()
BEGIN
  DROP TABLE IF EXISTS Q_info;
  CREATE TABLE Q_info (
      Q2 SMALLINT UNSIGNED NOT NULL,
      Q3 SMALLINT UNSIGNED NOT NULL,
      Q4 SMALLINT UNSIGNED NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  INSERT INTO Q_info (Q2, Q3, Q4)
  SELECT
      (SELECT COUNT(*) FROM Q2) AS Q2,
      (SELECT COUNT(*) FROM Q3) AS Q3,
      (SELECT COUNT(*) FROM Q4) AS Q4;
END//

DELIMITER ;

-- Пересчёт:  CALL fill_Q_info();
