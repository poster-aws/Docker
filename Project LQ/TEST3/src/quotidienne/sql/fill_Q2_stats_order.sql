-- Добавление одной строки в Q2_stats_order: только для самой свежей записи из Q2.
-- Таблица Q2_stats_order уже должна существовать.
-- Требует MySQL 8.0+ (оконные функции для max_days).

INSERT INTO Q2_stats_order (Tirage, n1, n2, days, days2, fois, max_days)
SELECT
    latest.Tirage,
    latest.n1,
    latest.n2,

    -- days: дней с предыдущего выпадения этой комбинации (0 для первого раза)
    IFNULL(DATEDIFF(
        latest.Tirage,
        (SELECT MAX(Tirage) FROM Q2
         WHERE n1 = latest.n1 AND n2 = latest.n2 AND Tirage < latest.Tirage)
    ), 0),

    -- days2: интервал между предыдущим и пред-предыдущим выпадением (0 если нет двух предыдущих)
    IFNULL((SELECT DATEDIFF(prev.Tirage, prev2.Tirage)
     FROM (SELECT MAX(Tirage) AS Tirage FROM Q2
           WHERE n1 = latest.n1 AND n2 = latest.n2 AND Tirage < latest.Tirage) AS prev
     JOIN (SELECT MAX(Tirage) AS Tirage FROM Q2
           WHERE n1 = latest.n1 AND n2 = latest.n2
             AND Tirage < (SELECT MAX(Tirage) FROM Q2
                           WHERE n1 = latest.n1 AND n2 = latest.n2 AND Tirage < latest.Tirage)) AS prev2 ON 1
     WHERE prev.Tirage IS NOT NULL AND prev2.Tirage IS NOT NULL
     LIMIT 1), 0),

    -- fois: сколько раз эта комбинация выпадала за весь период (на текущую дату)
    (SELECT COUNT(*) FROM Q2
     WHERE n1 = latest.n1 AND n2 = latest.n2 AND Tirage <= latest.Tirage),

    -- max_days: максимальный интервал в днях между двумя последовательными выпадениями (по всем данным до этой даты)
    (SELECT IFNULL(MAX(gap), 0)
     FROM (SELECT DATEDIFF(Tirage, LAG(Tirage) OVER (ORDER BY Tirage)) AS gap
           FROM Q2
           WHERE n1 = latest.n1 AND n2 = latest.n2 AND Tirage <= latest.Tirage) AS t
     WHERE gap IS NOT NULL)

FROM (SELECT Tirage, n1, n2 FROM Q2 ORDER BY Tirage DESC LIMIT 1) AS latest;
