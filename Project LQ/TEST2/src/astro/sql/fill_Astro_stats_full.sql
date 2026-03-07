-- Формирование полной таблицы Astro_stats из Astro
-- Читаем Astro от самой первой записи по Tirage к самой свежей.
-- Для каждой строки (каждого тиража):
--   fois — сколько раз эта комбинация (jour, mois, annee, signe) уже встречалась к этому моменту (с учётом текущей строки).
--   days — дней между предыдущим и текущим появлением этой комбинации; 0 если это первое появление.
-- Требует MySQL 8.0+

DROP TABLE IF EXISTS Astro_stats;

CREATE TABLE Astro_stats LIKE Astro;
ALTER TABLE Astro_stats
  ADD COLUMN fois INT NULL,
  ADD COLUMN days INT NULL;

INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)
WITH base AS (
    SELECT
        Tirage,
        jour,
        mois,
        annee,
        signe,
        LAG(Tirage) OVER (PARTITION BY jour, mois, annee, signe ORDER BY Tirage) AS prev_Tirage,
        ROW_NUMBER() OVER (PARTITION BY jour, mois, annee, signe ORDER BY Tirage) AS fois
    FROM Astro
)
SELECT
    Tirage,
    jour,
    mois,
    annee,
    signe,
    fois,
    IFNULL(DATEDIFF(Tirage, prev_Tirage), 0) AS days
FROM base
ORDER BY Tirage;
