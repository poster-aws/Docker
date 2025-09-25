DELIMITER //

DROP PROCEDURE IF EXISTS build_analyse//
CREATE PROCEDURE build_analyse()
BEGIN
  -- 1) Пересоздаём таблицу Analyse
  DROP TABLE IF EXISTS Analyse;

  CREATE TABLE Analyse (
    pos VARCHAR(5) NOT NULL PRIMARY KEY,
    `1`  INT NOT NULL DEFAULT 0, `2`  INT NOT NULL DEFAULT 0, `3`  INT NOT NULL DEFAULT 0, `4`  INT NOT NULL DEFAULT 0,
    `5`  INT NOT NULL DEFAULT 0, `6`  INT NOT NULL DEFAULT 0, `7`  INT NOT NULL DEFAULT 0, `8`  INT NOT NULL DEFAULT 0,
    `9`  INT NOT NULL DEFAULT 0, `10` INT NOT NULL DEFAULT 0, `11` INT NOT NULL DEFAULT 0, `12` INT NOT NULL DEFAULT 0,
    `13` INT NOT NULL DEFAULT 0, `14` INT NOT NULL DEFAULT 0, `15` INT NOT NULL DEFAULT 0, `16` INT NOT NULL DEFAULT 0,
    `17` INT NOT NULL DEFAULT 0, `18` INT NOT NULL DEFAULT 0, `19` INT NOT NULL DEFAULT 0, `20` INT NOT NULL DEFAULT 0,
    `21` INT NOT NULL DEFAULT 0, `22` INT NOT NULL DEFAULT 0, `23` INT NOT NULL DEFAULT 0, `24` INT NOT NULL DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

  -- 2) Заполняем таблицу сводными данными из Tout
  INSERT INTO Analyse (pos,
    `1`,`2`,`3`,`4`,`5`,`6`,`7`,`8`,`9`,`10`,`11`,`12`,
    `13`,`14`,`15`,`16`,`17`,`18`,`19`,`20`,`21`,`22`,`23`,`24`)
  SELECT * FROM (
    SELECT 'n1'  AS pos,
      SUM(n1=1),  SUM(n1=2),  SUM(n1=3),  SUM(n1=4),
      SUM(n1=5),  SUM(n1=6),  SUM(n1=7),  SUM(n1=8),
      SUM(n1=9),  SUM(n1=10), SUM(n1=11), SUM(n1=12),
      SUM(n1=13), SUM(n1=14), SUM(n1=15), SUM(n1=16),
      SUM(n1=17), SUM(n1=18), SUM(n1=19), SUM(n1=20),
      SUM(n1=21), SUM(n1=22), SUM(n1=23), SUM(n1=24)
    FROM Tout

    UNION ALL
    SELECT 'n2',
      SUM(n2=1),  SUM(n2=2),  SUM(n2=3),  SUM(n2=4),
      SUM(n2=5),  SUM(n2=6),  SUM(n2=7),  SUM(n2=8),
      SUM(n2=9),  SUM(n2=10), SUM(n2=11), SUM(n2=12),
      SUM(n2=13), SUM(n2=14), SUM(n2=15), SUM(n2=16),
      SUM(n2=17), SUM(n2=18), SUM(n2=19), SUM(n2=20),
      SUM(n2=21), SUM(n2=22), SUM(n2=23), SUM(n2=24)
    FROM Tout

    UNION ALL
    SELECT 'n3',
      SUM(n3=1),  SUM(n3=2),  SUM(n3=3),  SUM(n3=4),
      SUM(n3=5),  SUM(n3=6),  SUM(n3=7),  SUM(n3=8),
      SUM(n3=9),  SUM(n3=10), SUM(n3=11), SUM(n3=12),
      SUM(n3=13), SUM(n3=14), SUM(n3=15), SUM(n3=16),
      SUM(n3=17), SUM(n3=18), SUM(n3=19), SUM(n3=20),
      SUM(n3=21), SUM(n3=22), SUM(n3=23), SUM(n3=24)
    FROM Tout

    UNION ALL
    SELECT 'n4',
      SUM(n4=1),  SUM(n4=2),  SUM(n4=3),  SUM(n4=4),
      SUM(n4=5),  SUM(n4=6),  SUM(n4=7),  SUM(n4=8),
      SUM(n4=9),  SUM(n4=10), SUM(n4=11), SUM(n4=12),
      SUM(n4=13), SUM(n4=14), SUM(n4=15), SUM(n4=16),
      SUM(n4=17), SUM(n4=18), SUM(n4=19), SUM(n4=20),
      SUM(n4=21), SUM(n4=22), SUM(n4=23), SUM(n4=24)
    FROM Tout

    UNION ALL
    SELECT 'n5',
      SUM(n5=1),  SUM(n5=2),  SUM(n5=3),  SUM(n5=4),
      SUM(n5=5),  SUM(n5=6),  SUM(n5=7),  SUM(n5=8),
      SUM(n5=9),  SUM(n5=10), SUM(n5=11), SUM(n5=12),
      SUM(n5=13), SUM(n5=14), SUM(n5=15), SUM(n5=16),
      SUM(n5=17), SUM(n5=18), SUM(n5=19), SUM(n5=20),
      SUM(n5=21), SUM(n5=22), SUM(n5=23), SUM(n5=24)
    FROM Tout

    UNION ALL
    SELECT 'n6',
      SUM(n6=1),  SUM(n6=2),  SUM(n6=3),  SUM(n6=4),
      SUM(n6=5),  SUM(n6=6),  SUM(n6=7),  SUM(n6=8),
      SUM(n6=9),  SUM(n6=10), SUM(n6=11), SUM(n6=12),
      SUM(n6=13), SUM(n6=14), SUM(n6=15), SUM(n6=16),
      SUM(n6=17), SUM(n6=18), SUM(n6=19), SUM(n6=20),
      SUM(n6=21), SUM(n6=22), SUM(n6=23), SUM(n6=24)
    FROM Tout

    UNION ALL
    SELECT 'n7',
      SUM(n7=1),  SUM(n7=2),  SUM(n7=3),  SUM(n7=4),
      SUM(n7=5),  SUM(n7=6),  SUM(n7=7),  SUM(n7=8),
      SUM(n7=9),  SUM(n7=10), SUM(n7=11), SUM(n7=12),
      SUM(n7=13), SUM(n7=14), SUM(n7=15), SUM(n7=16),
      SUM(n7=17), SUM(n7=18), SUM(n7=19), SUM(n7=20),
      SUM(n7=21), SUM(n7=22), SUM(n7=23), SUM(n7=24)
    FROM Tout

    UNION ALL
    SELECT 'n8',
      SUM(n8=1),  SUM(n8=2),  SUM(n8=3),  SUM(n8=4),
      SUM(n8=5),  SUM(n8=6),  SUM(n8=7),  SUM(n8=8),
      SUM(n8=9),  SUM(n8=10), SUM(n8=11), SUM(n8=12),
      SUM(n8=13), SUM(n8=14), SUM(n8=15), SUM(n8=16),
      SUM(n8=17), SUM(n8=18), SUM(n8=19), SUM(n8=20),
      SUM(n8=21), SUM(n8=22), SUM(n8=23), SUM(n8=24)
    FROM Tout

    UNION ALL
    SELECT 'n9',
      SUM(n9=1),  SUM(n9=2),  SUM(n9=3),  SUM(n9=4),
      SUM(n9=5),  SUM(n9=6),  SUM(n9=7),  SUM(n9=8),
      SUM(n9=9),  SUM(n9=10), SUM(n9=11), SUM(n9=12),
      SUM(n9=13), SUM(n9=14), SUM(n9=15), SUM(n9=16),
      SUM(n9=17), SUM(n9=18), SUM(n9=19), SUM(n9=20),
      SUM(n9=21), SUM(n9=22), SUM(n9=23), SUM(n9=24)
    FROM Tout

    UNION ALL
    SELECT 'n10',
      SUM(n10=1),  SUM(n10=2),  SUM(n10=3),  SUM(n10=4),
      SUM(n10=5),  SUM(n10=6),  SUM(n10=7),  SUM(n10=8),
      SUM(n10=9),  SUM(n10=10), SUM(n10=11), SUM(n10=12),
      SUM(n10=13), SUM(n10=14), SUM(n10=15), SUM(n10=16),
      SUM(n10=17), SUM(n10=18), SUM(n10=19), SUM(n10=20),
      SUM(n10=21), SUM(n10=22), SUM(n10=23), SUM(n10=24)
    FROM Tout

    UNION ALL
    SELECT 'n11',
      SUM(n11=1),  SUM(n11=2),  SUM(n11=3),  SUM(n11=4),
      SUM(n11=5),  SUM(n11=6),  SUM(n11=7),  SUM(n11=8),
      SUM(n11=9),  SUM(n11=10), SUM(n11=11), SUM(n11=12),
      SUM(n11=13), SUM(n11=14), SUM(n11=15), SUM(n11=16),
      SUM(n11=17), SUM(n11=18), SUM(n11=19), SUM(n11=20),
      SUM(n11=21), SUM(n11=22), SUM(n11=23), SUM(n11=24)
    FROM Tout

    UNION ALL
    SELECT 'n12',
      SUM(n12=1),  SUM(n12=2),  SUM(n12=3),  SUM(n12=4),
      SUM(n12=5),  SUM(n12=6),  SUM(n12=7),  SUM(n12=8),
      SUM(n12=9),  SUM(n12=10), SUM(n12=11), SUM(n12=12),
      SUM(n12=13), SUM(n12=14), SUM(n12=15), SUM(n12=16),
      SUM(n12=17), SUM(n12=18), SUM(n12=19), SUM(n12=20),
      SUM(n12=21), SUM(n12=22), SUM(n12=23), SUM(n12=24)
    FROM Tout
  ) AS t
  ORDER BY FIELD(pos,'n1','n2','n3','n4','n5','n6','n7','n8','n9','n10','n11','n12');
END//

DELIMITER ;