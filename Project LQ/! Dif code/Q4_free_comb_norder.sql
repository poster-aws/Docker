-- 1.	Создания таблицы Q4_free_comb_norder,
-- 2.	Первичного заполнения всех 10 000 комбинаций от 0000 до 9999,
-- 3.	Удаления из неё всех комбинаций, которые уже встречаются в Q4 в любом порядке (norder) — через перебор всех 24 перестановок.

-- 1. Удалить таблицу, если она уже существует
DROP TABLE IF EXISTS Q4_free_comb_norder;

-- 2. Создать таблицу
CREATE TABLE Q4_free_comb_norder (
    id INT AUTO_INCREMENT PRIMARY KEY,
    n1 TINYINT UNSIGNED NOT NULL,
    n2 TINYINT UNSIGNED NOT NULL,
    n3 TINYINT UNSIGNED NOT NULL,
    n4 TINYINT UNSIGNED NOT NULL
);

-- 3. Заполнить 10 000 комбинаций от 0000 до 9999
INSERT INTO Q4_free_comb_norder (n1, n2, n3, n4)
SELECT
  a.num AS n1,
  b.num AS n2,
  c.num AS n3,
  d.num AS n4
FROM
  (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
   UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
  (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
   UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b,
  (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
   UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c,
  (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
   UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d;

-- 4. Удалить из неё те, которые уже были в Q4 (в любом порядке)
DELETE fc
FROM Q4_free_comb_norder fc
JOIN Q4 q
  ON (
    (fc.n1 = q.n1 AND fc.n2 = q.n2 AND fc.n3 = q.n3 AND fc.n4 = q.n4) OR
    (fc.n1 = q.n1 AND fc.n2 = q.n2 AND fc.n3 = q.n4 AND fc.n4 = q.n3) OR
    (fc.n1 = q.n1 AND fc.n2 = q.n3 AND fc.n3 = q.n2 AND fc.n4 = q.n4) OR
    (fc.n1 = q.n1 AND fc.n2 = q.n3 AND fc.n3 = q.n4 AND fc.n4 = q.n2) OR
    (fc.n1 = q.n1 AND fc.n2 = q.n4 AND fc.n3 = q.n2 AND fc.n4 = q.n3) OR
    (fc.n1 = q.n1 AND fc.n2 = q.n4 AND fc.n3 = q.n3 AND fc.n4 = q.n2) OR

    (fc.n1 = q.n2 AND fc.n2 = q.n1 AND fc.n3 = q.n3 AND fc.n4 = q.n4) OR
    (fc.n1 = q.n2 AND fc.n2 = q.n1 AND fc.n3 = q.n4 AND fc.n4 = q.n3) OR
    (fc.n1 = q.n2 AND fc.n2 = q.n3 AND fc.n3 = q.n1 AND fc.n4 = q.n4) OR
    (fc.n1 = q.n2 AND fc.n2 = q.n3 AND fc.n3 = q.n4 AND fc.n4 = q.n1) OR
    (fc.n1 = q.n2 AND fc.n2 = q.n4 AND fc.n3 = q.n1 AND fc.n4 = q.n3) OR
    (fc.n1 = q.n2 AND fc.n2 = q.n4 AND fc.n3 = q.n3 AND fc.n4 = q.n1) OR

    (fc.n1 = q.n3 AND fc.n2 = q.n1 AND fc.n3 = q.n2 AND fc.n4 = q.n4) OR
    (fc.n1 = q.n3 AND fc.n2 = q.n1 AND fc.n3 = q.n4 AND fc.n4 = q.n2) OR
    (fc.n1 = q.n3 AND fc.n2 = q.n2 AND fc.n3 = q.n1 AND fc.n4 = q.n4) OR
    (fc.n1 = q.n3 AND fc.n2 = q.n2 AND fc.n3 = q.n4 AND fc.n4 = q.n1) OR
    (fc.n1 = q.n3 AND fc.n2 = q.n4 AND fc.n3 = q.n1 AND fc.n4 = q.n2) OR
    (fc.n1 = q.n3 AND fc.n2 = q.n4 AND fc.n3 = q.n2 AND fc.n4 = q.n1) OR

    (fc.n1 = q.n4 AND fc.n2 = q.n1 AND fc.n3 = q.n2 AND fc.n4 = q.n3) OR
    (fc.n1 = q.n4 AND fc.n2 = q.n1 AND fc.n3 = q.n3 AND fc.n4 = q.n2) OR
    (fc.n1 = q.n4 AND fc.n2 = q.n2 AND fc.n3 = q.n1 AND fc.n4 = q.n3) OR
    (fc.n1 = q.n4 AND fc.n2 = q.n2 AND fc.n3 = q.n3 AND fc.n4 = q.n1) OR
    (fc.n1 = q.n4 AND fc.n2 = q.n3 AND fc.n3 = q.n1 AND fc.n4 = q.n2) OR
    (fc.n1 = q.n4 AND fc.n2 = q.n3 AND fc.n3 = q.n2 AND fc.n4 = q.n1)
  );