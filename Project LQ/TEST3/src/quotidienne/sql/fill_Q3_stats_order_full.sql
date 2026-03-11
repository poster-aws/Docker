-- Формирование полной таблицы Q3_stats_order из Q3
-- Один проход по Q3 от самой старой записи к самой новой (ORDER BY Tirage)
-- Комбинация из трёх цифр (n1, n2, n3); порядок важен.
-- Требует MySQL 8.0+ (оконные функции)

DROP TABLE IF EXISTS Q3_stats_order;

CREATE TABLE Q3_stats_order (
    Tirage DATE,
    n1 TINYINT UNSIGNED,
    n2 TINYINT UNSIGNED,
    n3 TINYINT UNSIGNED,
    days INT NULL,
    days2 INT NULL,
    fois INT NULL,
    max_days INT NULL
);

INSERT INTO Q3_stats_order (Tirage, n1, n2, n3, days, days2, fois, max_days)
WITH
base AS (
    SELECT
        Tirage, n1, n2, n3,
        LAG(Tirage, 1) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage) AS prev_Tirage,
        LAG(Tirage, 2) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage) AS prev2_Tirage,
        ROW_NUMBER() OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage) AS fois,
        DATEDIFF(Tirage, LAG(Tirage, 1) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage)) AS gap
    FROM Q3
),
computed AS (
    SELECT
        Tirage, n1, n2, n3,
        IFNULL(gap, 0) AS days,
        CASE
            WHEN prev_Tirage IS NOT NULL AND prev2_Tirage IS NOT NULL
            THEN DATEDIFF(prev_Tirage, prev2_Tirage)
            ELSE 0
        END AS days2,
        fois,
        IFNULL(MAX(gap) OVER (PARTITION BY n1, n2, n3 ORDER BY Tirage ROWS UNBOUNDED PRECEDING), 0) AS max_gap
    FROM base
)
SELECT Tirage, n1, n2, n3, days, days2, fois, max_gap AS max_days FROM computed;
