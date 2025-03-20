-- Создаем результирующую таблицу Q2_days
CREATE TABLE IF NOT EXISTS Q2_days (
    Tirage DATE NOT NULL,
    n1 INT NOT NULL,
    n2 INT NOT NULL,
    days INT,
    PRIMARY KEY (Tirage, n1, n2)
);

-- Очищаем таблицу перед вставкой данных
TRUNCATE TABLE Q2_days;

-- Оптимизированный INSERT с COALESCE для NULL значений
-- INSERT INTO Q2_days (Tirage, n1, n2, days)
SELECT 
    t1.Tirage,
    t1.n1,
    t1.n2,
    COALESCE(
        DATEDIFF(t1.Tirage, (
            SELECT MAX(t2.Tirage)
            FROM Q2 t2
            WHERE t1.n1 = t2.n1 AND t1.n2 = t2.n2 AND t2.Tirage < t1.Tirage
        )), 0
    ) AS days
FROM Q2 t1;

-- Оптимизация: создаем индекс для ускорения поиска
-- CREATE INDEX IF NOT EXISTS idx_Q2 ON Q2 (n1, n2, Tirage);