-- Добавление одной строки в Q3_stats_norder: только для самой свежей записи из Q3.
-- Порядок цифр не важен: (1,2,3), (3,2,1), (2,1,3) — одна комбинация.
-- Каноническая форма: (a, b, c) где a = LEAST(n1,n2,n3), c = GREATEST(n1,n2,n3), b = среднее из трёх.
-- Таблица Q3_stats_norder уже должна существовать.
-- Требует MySQL 8.0+

INSERT INTO Q3_stats_norder (Tirage, n1, n2, n3, days, days2, fois, max_days)
SELECT
    latest.Tirage,
    latest.n1,
    latest.n2,
    latest.n3,

    IFNULL(DATEDIFF(
        latest.Tirage,
        (SELECT MAX(Tirage) FROM Q3
         WHERE LEAST(n1, n2, n3) = latest.a
           AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
           AND GREATEST(n1, n2, n3) = latest.c
           AND Tirage < latest.Tirage)
    ), 0),

    IFNULL((SELECT DATEDIFF(prev.Tirage, prev2.Tirage)
     FROM (SELECT MAX(Tirage) AS Tirage FROM Q3
           WHERE LEAST(n1, n2, n3) = latest.a
             AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
             AND GREATEST(n1, n2, n3) = latest.c
             AND Tirage < latest.Tirage) AS prev
     JOIN (SELECT MAX(Tirage) AS Tirage FROM Q3
           WHERE LEAST(n1, n2, n3) = latest.a
             AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
             AND GREATEST(n1, n2, n3) = latest.c
             AND Tirage < (SELECT MAX(Tirage) FROM Q3
                           WHERE LEAST(n1, n2, n3) = latest.a
                             AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
                             AND GREATEST(n1, n2, n3) = latest.c AND Tirage < latest.Tirage)) AS prev2 ON 1
     WHERE prev.Tirage IS NOT NULL AND prev2.Tirage IS NOT NULL
     LIMIT 1), 0),

    (SELECT COUNT(*) FROM Q3
     WHERE LEAST(n1, n2, n3) = latest.a
       AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
       AND GREATEST(n1, n2, n3) = latest.c
       AND Tirage <= latest.Tirage),

    (SELECT IFNULL(MAX(gap), 0)
     FROM (SELECT DATEDIFF(Tirage, LAG(Tirage) OVER (ORDER BY Tirage)) AS gap
           FROM Q3
           WHERE LEAST(n1, n2, n3) = latest.a
             AND (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) = latest.b
             AND GREATEST(n1, n2, n3) = latest.c
             AND Tirage <= latest.Tirage) AS t
     WHERE gap IS NOT NULL)

FROM (SELECT Tirage, n1, n2, n3,
             LEAST(n1, n2, n3) AS a,
             (n1 + n2 + n3 - LEAST(n1, n2, n3) - GREATEST(n1, n2, n3)) AS b,
             GREATEST(n1, n2, n3) AS c
      FROM Q3 ORDER BY Tirage DESC LIMIT 1) AS latest;
