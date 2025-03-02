-- Создаем результирующую таблицу Q2_days
CREATE TABLE IF NOT EXISTS Q2_days (
    Tirage DATE NOT NULL,
    n1 INT NOT NULL,
    n2 INT NOT NULL,
    days INT,
    PRIMARY KEY (Tirage, n1, n2)
);

-- Вставляем данные в таблицу Q2_days с вычислением days
INSERT INTO Q2_days (Tirage, n1, n2, days)
SELECT 
    t1.Tirage,
    t1.n1,
    t1.n2,
    DATEDIFF(t1.Tirage, (
        SELECT MAX(t2.Tirage)
        FROM Q2 t2
        WHERE t1.n1 = t2.n1 AND t1.n2 = t2.n2 AND t2.Tirage < t1.Tirage
    )) AS days
FROM Q2 t1;