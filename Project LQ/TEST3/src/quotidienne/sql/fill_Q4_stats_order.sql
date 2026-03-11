-- Добавление одной строки в Q4_stats_order: только для самой свежей записи из Q4.
-- Таблица Q4_stats_order уже должна существовать.
-- Требует MySQL 8.0+ (оконные функции для max_days).

INSERT INTO Q4_stats_order (Tirage, n1, n2, n3, n4, days, days2, fois, max_days)
SELECT
    latest.Tirage,
    latest.n1,
    latest.n2,
    latest.n3,
    latest.n4,

    IFNULL(DATEDIFF(
        latest.Tirage,
        (SELECT MAX(Tirage) FROM Q4
         WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND n4 = latest.n4 AND Tirage < latest.Tirage)
    ), 0),

    IFNULL((SELECT DATEDIFF(prev.Tirage, prev2.Tirage)
     FROM (SELECT MAX(Tirage) AS Tirage FROM Q4
           WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND n4 = latest.n4 AND Tirage < latest.Tirage) AS prev
     JOIN (SELECT MAX(Tirage) AS Tirage FROM Q4
           WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND n4 = latest.n4
             AND Tirage < (SELECT MAX(Tirage) FROM Q4
                           WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND n4 = latest.n4 AND Tirage < latest.Tirage)) AS prev2 ON 1
     WHERE prev.Tirage IS NOT NULL AND prev2.Tirage IS NOT NULL
     LIMIT 1), 0),

    (SELECT COUNT(*) FROM Q4
     WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND n4 = latest.n4 AND Tirage <= latest.Tirage),

    (SELECT IFNULL(MAX(gap), 0)
     FROM (SELECT DATEDIFF(Tirage, LAG(Tirage) OVER (ORDER BY Tirage)) AS gap
           FROM Q4
           WHERE n1 = latest.n1 AND n2 = latest.n2 AND n3 = latest.n3 AND n4 = latest.n4 AND Tirage <= latest.Tirage) AS t
     WHERE gap IS NOT NULL)

FROM (SELECT Tirage, n1, n2, n3, n4 FROM Q4 ORDER BY Tirage DESC LIMIT 1) AS latest;
