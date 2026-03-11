-- Заполнение Q3_combo_stats_norder из Q3_stats_norder
-- Порядок не важен: (1,2,3), (3,2,1) и т.д. — одна комбинация.
-- Одна строка на каждую уникальную тройку в каноническом виде (n1 <= n2 <= n3).
-- Таблица: n1, n2, n3, days, Tirage, max_fois, max_days

DROP TABLE IF EXISTS Q3_combo_stats_norder;

CREATE TABLE Q3_combo_stats_norder (
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    n3 TINYINT UNSIGNED,
    days INT NULL,
    Tirage DATE NULL,
    max_fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q3_combo_stats_norder (n1, n2, n3, days, Tirage, max_fois, max_days)
SELECT
    LEAST(n1, n2, n3) AS n1,
    (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) AS n2,
    GREATEST(n1, n2, n3) AS n3,
    DATEDIFF(CURDATE(), MAX(Tirage)) AS days,
    MAX(Tirage) AS Tirage,
    COUNT(*) AS max_fois,
    NULL AS max_days
FROM Q3_stats_norder
GROUP BY LEAST(n1, n2, n3), (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)), GREATEST(n1, n2, n3);

UPDATE Q3_combo_stats_norder c
JOIN Q3_stats_norder s
  ON c.n1 = LEAST(s.n1, s.n2, s.n3)
 AND c.n2 = (s.n1 + s.n2 + s.n3 - LEAST(s.n1, s.n2, s.n3) - GREATEST(s.n1, s.n2, s.n3))
 AND c.n3 = GREATEST(s.n1, s.n2, s.n3)
 AND c.Tirage = s.Tirage
SET c.max_days = s.max_days;
