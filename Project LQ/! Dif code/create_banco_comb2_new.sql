USE banco;

DROP TABLE IF EXISTS comb2;
CREATE TABLE comb2 (
  n1 TINYINT UNSIGNED NOT NULL,
  n2 TINYINT UNSIGNED NOT NULL,
  Tirage DATE,
  days INT,
  days2 INT,
  fois INT,
  max INT,
  UNIQUE KEY uniq_n1_n2 (n1, n2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заполнение всех комбинаций n1<n2
DELIMITER $$
CREATE PROCEDURE fill_comb2()
BEGIN
  DECLARE i INT DEFAULT 1;
  DECLARE j INT;
  WHILE i <= 69 DO
    SET j = i + 1;
    WHILE j <= 70 DO
      INSERT INTO comb2 (n1, n2) VALUES (i, j);
      SET j = j + 1;
    END WHILE;
    SET i = i + 1;
  END WHILE;
END$$
DELIMITER ;

CALL fill_comb2();
DROP PROCEDURE fill_comb2;

SELECT COUNT(*) FROM comb2;  -- должно показать 2415