-- =============================================================================
-- Banco — полный пересчёт comb4 (DROP + CREATE + INSERT, эквивалент banco_info4.py)
-- База: banco
--   mysql -h ... -u user -p banco < procedure_fill_Banco_comb4_full.sql
--   CALL fill_Banco_comb4_full();
--
-- Требует MySQL 8.0+ (JSON_TABLE, оконные функции, RECURSIVE CTE).
-- Первичное развёртывание / полная пересборка таблицы comb4.
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Banco_comb4_full//
CREATE PROCEDURE fill_Banco_comb4_full()
BEGIN
  DECLARE v_last_global DATE;

  SELECT MAX(Tirage) INTO v_last_global FROM banco;

  IF v_last_global IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb4_full: таблица banco пуста';
  END IF;

  DROP TABLE IF EXISTS comb4;
  CREATE TABLE comb4 (
    n1 TINYINT UNSIGNED NOT NULL,
    n2 TINYINT UNSIGNED NOT NULL,
    n3 TINYINT UNSIGNED NOT NULL,
    n4 TINYINT UNSIGNED NOT NULL,
    Tirage DATE DEFAULT NULL,
    days SMALLINT DEFAULT NULL,
    days2 SMALLINT DEFAULT NULL,
    fois SMALLINT DEFAULT NULL,
    `max` SMALLINT DEFAULT NULL,
    PRIMARY KEY (n1, n2, n3, n4)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  INSERT INTO comb4 (n1, n2, n3, n4, Tirage, days, days2, fois, `max`)
  WITH RECURSIVE nums AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM nums WHERE n < 70
  ),
  all_quads AS (
    SELECT
      a.n AS n1,
      b.n AS n2,
      c.n AS n3,
      d.n AS n4
    FROM nums a
    INNER JOIN nums b ON b.n > a.n
    INNER JOIN nums c ON c.n > b.n
    INNER JOIN nums d ON d.n > c.n
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
  quads AS (
    SELECT
      a.Tirage,
      a.n AS n1,
      b.n AS n2,
      c.n AS n3,
      d.n AS n4
    FROM draw_nums a
    INNER JOIN draw_nums b
      ON a.Tirage = b.Tirage
     AND a.n < b.n
    INNER JOIN draw_nums c
      ON a.Tirage = c.Tirage
     AND b.n < c.n
    INNER JOIN draw_nums d
      ON a.Tirage = d.Tirage
     AND c.n < d.n
  ),
  occ AS (
    SELECT
      q.n1,
      q.n2,
      q.n3,
      q.n4,
      q.Tirage,
      ROW_NUMBER() OVER (
        PARTITION BY q.n1, q.n2, q.n3, q.n4
        ORDER BY q.Tirage DESC
      ) AS rn_desc,
      DATEDIFF(
        q.Tirage,
        LAG(q.Tirage) OVER (
          PARTITION BY q.n1, q.n2, q.n3, q.n4
          ORDER BY q.Tirage
        )
      ) AS gap
    FROM quads q
  ),
  stats AS (
    SELECT
      o.n1,
      o.n2,
      o.n3,
      o.n4,
      MAX(CASE WHEN o.rn_desc = 1 THEN o.Tirage END) AS last_tirage,
      MAX(CASE WHEN o.rn_desc = 2 THEN o.Tirage END) AS prev_tirage,
      COUNT(*) AS fois,
      IFNULL(MAX(o.gap), 0) AS max_gap
    FROM occ o
    GROUP BY o.n1, o.n2, o.n3, o.n4
  )
  SELECT
    q.n1,
    q.n2,
    q.n3,
    q.n4,
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
  FROM all_quads q
  LEFT JOIN stats s
    ON q.n1 = s.n1
   AND q.n2 = s.n2
   AND q.n3 = s.n3
   AND q.n4 = s.n4;

  SELECT
    (SELECT COUNT(*) FROM comb4) AS comb4_total,
    (SELECT COUNT(*) FROM comb4 WHERE fois > 0) AS comb4_with_hits,
    v_last_global AS last_global_tirage;

END//

DELIMITER ;

-- Полный пересчёт:  CALL fill_Banco_comb4_full();
