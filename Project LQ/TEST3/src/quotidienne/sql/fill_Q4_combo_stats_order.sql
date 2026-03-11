-- Заполнение Q4_combo_stats_order из Q4_stats_order
-- Одна строка на каждую комбинацию (n1, n2, n3, n4); порядок важен (order).
-- Таблица: n1, n2, n3, n4, days, Tirage, max_fois, max_days

DROP TABLE IF EXISTS Q4_combo_stats_order;

CREATE TABLE Q4_combo_stats_order (
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    n3 TINYINT UNSIGNED,
    n4 TINYINT UNSIGNED,
    days INT NULL,
    Tirage DATE NULL,
    max_fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q4_combo_stats_order (n1, n2, n3, n4, days, Tirage, max_fois, max_days)
SELECT
    n1, n2, n3, n4,
    DATEDIFF(CURDATE(), MAX(Tirage)) AS days,
    MAX(Tirage) AS Tirage,
    COUNT(*) AS max_fois,
    NULL AS max_days
FROM Q4_stats_order
GROUP BY n1, n2, n3, n4;

UPDATE Q4_combo_stats_order c
JOIN Q4_stats_order s
  ON c.n1 = s.n1 AND c.n2 = s.n2 AND c.n3 = s.n3 AND c.n4 = s.n4 AND c.Tirage = s.Tirage
SET c.max_days = s.max_days;
