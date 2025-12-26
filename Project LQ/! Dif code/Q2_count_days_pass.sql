WITH RECURSIVE nums AS (
    SELECT 0 AS num
    UNION ALL
    SELECT num + 1 FROM nums WHERE num < 9
),
hits AS (
    SELECT
        n.num,
        q.Tirage
    FROM nums n
    JOIN Q2 q
        ON n.num IN (q.n1, q.n2)
),
gaps AS (
    SELECT
        num,
        Tirage,
        DATEDIFF(
            Tirage,
            LAG(Tirage) OVER (PARTITION BY num ORDER BY Tirage)
        ) AS days_gap
    FROM hits
)
SELECT
    num        AS цифра,
    days_gap  AS дней_прошло,
    COUNT(*)  AS раз
FROM gaps
WHERE days_gap BETWEEN 10 AND 50
GROUP BY num, days_gap
ORDER BY num, days_gap;