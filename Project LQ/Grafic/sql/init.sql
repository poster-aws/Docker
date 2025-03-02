-- Создаем таблицу, если она еще не существует
CREATE TABLE IF NOT EXISTS `data_table` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `two_char` INT NOT NULL,  -- Столбец с числом от 1 до 100
    `three_char` INT NOT NULL  -- Столбец с числом от 1 до 400
);

-- Генерация 200 записей с случайными значениями
DELIMITER $$

CREATE PROCEDURE generate_random_data()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE rand_two_char INT;
    DECLARE rand_three_char INT;

    -- Цикл для вставки 200 записей
    WHILE i <= 200 DO
        -- Генерация случайного значения для two_char (от 1 до 100)
        SET rand_two_char = FLOOR(1 + (RAND() * 100));
        
        -- Генерация случайного значения для three_char (от 1 до 400)
        SET rand_three_char = FLOOR(1 + (RAND() * 200));
        
        -- Вставка данных в таблицу
        INSERT INTO `data_table` (`two_char`, `three_char`) 
        VALUES (rand_two_char, rand_three_char);
        
        -- Увеличение счетчика
        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;

-- Вызов процедуры для генерации данных
CALL generate_random_data();
