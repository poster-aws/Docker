-- Формирование полной таблицы Q2_stats_norder из Q2
-- Порядок цифр не важен: (4,5) и (5,4) — одна комбинация. Группировка по (LEAST(n1,n2), GREATEST(n1,n2)).
-- Один проход от самой старой записи к самой новой (ORDER BY Tirage).
-- Требует MySQL 8.0+ (оконные функции)

DROP TABLE IF EXISTS Q2_stats_norder;

CREATE TABLE Q2_stats_norder (
    Tirage DATE,
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    days INT NULL,
    days2 INT NULL,
    fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q2_stats_norder (Tirage, n1, n2, days, days2, fois, max_days)
WITH
base AS (
    SELECT
        Tirage,
        n1,
        n2,
        LAG(Tirage, 1) OVER (PARTITION BY LEAST(n1, n2), GREATEST(n1, n2) ORDER BY Tirage) AS prev_Tirage,
        LAG(Tirage, 2) OVER (PARTITION BY LEAST(n1, n2), GREATEST(n1, n2) ORDER BY Tirage) AS prev2_Tirage,
        ROW_NUMBER() OVER (PARTITION BY LEAST(n1, n2), GREATEST(n1, n2) ORDER BY Tirage) AS fois,
        DATEDIFF(Tirage, LAG(Tirage, 1) OVER (PARTITION BY LEAST(n1, n2), GREATEST(n1, n2) ORDER BY Tirage)) AS gap
    FROM Q2
),
computed AS (
    SELECT
        Tirage,
        n1,
        n2,
        IFNULL(gap, 0) AS days,
        CASE
            WHEN prev_Tirage IS NOT NULL AND prev2_Tirage IS NOT NULL
            THEN DATEDIFF(prev_Tirage, prev2_Tirage)
            ELSE 0
        END AS days2,
        fois,
        IFNULL(MAX(gap) OVER (PARTITION BY LEAST(n1, n2), GREATEST(n1, n2) ORDER BY Tirage ROWS UNBOUNDED PRECEDING), 0) AS max_gap
    FROM base
)
SELECT Tirage, n1, n2, days, days2, fois, max_gap AS max_days
FROM computed;
