USE banco;

-- 1️⃣ Удаляем, если уже существует
DROP TABLE IF EXISTS comb4;

-- 2️⃣ Создаём таблицу для комбинаций из 4 чисел (1–70)
CREATE TABLE comb4 (
  n1 TINYINT UNSIGNED NOT NULL,
  n2 TINYINT UNSIGNED NOT NULL,
  n3 TINYINT UNSIGNED NOT NULL,
  n4 TINYINT UNSIGNED NOT NULL,
  Tirage DATE DEFAULT NULL,
  days  SMALLINT DEFAULT NULL,
  days2 SMALLINT DEFAULT NULL,
  fois  SMALLINT DEFAULT NULL,
  max   SMALLINT DEFAULT NULL,
  PRIMARY KEY (n1, n2, n3, n4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3️⃣ Заполняем таблицу всеми уникальными комбинациями (n1 < n2 < n3 < n4)
DELIMITER $$

CREATE PROCEDURE fill_comb4()
BEGIN
  DECLARE i INT DEFAULT 1;
  DECLARE j INT;
  DECLARE k INT;
  DECLARE l INT;

  WHILE i <= 67 DO              -- n1: 1 → 67
    SET j = i + 1;
    WHILE j <= 68 DO            -- n2: i+1 → 68
      SET k = j + 1;
      WHILE k <= 69 DO          -- n3: j+1 → 69
        SET l = k + 1;
        WHILE l <= 70 DO        -- n4: k+1 → 70
          INSERT INTO comb4 (n1, n2, n3, n4) VALUES (i, j, k, l);
          SET l = l + 1;
        END WHILE;
        SET k = k + 1;
      END WHILE;
      SET j = j + 1;
    END WHILE;
    SET i = i + 1;
  END WHILE;
END$$

DELIMITER ;

-- 4️⃣ Запускаем процедуру
CALL fill_comb4();

-- 5️⃣ Удаляем процедуру, чтобы не мешала
DROP PROCEDURE fill_comb4;

-- 6️⃣ Проверка количества строк
SELECT COUNT(*) AS total_combinations FROM comb4;