-- Заполнение Q2_combo_stats_norder из Q2_stats_norder
-- Порядок не важен: (1,2) и (2,1) — одна комбинация, (1,1) — одна комбинация.
-- Одна строка на каждую уникальную пару (LEAST(n1,n2), GREATEST(n1,n2)).
-- Таблица: n1, n2, days, Tirage, max_fois, max_days (n1 <= n2)

DROP TABLE IF EXISTS Q2_combo_stats_norder;

CREATE TABLE Q2_combo_stats_norder (
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    days INT NULL,
    Tirage DATE NULL,
    max_fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q2_combo_stats_norder (n1, n2, days, Tirage, max_fois, max_days)
SELECT
    LEAST(n1, n2) AS n1,
    GREATEST(n1, n2) AS n2,
    DATEDIFF(CURDATE(), MAX(Tirage)) AS days,
    MAX(Tirage) AS Tirage,
    COUNT(*) AS max_fois,
    NULL AS max_days
FROM Q2_stats_norder
GROUP BY LEAST(n1, n2), GREATEST(n1, n2);

UPDATE Q2_combo_stats_norder c
JOIN Q2_stats_norder s
  ON c.n1 = LEAST(s.n1, s.n2)
 AND c.n2 = GREATEST(s.n1, s.n2)
 AND c.Tirage = s.Tirage
SET c.max_days = s.max_days;
