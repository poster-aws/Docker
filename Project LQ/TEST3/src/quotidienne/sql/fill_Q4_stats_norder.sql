-- Добавление одной строки в Q4_stats_norder: только для самой свежей записи из Q4.
-- Порядок цифр не важен: (1,2,3,4), (4,1,2,3) и т.д. — одна комбинация.
-- Канонический ключ: строка из четырёх цифр, отсортированных по возрастанию (sk).
-- Таблица Q4_stats_norder уже должна существовать.
-- Требует MySQL 8.0+

INSERT INTO Q4_stats_norder (Tirage, n1, n2, n3, n4, days, days2, fois, max_days)
SELECT
    latest.Tirage,
    latest.n1,
    latest.n2,
    latest.n3,
    latest.n4,

    IFNULL(DATEDIFF(
        latest.Tirage,
        (SELECT MAX(Tirage) FROM Q4
         WHERE Tirage < latest.Tirage
           AND (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) = latest.sk)
    ), 0),

    IFNULL((SELECT DATEDIFF(prev.Tirage, prev2.Tirage)
     FROM (SELECT MAX(Tirage) AS Tirage FROM Q4
           WHERE Tirage < latest.Tirage
             AND (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) = latest.sk) AS prev
     JOIN (SELECT MAX(Tirage) AS Tirage FROM Q4
           WHERE (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) = latest.sk
             AND Tirage < (SELECT MAX(Tirage) FROM Q4
                           WHERE Tirage < latest.Tirage
                             AND (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) = latest.sk)) AS prev2 ON 1
     WHERE prev.Tirage IS NOT NULL AND prev2.Tirage IS NOT NULL
     LIMIT 1), 0),

    (SELECT COUNT(*) FROM Q4
     WHERE Tirage <= latest.Tirage
       AND (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) = latest.sk),

    (SELECT IFNULL(MAX(gap), 0)
     FROM (SELECT DATEDIFF(Tirage, LAG(Tirage) OVER (ORDER BY Tirage)) AS gap
           FROM Q4
           WHERE (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) = latest.sk
             AND Tirage <= latest.Tirage) AS t
     WHERE gap IS NOT NULL)

FROM (SELECT Tirage, n1, n2, n3, n4,
             (SELECT GROUP_CONCAT(n ORDER BY n) FROM (SELECT n1 AS n UNION ALL SELECT n2 UNION ALL SELECT n3 UNION ALL SELECT n4) t) AS sk
      FROM Q4 ORDER BY Tirage DESC LIMIT 1) AS latest;
