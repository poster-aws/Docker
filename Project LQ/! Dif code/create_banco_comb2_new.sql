USE banco;

-- 1️⃣ Удаляем таблицу, если уже есть
DROP TABLE IF EXISTS comb2;

-- 2️⃣ Создаём оптимизированную таблицу comb2
CREATE TABLE comb2 (
  n1 TINYINT UNSIGNED NOT NULL,
  n2 TINYINT UNSIGNED NOT NULL,
  Tirage DATE DEFAULT NULL,
  days SMALLINT DEFAULT NULL,
  days2 SMALLINT DEFAULT NULL,
  fois SMALLINT DEFAULT NULL,
  max SMALLINT DEFAULT NULL,
  PRIMARY KEY (n1, n2)   -- 🔥 кластерный индекс по паре
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3️⃣ Процедура заполнения всех комбинаций (n1 < n2, от 1 до 70)
DELIMITER $$

CREATE PROCEDURE fill_comb2()
BEGIN
  DECLARE i INT DEFAULT 1;
  DECLARE j INT;

  WHILE i <= 69 DO          -- n1: 1..69
    SET j = i + 1;
    WHILE j <= 70 DO        -- n2: (i+1)..70
      INSERT INTO comb2 (n1, n2) VALUES (i, j);
      SET j = j + 1;
    END WHILE;
    SET i = i + 1;
  END WHILE;
END$$

DELIMITER ;

-- 4️⃣ Запускаем заполнение
CALL fill_comb2();

-- 5️⃣ Удаляем процедуру, чтобы не мешалась
DROP PROCEDURE fill_comb2;

-- 6️⃣ Проверяем количество комбинаций (C(70,2) = 2415)
SELECT COUNT(*) AS total_combinations FROM comb2;