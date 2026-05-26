-- =============================================================================
-- Banco — полный пересчёт comb3 (DROP + CREATE + INSERT, эквивалент banco_info3.py)
-- База: banco
--   mysql -h ... -u user -p banco < procedure_fill_Banco_comb3_full.sql
--   CALL fill_Banco_comb3_full();
--
-- Требует MySQL 8.0+ (JSON_TABLE, оконные функции, RECURSIVE CTE).
-- Первичное развёртывание / полная пересборка таблицы comb3.
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Banco_comb3_full//
CREATE PROCEDURE fill_Banco_comb3_full()
BEGIN
  DECLARE v_last_global DATE;

  SELECT MAX(Tirage) INTO v_last_global FROM banco;

  IF v_last_global IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb3_full: таблица banco пуста';
  END IF;

  DROP TABLE IF EXISTS comb3;
  CREATE TABLE comb3 (
    n1 TINYINT UNSIGNED NOT NULL,
    n2 TINYINT UNSIGNED NOT NULL,
    n3 TINYINT UNSIGNED NOT NULL,
    Tirage DATE DEFAULT NULL,
    days SMALLINT UNSIGNED DEFAULT NULL,
    days2 SMALLINT UNSIGNED DEFAULT NULL,
    fois SMALLINT UNSIGNED DEFAULT NULL,
    `max` SMALLINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (n1, n2, n3)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  INSERT INTO comb3 (n1, n2, n3, Tirage, days, days2, fois, `max`)
  WITH RECURSIVE nums AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM nums WHERE n < 70
  ),
  all_triples AS (
    SELECT a.n AS n1, b.n AS n2, c.n AS n3
    FROM nums a
    CROSS JOIN nums b
    CROSS JOIN nums c
    WHERE a.n < b.n AND b.n < c.n
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
  triples AS (
    SELECT
      a.Tirage,
      a.n AS n1,
      b.n AS n2,
      c.n AS n3
    FROM draw_nums a
    INNER JOIN draw_nums b
      ON a.Tirage = b.Tirage
     AND a.n < b.n
    INNER JOIN draw_nums c
      ON a.Tirage = c.Tirage
     AND b.n < c.n
  ),
  occ AS (
    SELECT
      t.n1,
      t.n2,
      t.n3,
      t.Tirage,
      ROW_NUMBER() OVER (
        PARTITION BY t.n1, t.n2, t.n3
        ORDER BY t.Tirage DESC
      ) AS rn_desc,
      DATEDIFF(
        t.Tirage,
        LAG(t.Tirage) OVER (
          PARTITION BY t.n1, t.n2, t.n3
          ORDER BY t.Tirage
        )
      ) AS gap
    FROM triples t
  ),
  stats AS (
    SELECT
      o.n1,
      o.n2,
      o.n3,
      MAX(CASE WHEN o.rn_desc = 1 THEN o.Tirage END) AS last_tirage,
      MAX(CASE WHEN o.rn_desc = 2 THEN o.Tirage END) AS prev_tirage,
      COUNT(*) AS fois,
      IFNULL(MAX(o.gap), 0) AS max_gap
    FROM occ o
    GROUP BY o.n1, o.n2, o.n3
  )
  SELECT
    t.n1,
    t.n2,
    t.n3,
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
  FROM all_triples t
  LEFT JOIN stats s
    ON t.n1 = s.n1 AND t.n2 = s.n2 AND t.n3 = s.n3;

  SELECT
    (SELECT COUNT(*) FROM comb3) AS comb3_total,
    (SELECT COUNT(*) FROM comb3 WHERE fois > 0) AS comb3_with_hits,
    v_last_global AS last_global_tirage;

END//

DELIMITER ;

-- Полный пересчёт:  CALL fill_Banco_comb3_full();
