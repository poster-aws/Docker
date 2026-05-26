-- =============================================================================
-- Banco — обновление comb4 (эквивалент banco_info4.py)
-- База: banco
--   mysql -h ... -u user -p banco < procedure_fill_Banco_comb4.sql
--   CALL fill_Banco_comb4();
--
-- Требует MySQL 8.0+ и существующую таблицу comb4 (после fill_Banco_comb4_full
-- или restore дампа). Только UPDATE строк, выпадавших хоть раз в banco.
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Banco_comb4//
CREATE PROCEDURE fill_Banco_comb4()
BEGIN
  DECLARE v_last_global DATE;
  DECLARE v_updated INT DEFAULT 0;

  SELECT MAX(Tirage) INTO v_last_global FROM banco;

  IF v_last_global IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb4: таблица banco пуста';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'comb4'
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb4: таблица comb4 не существует (нужен fill_Banco_comb4_full)';
  END IF;

  WITH draw_nums AS (
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
  UPDATE comb4 AS c
  INNER JOIN stats AS s
    ON c.n1 = s.n1
   AND c.n2 = s.n2
   AND c.n3 = s.n3
   AND c.n4 = s.n4
  SET
    c.Tirage = s.last_tirage,
    c.days   = DATEDIFF(v_last_global, s.last_tirage),
    c.days2  = IF(s.prev_tirage IS NULL, 0, DATEDIFF(s.last_tirage, s.prev_tirage)),
    c.fois   = s.fois,
    c.`max`  = s.max_gap;

  SET v_updated = ROW_COUNT();

  SELECT
    v_updated AS updated_rows,
    (SELECT COUNT(*) FROM comb4) AS comb4_total,
    (SELECT COUNT(*) FROM comb4 WHERE fois > 0) AS comb4_with_hits,
    v_last_global AS last_global_tirage;

END//

DELIMITER ;

-- После новых тиражей в banco:  CALL fill_Banco_comb4();
