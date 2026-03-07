-- Добавление строк в Astro_stats только за последний день тиража (самая свежая дата в Astro).
-- Таблица Astro_stats уже должна существовать с колонками Tirage, jour, mois, annee, signe, fois, days.
-- Для каждой строки последнего тиража: fois — сколько раз эта комбинация (jour, mois, annee, signe) встретилась за всю историю (с учётом текущей); days — дней с предыдущего появления (0 если первое).
-- Требует MySQL 8.0+

INSERT INTO Astro_stats (Tirage, jour, mois, annee, signe, fois, days)
WITH latest_data AS (
    SELECT
        Tirage,
        jour,
        mois,
        annee,
        signe,
        ROW_NUMBER() OVER (PARTITION BY jour, mois, annee, signe ORDER BY jour, mois, annee, signe) AS rn
    FROM Astro
    WHERE Tirage = (SELECT MAX(Tirage) FROM Astro)
)
SELECT
    l.Tirage,
    l.jour,
    l.mois,
    l.annee,
    l.signe,
    (SELECT COUNT(*) FROM Astro a
     WHERE a.jour = l.jour AND a.mois = l.mois AND a.annee = l.annee AND a.signe = l.signe
       AND a.Tirage < l.Tirage) + l.rn AS fois,
    IFNULL(DATEDIFF(
        l.Tirage,
        (SELECT MAX(a.Tirage) FROM Astro a
         WHERE a.jour = l.jour AND a.mois = l.mois AND a.annee = l.annee AND a.signe = l.signe
           AND a.Tirage < l.Tirage)
    ), 0) AS days
FROM latest_data l;
