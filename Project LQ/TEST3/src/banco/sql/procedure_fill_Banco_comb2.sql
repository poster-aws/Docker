-- =============================================================================
-- Banco — обновление comb2 (эквивалент banco_info2.py)
-- База: banco
--   mysql -h ... -u user -p banco < procedure_fill_Banco_comb2.sql
--   CALL fill_Banco_comb2();
--
-- Требует MySQL 8.0+ и существующую таблицу comb2 (после fill_Banco_comb2_full
-- или restore дампа). Только UPDATE строк, выпадавших хоть раз в banco.
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS fill_Banco_comb2//
CREATE PROCEDURE fill_Banco_comb2()
BEGIN
  DECLARE v_last_global DATE;
  DECLARE v_updated INT DEFAULT 0;

  SELECT MAX(Tirage) INTO v_last_global FROM banco;

  IF v_last_global IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb2: таблица banco пуста';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'comb2'
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'fill_Banco_comb2: таблица comb2 не существует (нужен fill_Banco_comb2_full)';
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
  UPDATE comb2 AS c
  INNER JOIN stats AS s
    ON c.n1 = s.n1 AND c.n2 = s.n2
  SET
    c.Tirage = s.last_tirage,
    c.days   = DATEDIFF(v_last_global, s.last_tirage),
    c.days2  = IF(s.prev_tirage IS NULL, 0, DATEDIFF(s.last_tirage, s.prev_tirage)),
    c.fois   = s.fois,
    c.`max`  = s.max_gap;

  SET v_updated = ROW_COUNT();

  SELECT
    v_updated AS updated_rows,
    (SELECT COUNT(*) FROM comb2) AS comb2_total,
    (SELECT COUNT(*) FROM comb2 WHERE fois > 0) AS comb2_with_hits,
    v_last_global AS last_global_tirage;

END//

DELIMITER ;

-- После новых тиражей в banco:  CALL fill_Banco_comb2();
