-- 1. nums Создаёт список чисел 1…70.
-- 2. hits Для каждого числа находит все тиражи, где оно встречалось (в любой из 20 позиций).
-- 3. gaps Для каждого числа считает разницу в днях между текущим и предыдущим выпадением:
-- 4. Финальный SELECT Оставляем интервалы 10–50 дней

WITH RECURSIVE nums AS (
    SELECT 1 AS num
    UNION ALL
    SELECT num + 1 FROM nums WHERE num < 70
),
hits AS (
    SELECT
        n.num,
        b.Tirage
    FROM nums n
    JOIN banco b
        ON n.num IN (b.n1, b.n2, b.n3, b.n4, b.n5,
                      b.n6, b.n7, b.n8, b.n9, b.n10,
                      b.n11, b.n12, b.n13, b.n14, b.n15,
                      b.n16, b.n17, b.n18, b.n19, b.n20)
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
    num               AS число,
    days_gap          AS дней_прошло,
    COUNT(*)          AS раз_встретилось
FROM gaps
WHERE days_gap BETWEEN 10 AND 50
GROUP BY num, days_gap
ORDER BY num, days_gap;