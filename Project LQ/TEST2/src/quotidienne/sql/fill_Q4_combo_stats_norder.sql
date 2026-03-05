-- Заполнение Q4_combo_stats_norder из Q4_stats_norder
-- Порядок не важен: (1,2,3,4), (4,1,2,3) и т.д. — одна комбинация.
-- Одна строка на каждую уникальную четвёрку в каноническом виде (n1 <= n2 <= n3 <= n4).
-- Таблица: n1, n2, n3, n4, days, Tirage, max_fois, max_days

DROP TABLE IF EXISTS Q4_combo_stats_norder;

CREATE TABLE Q4_combo_stats_norder (
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    n3 TINYINT UNSIGNED,
    n4 TINYINT UNSIGNED,
    days INT NULL,
    Tirage DATE NULL,
    max_fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q4_combo_stats_norder (n1, n2, n3, n4, days, Tirage, max_fois, max_days)
SELECT
    SUBSTRING_INDEX(sorted_str, ',', 1) + 0 AS n1,
    SUBSTRING_INDEX(SUBSTRING_INDEX(sorted_str, ',', 2), ',', -1) + 0 AS n2,
    SUBSTRING_INDEX(SUBSTRING_INDEX(sorted_str, ',', 3), ',', -1) + 0 AS n3,
    SUBSTRING_INDEX(sorted_str, ',', -1) + 0 AS n4,
    DATEDIFF(CURDATE(), MAX(Tirage)) AS days,
    MAX(Tirage) AS Tirage,
    COUNT(*) AS max_fois,
    NULL AS max_days
FROM (
    SELECT Tirage, n1, n2, n3, n4,
        (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) AS sorted_str
    FROM Q4_stats_norder
) x
GROUP BY sorted_str;

UPDATE Q4_combo_stats_norder c
JOIN Q4_stats_norder s
  ON c.Tirage = s.Tirage
 AND (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT s.n1 AS n UNION ALL SELECT s.n2 UNION ALL SELECT s.n3 UNION ALL SELECT s.n4) t) = CONCAT(c.n1, ',', c.n2, ',', c.n3, ',', c.n4)
SET c.max_days = s.max_days;
