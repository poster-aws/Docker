SET @start_date = '2025-10-02';

DROP TABLE IF EXISTS anal3;

CREATE TABLE anal3 (
    n1           TINYINT UNSIGNED NOT NULL,
    n2           TINYINT UNSIGNED NOT NULL,
    n3           TINYINT UNSIGNED NOT NULL,
    fois         INT UNSIGNED NOT NULL DEFAULT 0,
    last_tirage  DATE DEFAULT NULL,
    PRIMARY KEY (n1, n2, n3)
);

INSERT INTO anal3 (n1, n2, n3, fois, last_tirage)
SELECT
    c.n1,
    c.n2,
    c.n3,
    COALESCE(t.fois, 0)        AS fois,
    t.last_tirage
FROM comb3 c
LEFT JOIN (
    SELECT
        x.n1,
        x.n2,
        x.n3,
        COUNT(*)        AS fois,
        MAX(x.Tirage)  AS last_tirage
    FROM (
        SELECT
            a.Tirage,
            LEAST(a.num, b.num, d.num)        AS n1,
            (a.num + b.num + d.num
             - LEAST(a.num, b.num, d.num)
             - GREATEST(a.num, b.num, d.num)) AS n2,
            GREATEST(a.num, b.num, d.num)     AS n3
        FROM (
            SELECT Tirage, n1 AS num FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n2  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n3  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n4  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n5  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n6  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n7  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n8  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n9  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n10 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n11 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n12 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n13 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n14 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n15 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n16 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n17 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n18 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n19 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n20 FROM banco WHERE Tirage >= @start_date
        ) a
        JOIN (
            SELECT Tirage, n1 AS num FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n2  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n3  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n4  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n5  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n6  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n7  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n8  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n9  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n10 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n11 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n12 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n13 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n14 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n15 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n16 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n17 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n18 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n19 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n20 FROM banco WHERE Tirage >= @start_date
        ) b ON a.Tirage = b.Tirage AND a.num < b.num
        JOIN (
            SELECT Tirage, n1 AS num FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n2  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n3  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n4  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n5  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n6  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n7  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n8  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n9  FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n10 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n11 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n12 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n13 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n14 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n15 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n16 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n17 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n18 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n19 FROM banco WHERE Tirage >= @start_date
            UNION ALL SELECT Tirage, n20 FROM banco WHERE Tirage >= @start_date
        ) d ON a.Tirage = d.Tirage AND b.num < d.num
    ) x
    GROUP BY x.n1, x.n2, x.n3
) t
ON c.n1 = t.n1
AND c.n2 = t.n2
AND c.n3 = t.n3;

