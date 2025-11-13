USE banco;

-- 1️⃣ Удаляем, если уже существует
DROP TABLE IF EXISTS comb3;

-- 2️⃣ Создаём таблицу для комбинаций из 3 чисел (1–70)
CREATE TABLE comb3 (
  n1 TINYINT UNSIGNED NOT NULL,
  n2 TINYINT UNSIGNED NOT NULL,
  n3 TINYINT UNSIGNED NOT NULL,
  Tirage DATE DEFAULT NULL,
  days INT DEFAULT NULL,
  days2 INT DEFAULT NULL,
  fois INT DEFAULT NULL,
  max INT DEFAULT NULL,
  UNIQUE KEY uniq_n1_n2_n3 (n1, n2, n3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3️⃣ Заполняем таблицу всеми уникальными комбинациями (n1 < n2 < n3)
DELIMITER $$

CREATE PROCEDURE fill_comb3()
BEGIN
  DECLARE i INT DEFAULT 1;
  DECLARE j INT;
  DECLARE k INT;

  WHILE i <= 68 DO              -- n1: 1 → 68
    SET j = i + 1;
    WHILE j <= 69 DO            -- n2: i+1 → 69
      SET k = j + 1;
      WHILE k <= 70 DO          -- n3: j+1 → 70
        INSERT INTO comb3 (n1, n2, n3) VALUES (i, j, k);
        SET k = k + 1;
      END WHILE;
      SET j = j + 1;
    END WHILE;
    SET i = i + 1;
  END WHILE;
END$$

DELIMITER ;

-- 4️⃣ Запускаем процедуру
CALL fill_comb3();

-- 5️⃣ Удаляем процедуру, чтобы не мешала
DROP PROCEDURE fill_comb3;

-- 6️⃣ Проверка количества строк
SELECT COUNT(*) AS total_combinations FROM comb3;