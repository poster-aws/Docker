-- Формирование полной таблицы Q4_stats_norder из Q4
-- Порядок цифр не важен: (1,2,3,4) и (4,1,2,3) — одна комбинация.
-- Группировка по канонической четвёрке (c1 <= c2 <= c3 <= c4), где c1..c4 — сортированные n1..n4.
-- Один проход от самой старой записи к самой новой (ORDER BY Tirage).
-- Требует MySQL 8.0+ (оконные функции)

DROP TABLE IF EXISTS Q4_stats_norder;

CREATE TABLE Q4_stats_norder (
    Tirage DATE,
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    n3 TINYINT UNSIGNED,
    n4 TINYINT UNSIGNED,
    days INT NULL,
    days2 INT NULL,
    fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q4_stats_norder (Tirage, n1, n2, n3, n4, days, days2, fois, max_days)
WITH
base0 AS (
    SELECT Tirage, n1, n2, n3, n4,
        (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) AS sorted_str
    FROM Q4
),
base1 AS (
    SELECT Tirage, n1, n2, n3, n4,
        SUBSTRING_INDEX(sorted_str, ',', 1) + 0 AS c1,
        SUBSTRING_INDEX(SUBSTRING_INDEX(sorted_str, ',', 2), ',', -1) + 0 AS c2,
        SUBSTRING_INDEX(SUBSTRING_INDEX(sorted_str, ',', 3), ',', -1) + 0 AS c3,
        SUBSTRING_INDEX(sorted_str, ',', -1) + 0 AS c4
    FROM base0
),
base AS (
    SELECT
        Tirage, n1, n2, n3, n4, c1, c2, c3, c4,
        LAG(Tirage, 1) OVER (PARTITION BY c1, c2, c3, c4 ORDER BY Tirage) AS prev_Tirage,
        LAG(Tirage, 2) OVER (PARTITION BY c1, c2, c3, c4 ORDER BY Tirage) AS prev2_Tirage,
        ROW_NUMBER() OVER (PARTITION BY c1, c2, c3, c4 ORDER BY Tirage) AS fois,
        DATEDIFF(Tirage, LAG(Tirage, 1) OVER (PARTITION BY c1, c2, c3, c4 ORDER BY Tirage)) AS gap
    FROM base1
),
computed AS (
    SELECT
        Tirage, n1, n2, n3, n4,
        IFNULL(gap, 0) AS days,
        CASE
            WHEN prev_Tirage IS NOT NULL AND prev2_Tirage IS NOT NULL
            THEN DATEDIFF(prev_Tirage, prev2_Tirage)
            ELSE 0
        END AS days2,
        fois,
        IFNULL(MAX(gap) OVER (PARTITION BY c1, c2, c3, c4 ORDER BY Tirage ROWS UNBOUNDED PRECEDING), 0) AS max_gap
    FROM base
)
SELECT Tirage, n1, n2, n3, n4, days, days2, fois, max_gap AS max_days FROM computed;