CREATE DATABASE toutourien
CHARACTER SET utf8mb4
COLLATE utf8mb4_0900_ai_ci;



CREATE TABLE Tout (
    Tirage DATE,
    n1 TINYINT, n2 TINYINT, n3 TINYINT, n4 TINYINT, n5 TINYINT, n6 TINYINT,
    n7 TINYINT, n8 TINYINT, n9 TINYINT, n10 TINYINT, n11 TINYINT, n12 TINYINT
);



CREATE TABLE tout_sorted LIKE Tout;

INSERT INTO tout_sorted
SELECT *
FROM Tout
ORDER BY Tirage ASC;

-- (опционально) удалить старую и переименовать
DROP TABLE Tout;
RENAME TABLE tout_sorted TO Tout;


SHOW GRANTS FOR 'user'@'%';


GRANT ALL PRIVILEGES ON toutourien.* TO 'user'@'%';
FLUSH PRIVILEGES;