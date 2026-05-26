-- =============================================================================
-- Banco — полный пересчёт comb2 (DROP + CREATE + INSERT, эквивалент banco_info2.py)
-- База: banco
--   mysql -h ... -u user -p banco < procedure_fill_Banco_comb2_full.sql
--   CALL fill_Banco_comb2_full();
--
-- Требует MySQL 8.0+ (JSON_TABLE, оконные функции, RECURSIVE CTE).
-- Первичное развёртывание / полная пересборка таблицы comb2.
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Banco_comb2_full//
CREATE PROCEDURE fill_Banco_comb2_full()
BEGIN
  DECLARE v_last_global DATE;

  SELECT MAX(Tirage) INTO v_last_global FROM banco;

  IF v_last_global IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb2_full: таблица banco пуста';
  END IF;

  DROP TABLE IF EXISTS comb2;
  CREATE TABLE comb2 (
    n1 TINYINT UNSIGNED NOT NULL,
    n2 TINYINT UNSIGNED NOT NULL,
    Tirage DATE DEFAULT NULL,
    days SMALLINT UNSIGNED DEFAULT NULL,
    days2 SMALLINT UNSIGNED DEFAULT NULL,
    fois SMALLINT UNSIGNED DEFAULT NULL,
    `max` SMALLINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (n1, n2)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  INSERT INTO comb2 (n1, n2, Tirage, days, days2, fois, `max`)
  WITH RECURSIVE nums AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM nums WHERE n < 70
  ),
  all_pairs AS (
    SELECT a.n AS n1, b.n AS n2
    FROM nums a
    CROSS JOIN nums b
    WHERE a.n < b.n
  ),
  draw_nums AS (
    SELECT
      b.Tirage,
      nums.n
    FROM banco b
    CROSS JOIN JSON_TABLE(
      JSON_ARRAY(
        b.n1, b.n2, b.n3, b.n4, b.n5, b.n6, b.n7, b.n8, b.n9, b.n10,
        b.n11, b.n12, b.n13, b.n14, b.n15, b.n16, b.n17, b.n18, b.n19, b.n20
      ),
      '$[*]' COLUMNS (n TINYINT UNSIGNED PATH '$')
    ) AS nums
    WHERE nums.n IS NOT NULL
  ),
  pairs AS (
    SELECT
      a.Tirage,
      a.n AS n1,
      b.n AS n2
    FROM draw_nums a
    INNER JOIN draw_nums b
      ON a.Tirage = b.Tirage
     AND a.n < b.n
  ),
  occ AS (
    SELECT
      p.n1,
      p.n2,
      p.Tirage,
      ROW_NUMBER() OVER (
        PARTITION BY p.n1, p.n2
        ORDER BY p.Tirage DESC
      ) AS rn_desc,
      DATEDIFF(
        p.Tirage,
        LAG(p.Tirage) OVER (
          PARTITION BY p.n1, p.n2
          ORDER BY p.Tirage
        )
      ) AS gap
    FROM pairs p
  ),
  stats AS (
    SELECT
      o.n1,
      o.n2,
      MAX(CASE WHEN o.rn_desc = 1 THEN o.Tirage END) AS last_tirage,
      MAX(CASE WHEN o.rn_desc = 2 THEN o.Tirage END) AS prev_tirage,
      COUNT(*) AS fois,
      IFNULL(MAX(o.gap), 0) AS max_gap
    FROM occ o
    GROUP BY o.n1, o.n2
  )
  SELECT
    p.n1,
    p.n2,
    s.last_tirage,
    CASE
      WHEN s.n1 IS NOT NULL THEN DATEDIFF(v_last_global, s.last_tirage)
    END,
    CASE
      WHEN s.n1 IS NULL THEN NULL
      WHEN s.prev_tirage IS NULL THEN 0
      ELSE DATEDIFF(s.last_tirage, s.prev_tirage)
    END,
    IFNULL(s.fois, 0),
    s.max_gap
  FROM all_pairs p
  LEFT JOIN stats s
    ON p.n1 = s.n1 AND p.n2 = s.n2;

  SELECT
    (SELECT COUNT(*) FROM comb2) AS comb2_total,
    (SELECT COUNT(*) FROM comb2 WHERE fois > 0) AS comb2_with_hits,
    v_last_global AS last_global_tirage;

END//

DELIMITER ;

-- Полный пересчёт:  CALL fill_Banco_comb2_full();
