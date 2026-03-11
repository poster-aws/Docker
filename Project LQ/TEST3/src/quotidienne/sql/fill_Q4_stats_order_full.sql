-- Формирование полной таблицы Q4_stats_order из Q4
-- Один проход по Q4 от самой старой записи к самой новой (ORDER BY Tirage)
-- Комбинация из четырёх цифр (n1, n2, n3, n4); порядок важен.
-- Требует MySQL 8.0+ (оконные функции)

DROP TABLE IF EXISTS Q4_stats_order;

CREATE TABLE Q4_stats_order (
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

INSERT INTO Q4_stats_order (Tirage, n1, n2, n3, n4, days, days2, fois, max_days)
WITH
base AS (
    SELECT
        Tirage, n1, n2, n3, n4,
        LAG(Tirage, 1) OVER (PARTITION BY n1, n2, n3, n4 ORDER BY Tirage) AS prev_Tirage,
        LAG(Tirage, 2) OVER (PARTITION BY n1, n2, n3, n4 ORDER BY Tirage) AS prev2_Tirage,
        ROW_NUMBER() OVER (PARTITION BY n1, n2, n3, n4 ORDER BY Tirage) AS fois,
        DATEDIFF(Tirage, LAG(Tirage, 1) OVER (PARTITION BY n1, n2, n3, n4 ORDER BY Tirage)) AS gap
    FROM Q4
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
        IFNULL(MAX(gap) OVER (PARTITION BY n1, n2, n3, n4 ORDER BY Tirage ROWS UNBOUNDED PRECEDING), 0) AS max_gap
    FROM base
)
SELECT Tirage, n1, n2, n3, n4, days, days2, fois, max_gap AS max_days FROM computed;
